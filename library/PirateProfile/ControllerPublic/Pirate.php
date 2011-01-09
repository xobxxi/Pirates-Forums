<?php

class PirateProfile_ControllerPublic_Pirate extends XenForo_ControllerPublic_Abstract
{
	public function actionIndex()
	{
		$user_id = $this->_input->filterSingle('id', XenForo_Input::UINT);

		if (empty($user_id))
		{
			$user	  = XenForo_Visitor::getInstance();
			$user_id  = $user['user_id'];
			if (empty($user_id)) throw $this->getNoPermissionResponseException();
		}
		else
		{
			$user = $this->_getUserModel()->getUserById($user_id);
			if (empty($user['user_id']))
			{
				throw $this->responseException(
					$this->responseError(new XenForo_Phrase('requested_member_not_found'), 404)
				);
			}
		}

		$this->canonicalizeRequestUrl(
			XenForo_Link::buildPublicLink('pirates', $user)
		);
		
		$pirateModel = $this->_getPirateModel();
		
		$perms = $pirateModel->getPermissions();
		if (!$perms['view']) throw $this->getNoPermissionResponseException();

		$pirates = $pirateModel->getUserPirates($user_id);
		$pirates = $this->_censorPirates($pirates);

		$viewParams = array(
			'user'	  => $user,
			'perms'   => $perms,
			'pirates' => $pirates
		);
		return $this->responseView('PirateProfile_ViewPublic_Pirates',
				'pirateProfile_view', $viewParams);
	}
	
	public function actionList()
	{
		throw $this->responseException($this->responseError(
			new XenForo_Phrase('pirateProfile_requested_pirate_not_found'), 404)
		);
		
		$limit = 10;
		$page = 1;
		
		$pirates = $this->_getPirateModel()->getAllPirates($limit, $page);
		
		$viewParams = array('pirates' => $pirates);
		return $this->responseView('PirateProfile_ViewPublic_Pirates',
			'pirateProfile_list', $viewParams);
	}

	public function actionCard()
	{
		$pirate_id = $this->_input->filterSingle('id', XenForo_Input::UINT);
		
		$pirateModel = $this->_getPirateModel();
		
		$perms = $pirateModel->getPermissions();
		if (!$perms['view']) throw $this->getNoPermissionResponseException();
		
		$pirate	= $pirateModel->getPirateById($pirate_id, array('likeUserId' => XenForo_Visitor::getUserId()));

		if (empty($pirate))
		{
			throw $this->responseException($this->responseError(
				new XenForo_Phrase('pirateProfile_requested_pirate_not_found'), 404)
			);
		}

		$pirate = $this->_preparePirate($this->_censorPirate($pirate));
		
		$pirate['canLike'] = true;
		$visitor = XenForo_Visitor::getInstance();
		if (empty($visitor['user_id']) OR !$perms['view'])
		{
			$pirate['canLike'] = false;
		}

		$user = $this->_getUserModel()->getUserById($pirate['user_id']);

		$viewParams = array(
			'user'	   => $user,
			'perms'    => $perms,
			'pirate'   => $pirate,
		);
		return $this->responseView(
			'PirateProfile_ViewPublic_Pirates', 'pirateProfile_pirate_card', $viewParams
		);
	}
	
	public function actionLike()
	{
		$pirate_id = $this->_input->filterSingle('id', XenForo_Input::UINT);
		
		$pirateModel = $this->getModelFromCache('PirateProfile_Model_Pirate');
		
		$perms = $pirateModel->getPermissions();
		if (!$perms['view']) throw $this->getNoPermissionResponseException();
		
		$pirate = $this->getModelFromCache('PirateProfile_Model_Pirate')
		               ->getPirateById($pirate_id);
		
		if (empty($pirate))
		{
			throw $this->responseException($this->responseError(
				new XenForo_Phrase('pirateProfile_requested_pirate_not_found'), 404)
			);	
		}
		
		$user   = $this->getModelFromCache('XenForo_Model_User')
		               ->getUserById($pirate['user_id']);
		
		if (!$this->_getPirateModel()->canLikePirate($pirate, $user, $errorPhraseKey))
		{
			throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
		}
		
		$likeModel = $this->getModelFromCache('XenForo_Model_Like');

		$existingLike = $likeModel->getContentLikeByLikeUser('pirate', $pirate_id, XenForo_Visitor::getUserId());
		
		if ($this->_request->isPost())
		{
			if ($existingLike)
			{
				$latestUsers = $likeModel->unlikeContent($existingLike);
			}
			else
			{
				$latestUsers = $likeModel->likeContent('pirate', $pirate_id, $pirate['user_id']);
			}
			
			$liked = ($existingLike ? false : true);

			if ($this->_noRedirect() && $latestUsers !== false)
			{
				$pirate['likeUsers'] = $latestUsers;
				$pirate['likes'] += ($liked ? 1 : -1);
				$pirate['like_date'] = ($liked ? XenForo_Application::$time : 0);

				$viewParams = array(
					'pirate' => $pirate,
					'user'   => $user,
					'liked'  => $liked,
				);

				return $this->responseView('PirateProfile_ViewPublic_LikeConfirmed', '', $viewParams);
			}
			else
			{
				return $this->responseRedirect(
						XenForo_ControllerResponse_Redirect::SUCCESS,
						XenForo_Link::buildPublicLink('pirates/card', $pirate)
				);
			}
		}
		else
		{
			$viewParams = array(
				'pirate' => $pirate,
				'user'   => $user,
				'like'   => $existingLike
			);

			return $this->responseView('PirateProfile_ViewPublic_Pirates', 'pirateProfile_pirate_like', $viewParams);
		}
	}
	
	public function actionLikes()
	{
		$pirate_id = $this->_input->filterSingle('id', XenForo_Input::UINT);
		
		$pirateModel = $this->getModelFromCache('PirateProfile_Model_Pirate');
		
		$perms = $pirateModel->getPermissions();
		if (!$perms['view']) throw $this->getNoPermissionResponseException();
		
		$pirate = $this->getModelFromCache('PirateProfile_Model_Pirate')
		               ->getPirateById($pirate_id);
		
		if (empty($pirate))
		{
			throw $this->responseException($this->responseError(
				new XenForo_Phrase('pirateProfile_requested_pirate_not_found'), 404)
			);	
		}
		
		$user   = $this->getModelFromCache('XenForo_Model_User')
		               ->getUserById($pirate['user_id']);
		
		$likes =  $this->getModelFromCache('XenForo_Model_Like')->getContentLikes('pirate', $pirate_id);
		if (!$likes)
		{
			return $this->responseError(new XenForo_Phrase('pirateProfile_no_one_has_liked_this_pirate_yet'));
		}

		$viewParams = array(
			'pirate' => $pirate,
			'user'   => $user,
			'likes'  => $likes	
		);

		return $this->responseView('PirateProfile_ViewPublic_Pirates', 'pirateProfile_pirate_likes', $viewParams);
	}

	public function actionAdd()
	{
		$visitor = XenForo_Visitor::getInstance();
		if (empty($visitor['user_id'])) throw $this->getNoPermissionResponseException();
		
		$pirateModel = $this->_getPirateModel();
		
		$perms = $pirateModel->getPermissions();
		if (!$perms['add']) throw $this->getNoPermissionResponseException();
		
		$attachmentParams = $pirateModel->getAttachmentParams(array());
		$attachmentConstraints = PirateProfile_AttachmentHandler_Pirate::getAttachmentConstraints();
		
		$viewParams = array(
			'attachmentParams'		=> $attachmentParams,
			'attachmentConstraints' => $attachmentConstraints
		);
		
		return $this->responseView(
			'PirateProfile_ViewPublic_Pirates', 'pirateProfile_add', $viewParams
		);
	}

	public function actionEdit()
	{
		$pirate_id = $this->_input->filterSingle('id', XenForo_Input::UINT);

		$pirateModel = $this->_getPirateModel();
		
		$perms = $pirateModel->getPermissions();
		if (!$perms['edit']) throw $this->getNoPermissionResponseException();
		
		$pirate		 = $pirateModel->getPirateById($pirate_id);

		if (empty($pirate))
		{
			throw $this->responseException($this->responseError(
				new XenForo_Phrase('pirateProfile_requested_pirate_not_found'), 404)
			);
		}

		$this->canonicalizeRequestUrl(
			XenForo_Link::buildPublicLink('pirates/edit', $pirate)
		);

		$visitor = XenForo_Visitor::getInstance();
		$this->_assertCanManagePirate($pirate, $visitor);

		$user = $this->_getUserModel()->getUserById($pirate['user_id']);

        $pictures = $pirateModel->getPicturesById($pirate['pirate_id']);
		$attachmentParams = $this->_getPirateModel()->getAttachmentParams(array(
			'pirate_id' => $pirate['pirate_id']
		));
		$attachmentConstraints = PirateProfile_AttachmentHandler_Pirate::getAttachmentConstraints();

		$viewParams = array(
			'user'					=> $user,
			'pirate'				=> $pirate,
			'attachments'			=> $pictures,
			'attachmentParams'		=> $attachmentParams,
			'attachmentConstraints' => $attachmentConstraints
		);
		return $this->responseView(
			'PirateProfile_ViewPublic_Pirates', 'pirateProfile_edit', $viewParams
		);
	}

	public function actionDelete()
	{
		$pirate_id = $this->_input->filterSingle('id', XenForo_Input::UINT);

		$pirateModel = $this->_getPirateModel();
		
		$perms = $pirateModel->getPermissions();
		if (!$perms['delete']) throw $this->getNoPermissionResponseException();
		
		$pirate		 = $pirateModel->getPirateById($pirate_id);

		if (empty($pirate))
		{
			throw $this->responseException($this->responseError(
				new XenForo_Phrase('pirateProfile_requested_pirate_not_found'), 404)
			);
		}

		$this->canonicalizeRequestUrl(
			XenForo_Link::buildPublicLink('pirates/delete', $pirate)
		);

		$visitor = XenForo_Visitor::getInstance();
		$this->_assertCanManagePirate($pirate, $visitor);

		$user = $this->_getUserModel()->getUserById($pirate['user_id']);

		if ($this->isConfirmedPost()) {
			$dw = XenForo_DataWriter::create('PirateProfile_DataWriter_Pirate');
			$dw->setExistingData($pirate_id);
			$dw->delete();

			return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::SUCCESS,
					XenForo_Link::buildPublicLink('pirates', $user)
			);
		}

		$viewParams = array(
			'pirate' => $pirate,
			'user'	 => $user
		);
		return $this->responseView(
			'PirateProfile_ViewPublic_Pirates', 'pirateProfile_delete', $viewParams
		);
	}

	public function actionNew()
	{
		$visitor = XenForo_Visitor::getInstance();
		if (empty($visitor['user_id'])) throw $this->getNoPermissionResponseException();
		
		$pirateModel = $this->_getPirateModel();
		
		$perms = $pirateModel->getPermissions();
		if (!$perms['add']) throw $this->getNoPermissionResponseException();

		$this->_assertPostOnly();

		$input = $this->_input->filter(array(
			'name'		 => XenForo_Input::STRING,
			'level'		 => XenForo_Input::UINT,
			'guild'		 => XenForo_Input::STRING,
			'cannon'	 => XenForo_Input::UINT,
			'sailing'	 => XenForo_Input::UINT,
			'sword'		 => XenForo_Input::UINT,
			'shooting'	 => XenForo_Input::UINT,
			'doll'		 => XenForo_Input::UINT,
			'dagger'	 => XenForo_Input::UINT,
			'grenade'	 => XenForo_Input::UINT,
			'staff'		 => XenForo_Input::UINT,
			'potions'	 => XenForo_Input::UINT,
			'fishing'	 => XenForo_Input::UINT,
			'extra'		 => XenForo_Input::STRING,
			'make_fit'   => XenForo_Input::UINT));
		$input = $this->_stripZeros($input);
		
		$attachment = $this->_input->filter(array(
			'attachment_hash' => XenForo_Input::STRING)
		);

		$dw = XenForo_DataWriter::create('PirateProfile_DataWriter_Pirate');
		$dw->set('user_id', $visitor['user_id']);
		
		if (!$dw->setPirate($input))
		{
			return $this->responseError(
				new XenForo_Phrase('pirateProfile_notoriety_level_too_low')
			);
		}
		
		$dw->setExtraData('attachment_hash', $attachment['attachment_hash']);
		$dw->save();

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::RESOURCE_CREATED,
			XenForo_Link::buildPublicLink('pirates', $visitor),
			new XenForo_Phrase(
				   'pirateProfile_the_pirate_has_been_saved_successfully'
			)
		);
	}

	public function actionSave()
	{
		$visitor = XenForo_Visitor::getInstance();
		if (empty($visitor['user_id'])) throw $this->getNoPermissionResponseException();
		
		$pirateModel = $this->_getPirateModel();
		
		$perms = $pirateModel->getPermissions();
		if (!$perms['edit']) throw $this->getNoPermissionResponseException();

		$this->_assertPostOnly();

		$pirate_id = $this->_input->filterSingle('id', XenForo_Input::UINT);

		$pirate = $pirateModel->getPirateById($pirate_id);
		if (empty($pirate)) throw $this->getNoPermissionResponseException();

		$user = $this->_getUserModel()->getUserById($pirate['user_id']);

		$input = $this->_input->filter(array(
			'name'		 => XenForo_Input::STRING,
			'level'		 => XenForo_Input::UINT,
			'guild'		 => XenForo_Input::STRING,
			'cannon'	 => XenForo_Input::UINT,
			'sailing'	 => XenForo_Input::UINT,
			'sword'		 => XenForo_Input::UINT,
			'shooting'	 => XenForo_Input::UINT,
			'doll'		 => XenForo_Input::UINT,
			'dagger'	 => XenForo_Input::UINT,
			'grenade'	 => XenForo_Input::UINT,
			'staff'		 => XenForo_Input::UINT,
			'potions'	 => XenForo_Input::UINT,
			'fishing'	 => XenForo_Input::UINT,
			'extra'		 => XenForo_Input::STRING,
			'make_fit'   => XenForo_Input::UINT));
		$input = $this->_stripZeros($input);

		$attachment = $this->_input->filter(array(
			'attachment_hash' => XenForo_Input::STRING)
		);

		$dw = XenForo_DataWriter::create('PirateProfile_DataWriter_Pirate');
		$dw->setExistingData($pirate_id);

		if (!$dw->setPirate($input))
		{
			return $this->responseError(
				new XenForo_Phrase('pirateProfile_notoriety_level_too_low')
			);
		}

		$dw->setExtraData('attachment_hash', $attachment['attachment_hash']);
		$dw->save();

		$checks = array(
			XenForo_Link::buildPublicLink('pirates/edit', $pirate),
			XenForo_Link::buildPublicLink('members', $user)
		);
		$fallback = XenForo_Link::buildPublicLink('pirates', $user);
		$redirect = $this->_redirector($checks, $fallback);


		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::RESOURCE_UPDATED,
			$redirect,
			new XenForo_Phrase('pirateProfile_the_pirate_has_been_saved_successfully')
		);
	}

	public static function getSessionActivityDetailsForList(array $activities)
	{
		foreach ($activities AS $key => $activity)
		{
			$action = $activity['controller_action'];

			switch ($action)
			{
				case 'Index':
					$userModel	= new XenForo_Model_User;
					$pirateUser = $userModel->getUserById($activity['params']['id']);
					$link		= XenForo_Link::buildPublicLink('pirates', $pirateUser);
					
					if (!isset($activity['params']['id']))
					{
						$viewing = new XenForo_Phrase('pirateProfile_viewing');
						$pirates = new XenForo_Phrase('pirateProfile_pirates');
						
						$doing = $viewing . ' ' . $pirates;
						
						return $doing;
					}
					
					if ($activity['params']['id'] != $activity['user_id'])
					{
						return array(
							$key => array(
								new XenForo_Phrase('pirateProfile_viewing'),
								new XenForo_Phrase('pirateProfile_xs_pirates',
									array('username' => $pirateUser['username'])), $link, false
							)
						);
					}
					
					return array(
						$key => array(new XenForo_Phrase('pirateProfile_viewing'),
						new XenForo_Phrase('pirateProfile_own_pirates'), $link, false)
					);
				case 'Card':
					$pirateModel = new PirateProfile_Model_Pirate;
					$pirate		 = $pirateModel->getPirateById($activity['params']['id']);
					$link		 = XenForo_Link::buildPublicLink('pirates/card', $pirate);
					
					return array(
						$key => array(
							new XenForo_Phrase('pirateProfile_viewing_pirate'), $pirate['name'], $link, $link
						)
					);
				case 'Add':
					return new XenForo_Phrase('pirateProfile_adding_pirate');
				case 'Edit':
					return new XenForo_Phrase('pirateProfile_editing_pirate');
				case 'Delete':
					return new XenForo_Phrase('pirateProfile_removing_pirate');
			}
		}
	}

	protected function _censorPirate($pirate)
	{
		if (!empty($pirate['name']))
		{
			$pirate['name']	 = XenForo_Helper_String::censorString($pirate['name']);
		}

		if (!empty($pirate['guild']))
		{
			$pirate['guild'] = XenForo_Helper_String::censorString($pirate['guild']);
		}

		if (!empty($pirate['extra']))
		{
			$pirate['extra'] = XenForo_Helper_String::censorString($pirate['extra']);
		}

		return $pirate;
	}

	protected function _censorPirates(array $pirates)
	{
		$return = array();
		foreach ($pirates as $pirate)
		{
			$return[] = $this->_censorPirate($pirate);
		}

		if (empty($return)) return false;

		return $return;
	}
	
	protected function _preparePirate($input)
	{
		$options = XenForo_Application::get('options');

		$pirate = array(
			'pirate_id'		=> $input['pirate_id'],
			'user_id'		=> $input['user_id'],
			'modified_date' => $input['modified_date'],
			'name'			=> $input['name'],
			'guild'			=> $input['guild'],
			'level'			=> $input['level'],
			'extra'			=> $input['extra'],
			'likes'          => $input['likes'],
			'likeUsers'     => unserialize($input['like_users']),
			'skills_set'	=> true,
			'max'			=> array(
				'weapon' => $options->pirateProfile_maxLevelWeapon,
				'skill'	 => $options->pirateProfile_maxLevelSkill
			),
			'weapons'		=> array(),
			'skills'		=> array());
			
		if (isset($input['like_id']))
		{
			$pirate += array(
				'like_id'         => $input['like_id'],
				'content_type'    => $input['content_type'],
				'content_id'      => $input['content_id'],
				'like_user_id'    => $input['like_user_id'],
				'like_date'       => $input['like_date'],
				'content_user_id' => $input['content_user_id']
			);
		}

		foreach ($input as $name => $level)
		{
			switch ($name)
			{
				case 'cannon':
				case 'sailing':
				case 'sword':
				case 'shooting':
				case 'doll':
				case 'dagger':
				case 'grenade':
				case 'staff':
					$pirate['weapons'][$name] = array(
						'name'	=> new XenForo_Phrase('pirateProfile_pirate_' . $name),
						'level' => $level
					);
				break;
				case 'potions':
				case 'fishing':
					$pirate['skills'][$name] = array(
						'name'	=> new XenForo_Phrase('pirateProfile_pirate_' . $name),
						'level' => $level
					);
				break;
			}
		}


		$skills_set = false;
		foreach ($pirate['weapons'] as $weapon)
		{
			if (!empty($weapon['level'])) $skills_set = true;
		}
		foreach ($pirate['skills'] as $skill)
		{
			if (!empty($skill['level'])) $skills_set = true;
		}

		if (!$skills_set)
		{
			$pirate['skills_set'] = false;
		}
		
		$pictures          = $this->_getPirateModel()
		                          ->getPicturesById($pirate['pirate_id']);
		$pirate['picture'] = $this->_preparePicture($pictures[0], $input['make_fit']);

		return $pirate;
	}
	
	protected function _preparePicture($picture, $fit = false)
	{
		if (empty($picture)) return false;

		$width  = 250;
		$height = 280;
		
		if ($fit)
		{
			$picture['width']  = $width;
			$picture['height'] = $height;
			
			return $picture;
		}

		switch ($picture['width'] >= $picture['height'])
		{
			default:
			case true:
				$ratio = ($picture['height'] / $height);
				$picture['width']  = intval(round($picture['width'] / $ratio));
				$picture['height'] = $height;
				$picture['margin'] = intval(round(-(($picture['width'] - $width) / 2)));
			break;
			case false:
				$ratio = ($picture['width'] /  $width);
				$picture['width']  = $width;
				$picture['height'] = intval(round($picture['height'] / $ratio));
			break;
		}

		return $picture;
	}

	protected function _assertCanManagePirate($pirate, $visitor)
	{
		$perms = $this->_getPirateModel()->getPermissions();
		
		if (($pirate['user_id'] == $visitor['user_id']) OR ($perms['manage']))
		{
			return true;
		}
		
		throw $this->getNoPermissionResponseException();
	}

	protected function _stripZeros(array $input)
	{
		return preg_replace("/^0$/is", null, $input);
	}

	protected function _redirector($checks, $fallback)
	{
		$referrer = $this->_request->getServer('HTTP_REFERER');
		
		$match = false;
		foreach ($checks as $check)
		{
			if (strpos($referrer, $check)) $match = true;
		}

		if ($match) return $fallback;
		
		return $this->getDynamicRedirect(
			XenForo_Link::buildPublicLink('pirates'), true
		);
	}

	protected function _getPirateModel()
	{
		return $this->getModelFromCache('PirateProfile_Model_Pirate');
	}

	protected function _getUserModel()
	{
		return $this->getModelFromCache('XenForo_Model_User');
	}
}
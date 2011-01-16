<?php

class PirateProfile_ControllerPublic_Pirate extends XenForo_ControllerPublic_Abstract
{
	public function actionIndex()
	{
		$userId = $this->_input->filterSingle('id', XenForo_Input::UINT);

		$user = $this->_getMemberOrError($userId);

		$this->_canonicalize($user);
		
		$pirateModel = $this->_getPirateModel();
		
		$perms = $pirateModel->getPermissions();
		if (!$perms['canView'])
		{
			throw $this->getNoPermissionResponseException();
		}

		$pirates = $pirateModel->getUserPirates($userId);
		$pirates = $this->_censorPirates($pirates);

		$viewParams = array(
			'user'	  => $user,
			'perms'   => $perms,
			'pirates' => $pirates
		);
		
		return $this->responseView(
			'PirateProfile_ViewPublic_Pirate_Index',
			'pirateProfile_view',
			$viewParams
		);
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
		return $this->responseView(
			'PirateProfile_ViewPublic_Pirate_List',
			'pirateProfile_list',
			$viewParams
		);
	}

	public function actionCard()
	{
		$pirateId = $this->_input->filterSingle('id', XenForo_Input::UINT);
		
		list($pirate, $user) = $this->_assertPirateValidAndViewable($pirateId);
		
		$this->_canonicalize($pirate, 'card');

		$pirate = $this->_preparePirate($this->_censorPirate($pirate));
		
		$pirate = $this->_assertCanLikePirate($pirate, $user);
		
		$pirateModel = $this->_getPirateModel();
		$pirate = $pirateModel->addPirateCommentsToPirate($pirate, array(
			'join' => PirateProfile_Model_Pirate::FETCH_COMMENT_USER
		));
		
		if (isset($pirate['comments']))
		{
			foreach ($pirate['comments'] as &$comment)
			{	
				$comment = $pirateModel->preparePirateComment($comment, $pirate, $user);
			}
		}
		
		$pirate['canComment'] = $this->_assertCanCommentOnPirate($pirate, $user);

		$viewParams = array(
			'user'	   => $user,
			'pirate'   => $pirate,
		);
		
		return $this->responseView(
			'PirateProfile_ViewPublic_Pirate_Card',
			'pirateProfile_pirate_card',
			$viewParams
		);
	}
	
	public function actionLike()
	{
		$pirateId = $this->_input->filterSingle('id', XenForo_Input::UINT);
		
		list($pirate, $user) = $this->_assertPirateValidAndViewable($pirateId);
		
		if (!$this->_getPirateModel()->canLikePirate($pirate, $user, $errorPhraseKey))
		{
			throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
		}
		
		$likeModel = $this->getModelFromCache('XenForo_Model_Like');

		$existingLike = $likeModel->getContentLikeByLikeUser(
			'pirate', $pirateId, XenForo_Visitor::getUserId()
		);
		
		if ($this->_request->isPost())
		{
			if ($existingLike)
			{
				$latestUsers = $likeModel->unlikeContent($existingLike);
			}
			else
			{
				$latestUsers = $likeModel->likeContent(
					'pirate', $pirateId, $pirate['user_id']
				);
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

				return $this->responseView(
					'PirateProfile_ViewPublic_Pirate_LikeConfirmed',
					'',
					$viewParams
				);
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

			return $this->responseView(
				'PirateProfile_ViewPublic_Pirate_Like',
				'pirateProfile_pirate_like',
				$viewParams
			);
		}
	}
	
	public function actionLikes()
	{
		$pirateId = $this->_input->filterSingle('id', XenForo_Input::UINT);
		
		list($pirate, $user) = $this->_assertPirateValidAndViewable($pirateId);
		
		$likes =  $this->getModelFromCache('XenForo_Model_Like')
		               ->getContentLikes('pirate', $pirateId);
		if (!$likes)
		{
			return $this->responseError(
				new XenForo_Phrase('pirateProfile_no_one_has_liked_this_pirate_yet')
			);
		}

		$viewParams = array(
			'pirate' => $pirate,
			'user'   => $user,
			'likes'  => $likes	
		);
		
		return $this->responseView(
			'PirateProfile_ViewPublic_Pirates',
			'pirateProfile_pirate_likes',
			$viewParams
		);
	}
	
	public function actionComment()
	{
		$pirateId = $this->_input->filterSingle('id', XenForo_Input::UINT);
		
		list($pirate, $user) = $this->_assertPirateValidAndViewable($pirateId);

		if ($this->_request->isPost())
		{
			$message = $this->_input->filterSingle('message', XenForo_Input::STRING);
			$visitor = XenForo_Visitor::getInstance();

			$dw = XenForo_DataWriter::create('PirateProfile_DataWriter_PirateComment');
			$dw->setExtraData(PirateProfile_DataWriter_PirateComment::DATA_PIRATE_USER, $user);
			$dw->setExtraData(PirateProfile_DataWriter_PirateComment::DATA_PIRATE, $pirate);
			$dw->bulkSet(array(
				'pirate_id' => $pirateId,
				'user_id'   => $visitor['user_id'],
				'username'  => $visitor['username'],
				'message'   => $message
			));
			$dw->save();

			if ($this->_noRedirect())
			{
				$pirateModel = $this->_getPirateModel();
				
				$comment = $pirateModel->getPirateCommentById(
					$dw->get('pirate_comment_id'),
					array('join' => PirateProfile_Model_Pirate::FETCH_COMMENT_USER)
				);

				$viewParams = array(
					'comment' => $pirateModel->preparePirateComment($comment, $pirate, $user),
					'pirate' => $pirate,
					'user' => $user
				);

				return $this->responseView(
					'PirateProfile_ViewPublic_Pirate_Comment',
					'',
					$viewParams
				);
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
				'user' => $user
			);

			return $this->responseView(
				'PirateProfile_ViewPublic_Pirate_Comment',
				'pirateProfile_pirate_comment_post',
				$viewParams
			);
		}
	}
	
	public function actionCommentDelete()
	{
		$pirateId = $this->_input->filterSingle('id', XenForo_Input::UINT);
		$commentId = $this->_input->filterSingle('comment', XenForo_Input::UINT);
		
		$pirateModel = $this->_getPirateModel();
		
		$comment = $pirateModel->getPirateCommentById($commentId);
		
		if (empty($comment))
		{
			throw $this->responseException($this->responseError(
				new XenForo_Phrase('requested_comment_not_found'), 404)
			);
		}
		
		list($pirate, $user) = $this->_assertPirateValidAndViewable($pirateId);

		if ($pirateId != $comment['pirate_id'])
		{
			return $this->responseNoPermission();
		}

		if (!$pirateModel->canDeletePirateComment($comment, $pirate, $user, $errorPhraseKey))
		{
			throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
		}

		if ($this->isConfirmedPost())
		{
			$dw = XenForo_DataWriter::create('PirateProfile_DataWriter_PirateComment');
			$dw->setExistingData($commentId);
			$dw->delete();

			return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::SUCCESS,
					XenForo_Link::buildPublicLink('pirates/card', $pirate)
			);
		}
		else
		{
			$viewParams = array(
				'comment' => $comment,
				'pirate' => $pirate,
				'user' => $user
			);

			return $this->responseView(
				'PirateProfile_ViewPublic_Pirate_CommentDelete',
				'pirateProfile_pirate_comment_delete',
				$viewParams
			);
		}
	}
	
	public function actionComments()
	{
		$pirateId = $this->_input->filterSingle('id', XenForo_Input::UINT);
		
		list($pirate, $user) = $this->_assertPirateValidAndViewable($pirateId);
		
		$beforeDate = $this->_input->filterSingle('before', XenForo_Input::UINT);

		$pirateModel = $this->_getPirateModel();

		$comments = $pirateModel->getPirateCommentsByPirate($pirateId, $beforeDate, array(
			'join'  => PirateProfile_Model_Pirate::FETCH_COMMENT_USER,
			'limit' => 50
		));

		if (!$comments)
		{
			return $this->responseMessage(new XenForo_Phrase('no_comments_to_display'));
		}

		foreach ($comments AS &$comment)
		{
			$comment = $pirateModel->preparePirateComment($comment, $pirate, $user);
		}

		$firstCommentShown = reset($comments);
		$lastCommentShown = end($comments);

		$viewParams = array(
			'comments' => $comments,
			'firstCommentShown' => $firstCommentShown,
			'lastCommentShown' => $lastCommentShown,
			'pirate' => $pirate,
			'user' => $user
		);

		return $this->responseView(
			'PirateProfile_ViewPublic_Pirate_Comments',
			'pirateProfile_pirate_comments',
			$viewParams
		);
	}

	public function actionAdd()
	{
		$this->_assertLoggedIn();
		
		$pirateModel = $this->_getPirateModel();
		
		$perms = $pirateModel->getPermissions();
		if (!$perms['canAdd']) throw $this->getNoPermissionResponseException();
		
		$attachmentParams = $pirateModel->getAttachmentParams(array());
		$attachmentConstraints = PirateProfile_AttachmentHandler_Pirate::getAttachmentConstraints();
		
		$viewParams = array(
			'attachmentParams'		=> $attachmentParams,
			'attachmentConstraints' => $attachmentConstraints
		);
		
		return $this->responseView(
			'PirateProfile_ViewPublic_Pirate_Add',
			'pirateProfile_add',
			$viewParams
		);
	}

	public function actionEdit()
	{
		$pirateId = $this->_input->filterSingle('id', XenForo_Input::UINT);
		
		list($pirate, $user) = $this->_assertPirateValidAndViewable($pirateId);
		
		$this->_canonicalize($pirate, 'edit');

		$pirateModel = $this->_getPirateModel();
		
		if (!$pirate['canEdit'])
		{
			throw $this->getNoPermissionResponseException();
		}
		
		$visitor = XenForo_Visitor::getInstance();
		$this->_assertCanManagePirate($pirate, $visitor);

		$user = $this->_getUserModel()->getUserById($pirate['user_id']);

        $pictures = $pirateModel->getPicturesById($pirate['pirate_id']);
		$attachmentParams = $pirateModel->getAttachmentParams(array(
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
			'PirateProfile_ViewPublic_Pirate_Edit',
			'pirateProfile_edit',
			$viewParams
		);
	}

	public function actionDelete()
	{
		$pirateId = $this->_input->filterSingle('id', XenForo_Input::UINT);
		
		list($pirate, $user) = $this->_assertPirateValidAndViewable($pirateId);

		if ($this->isConfirmedPost()) {
			$dw = XenForo_DataWriter::create('PirateProfile_DataWriter_Pirate');
			$dw->setExistingData($pirateId);
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
			'PirateProfile_ViewPublic_Pirate_Delete',
			'pirateProfile_delete',
			$viewParams
		);
	}

	public function actionNew()
	{
		$visitor = XenForo_Visitor::getInstance();
		$this->_assertLoggedIn($visitor['user_id']);
		
		$perms = $this->_getPirateModel()->getPermissions();
		if (!$perms['canAdd'])
		{
			throw $this->getNoPermissionResponseException();
		}

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
		$visitorId = XenForo_Visitor::getUserId();
		if (empty($visitorId)){
			throw $this->getNoPermissionResponseException();
		}
		
		$pirateModel = $this->_getPirateModel();
		
		$perms = $pirateModel->getPermissions();
		if (!$perms['canEdit'])
		{
			throw $this->getNoPermissionResponseException();
		}

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
				case 'Like':
					return new XenForo_Phrase('pirateProfile_liking_pirate');
				case 'Likes':
					return new XenForo_Phrase('pirateProfile_viewing_pirate_likes');
				case 'Add':
					return new XenForo_Phrase('pirateProfile_adding_pirate');
				case 'Edit':
					return new XenForo_Phrase('pirateProfile_editing_pirate');
				case 'Delete':
					return new XenForo_Phrase('pirateProfile_removing_pirate');
			}
		}
	}
	
	protected function _getMemberOrError($userId = false)
	{
		if (!$userId)
		{
			$user = XenForo_Visitor::getInstance();
			$userId = $user['user_id'];
			if (empty($userId))
			{
				throw $this->getNoPermissionResponseException();
			}
		}
		else
		{
			$user = $this->_getUserModel()->getUserById($userId);
			if (empty($user['user_id']))
			{
				throw $this->responseException(
					$this->responseError(
						new XenForo_Phrase('requested_member_not_found'), 404
					)
				);
			}
		}
		
		return $user;
	}
	
	protected function _assertLoggedIn($userId = null)
	{
		if (empty($userId))
		{
			$userId = XenForo_Visitor::getUserId();
		}
		
		if (empty($userId))
		{
			throw $this->getNoPermissionResponseException();
		}
		
		return true;
	}
	
	protected function _canonicalize($params, $action = false)
	{
		if ($action)
		{
			$this->canonicalizeRequestUrl(
				XenForo_Link::buildPublicLink('pirates/' . $action, $params)
			);
		}
		else
		{
			$this->canonicalizeRequestUrl(
				XenForo_Link::buildPublicLink('pirates', $params)
			);
		}
	}
	
	protected function _assertPirateValidAndViewable($pirateId)
	{
		$pirateModel = $this->_getPirateModel();
		
		$perms = $pirateModel->getPermissions();
		if (!$perms['canView'])
		{
			throw $this->getNoPermissionResponseException();
		}
		
		$pirate = $pirateModel->getPirateById(
			$pirateId, 
			array('likeUserId' => XenForo_Visitor::getUserId())
		);
		
		if (empty($pirate))
		{
			throw $this->responseException($this->responseError(
				new XenForo_Phrase('pirateProfile_requested_pirate_not_found'), 404)
			);
		}
		
		$pirate = array_merge_recursive($pirate, $perms);
		
		$user = $this->_getUserModel()->getUserById($pirate['user_id']);
		
		return array($pirate, $user);
	}
	
	protected function _assertCanLikePirate($pirate, $user)
	{
		$pirate['canLike'] = $this->_getPirateModel()->canLikePirate($pirate, $user);
		
		return $pirate;
	}
	
	protected function _assertCanCommentOnPirate($pirate, $user)
	{
		if ($pirate['canView'])
		{
			if (!empty($user['user_id']))
			{
				return true;
			}
		}
		
		return false;
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
	
	protected function _preparePirate($pirate)
	{
		$options = XenForo_Application::get('options');
		
		$pirate += array(
			'likeUsers'  => unserialize($pirate['like_users']),
			'skills_set' => true,
			'max'	     => array(
				'weapon' => $options->pirateProfile_maxLevelWeapon,
				'skill'	 => $options->pirateProfile_maxLevelSkill
			),
			'weapons'    => array(),
			'skills'     => array()
		);

		foreach ($pirate as $name => $level)
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
					unset($pirate[$name]);
				break;
				case 'potions':
				case 'fishing':
					$pirate['skills'][$name] = array(
						'name'	=> new XenForo_Phrase('pirateProfile_pirate_' . $name),
						'level' => $level
					);
					unset($pirate[$name]);
				break;
			}
		}

		$skillsSet = false;
		foreach ($pirate['weapons'] as $weapon)
		{
			if (!empty($weapon['level'])) $skillsSet = true;
		}
		foreach ($pirate['skills'] as $skill)
		{
			if (!empty($skill['level'])) $skillsSet = true;
		}

		if (!$skillsSet)
		{
			$pirate['skills_set'] = false;
		}
		
		$pictures          = $this->_getPirateModel()
		                          ->getPicturesById($pirate['pirate_id']);
		$pirate['picture'] = $this->_preparePicture($pictures[0], $pirate['make_fit']);

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
		
		if (($pirate['user_id'] == $visitor['user_id']) OR ($perms['canManage']))
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
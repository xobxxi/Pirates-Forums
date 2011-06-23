<?php

class PirateProfile_ControllerPublic_Pirate extends XenForo_ControllerPublic_Abstract
{
	public function actionIndex()
	{
		$userId = $this->_input->filterSingle('id', XenForo_Input::UINT);

		if ($userId)
		{
			return $this->responseReroute(__CLASS__, 'member');
		}

		$this->_assertCanView();

		$pirateModel = $this->_getPirateModel();

		$permissions = $pirateModel->getPermissions();

		$pirateName = $this->_input->filterSingle('pirateName', XenForo_Input::STRING);
		if ($pirateName !== '')
		{
			$pirate = $pirateModel->getPirateByName($pirateName);
			if ($pirate)
			{
				return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::SUCCESS,
					XenForo_Link::buildPublicLink('pirates/card', $pirate)
				);
			}
			else
			{
				$pirateNotFound = true;
			}
		}
		else
		{
			$pirateNotFound = false;
		}

		$page = max(1, $this->_input->filterSingle('page', XenForo_Input::UINT));
		$piratesPerPage = XenForo_Application::get('options')->membersPerPage;

		$defaultOrder     = 'name';
		$defaultDirection = 'asc';

		$order     = $this->_input->filterSingle('order', XenForo_Input::STRING, array('default' => $defaultOrder));
		$direction = $this->_input->filterSingle('direction', XenForo_Input::STRING, array('default' => $defaultDirection));

		$pirates = $pirateModel->getPirates(array(), array(
			'perPage' => $piratesPerPage,
			'page'    => $page,

			'order'     => $order,
			'direction' => $direction
		));

		$commentIds = array();
		foreach ($pirates as &$pirate)
		{
			$lastCommentIds = explode(',', $pirate['latest_comment_ids']);
			if (!empty($lastCommentIds))
			{
				$pirate['last_comment_id'] = end($lastCommentIds);

				$commentIds[$pirate['last_comment_id']] = $pirate['last_comment_id'];
			}
		}

		$comments = $pirateModel->getPirateCommentsByIds($commentIds);

		foreach ($pirates as &$pirate)
		{
			if (!empty($pirate['last_comment_id']))
			{
				$pirate['last_comment'] = $comments[$pirate['last_comment_id']];
			}
		}

		$pirateCount = $pirateModel->countPirates(array());
		$this->canonicalizePageNumber($page, $piratesPerPage, $pirateCount, 'pirates', $pirates);

		$pirates = $pirateModel->censorPirates($pirates);

		$recentlyUpdated = $pirateModel->getLatestPirates(array(), array('limit' => 4));
		$recentlyUpdated = $pirateModel->censorPirates($recentlyUpdated);

		$recentlyAdded = $pirateModel->getNewestPirates(array(), array('limit' => 4));
		$recentlyAdded = $pirateModel->censorPirates($recentlyAdded);

		$ids = array();
		foreach ($pirates as $pirate)
		{
			$ids[$pirate['user_id']] = $pirate['user_id'];
		}

		foreach ($recentlyUpdated as $pirate)
		{
			$ids[$pirate['user_id']] = $pirate['user_id'];
		}

		foreach ($recentlyAdded as $pirate)
		{
			$ids[$pirate['user_id']] = $pirate['user_id'];
		}

		$users = $this->_getUserModel()->getUsersByIds($ids);

		foreach ($pirates as $key => &$pirate)
		{
			if (!isset($users[$pirate['user_id']]))
			{
				unset($pirates[$key]);
				continue;
			}
			$pirate['user'] = $users[$pirate['user_id']];

			if (!empty($pirate['guild']) && strlen($pirate['guild']) > 12)
			{
				$pirate['guild'] = trim(substr($pirate['guild'], 0, 9)) . '...';
			}
		}

		foreach ($recentlyUpdated as &$pirate)
		{
			$pirate['user'] = $users[$pirate['user_id']];
		}

		foreach ($recentlyAdded as &$pirate)
		{
			$pirate['user'] = $users[$pirate['user_id']];
		}

		$orderParams = array();
		foreach (array('name', 'modified_date', 'level', 'guild', 'last_comment_date') AS $field)
		{
			$orderParams[$field]['order'] = ($field != $defaultOrder ? $field : false);
			if ($order == $field)
			{
				$orderParams[$field]['direction'] = ($direction == 'desc' ? 'asc' : 'desc');
			}
		}

		$viewParams = array(
			'pirates'            => $pirates,
			'permissions'        => $permissions,
			'page'               => $page,
			'piratesStartOffset' => ($page - 1) * $piratesPerPage + 1,
			'piratesEndOffset'   => ($page - 1) * $piratesPerPage + count($pirates),
			'piratesPerPage'     => $piratesPerPage,
			'totalPirates'       => $pirateCount,
			'pirateNotFound'     => $pirateNotFound,

			'recentlyUpdated' => $recentlyUpdated,
			'recentlyAdded'   => $recentlyAdded,

			'order'          => $order,
			'orderDirection' => $direction,
			'orderParams'    => $orderParams,

			'pageNavParams' => array(
				'order' => ($order != $defaultOrder ? $order : false),
				'direction' => ($direction != $defaultDirection ? $direction : false)
			)
		);

		return $this->responseView(
			'PirateProfile_ViewPublic_Pirate_List',
			'pirateProfile_list',
			$viewParams
		);
	}

	public function actionMember()
	{
		$userId = $this->_input->filterSingle('id', XenForo_Input::UINT);

		if (!$userId)
		{
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildPublicLink('pirates')
			);
		}

		$user = $this->getHelper('UserProfile')->getUserOrError($userId);

		$this->_canonicalize($user);

		$this->_assertCanView();

		$pirateModel = $this->_getPirateModel();

		$permissions = $pirateModel->getPermissions();

		$pirates = $pirateModel->getUserPirates($userId);
		$pirates = $pirateModel->censorPirates($pirates);

		$viewParams = array(
			'user'	        => $user,
			'permissions'   => $permissions,
			'pirates'       => $pirates
		);

		return $this->responseView(
			'PirateProfile_ViewPublic_Pirate_Index',
			'pirateProfile_view',
			$viewParams
		);
	}

	public function actionFind()
	{
		$this->_assertCanView();

		$q = $this->_input->filterSingle('q', XenForo_Input::STRING);

		if ($q !== '')
		{
			$pirateModel = $this->_getPirateModel();

			$pirates = $pirateModel->getPirates(
				array('name' => array($q , 'r')),
				array('limit' => 10)
			);

			$ids = array();
			foreach ($pirates as $key => $pirate)
			{
				$censored = $pirateModel->censorPirate($pirate);

				if ($pirate['name'] != $censored['name'])
				{
					unset($pirate[$key]);
					continue;
				}

				$ids[] = $pirate['user_id'];
			}

			$users = $this->_getUserModel()->getUsersByIds($ids);

			foreach ($pirates as $key => $pirate)
			{
				if (empty($users[$pirate['user_id']]))
				{
					unset($pirates[$key]);
				}
			}

			$pirates = $pirateModel->censorPirates($pirates);
		}
		else
		{
			$pirates = array();
		}

		$viewParams = array(
			'pirates' => $pirates
		);

		return $this->responseView(
			'PirateProfile_ViewPublic_Pirate_Find',
			'pirateProfile_pirate_autocomplete',
			$viewParams
		);
	}

	public function actionCard()
	{
		$pirateId = $this->_input->filterSingle('id', XenForo_Input::UINT);

		list($pirate, $user) = $this->_assertPirateValidAndViewable($pirateId);

		$this->_canonicalize($pirate, 'card');

		$pirateModel = $this->_getPirateModel();

		$pirate = $pirateModel->preparePirate($pirate, $user);

		$pirate = $pirateModel->addPirateCommentsToPirate($pirate, array(
			'join' => PirateProfile_Model_Pirate::FETCH_COMMENT_USER,
			'likeUserId' => XenForo_Visitor::getUserId()
		));

		if (isset($pirate['comments']))
		{
			foreach ($pirate['comments'] as &$comment)
			{
				$comment = $pirateModel->preparePirateComment($comment, $pirate, $user);
			}
		}

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

	public function actionReport()
	{
		$pirateId = $this->_input->filterSingle('id', XenForo_Input::UINT);

		list($pirate, $user) = $this->_assertPirateValidAndViewable($pirateId);

		if (!$this->_getPirateModel()->canReportPirate($pirate, $errorPhraseKey))
		{
			throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
		}

		if ($this->_request->isPost())
		{
			$reportMessage = $this->_input->filterSingle('message', XenForo_Input::STRING);
			if (!$reportMessage)
			{
				return $this->responseError(new XenForo_Phrase('pirateProfile_please_enter_reason_for_reporting_this_pirate'));
			}

			$this->getModelFromCache('XenForo_Model_Report')->reportContent('pirate', $pirate, $reportMessage);

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildPublicLink('pirates/card', $pirate),
				new XenForo_Phrase('pirateProfile_thank_you_for_reporting_this_pirate')
			);
		}
		else
		{
			$viewParams = array(
				'pirate' => $pirate,
				'user'   => $user
			);

			return $this->responseView(
				'PirateProfile_ViewPublic_Pirate_Report',
				'pirateProfile_pirate_report',
				$viewParams
			);
		}
	}

	public function actionLike()
	{
		$pirateId = $this->_input->filterSingle('id', XenForo_Input::UINT);

		list($pirate, $user) = $this->_assertPirateValidAndViewable($pirateId);

		if (!$this->_getPirateModel()->canLikePirate($pirate, $errorPhraseKey))
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

		if (!$this->_getPirateModel()->canCommentOnPirate($pirate, $errorPhraseKey))
		{
			throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
		}

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

	public function actionCommentEdit()
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

		if (!$pirateModel->canEditPirateComment($comment, $pirate, $user, $errorPhraseKey))
		{
			throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
		}

		if ($this->_request->isPost())
		{
			$inputMessage = $this->_input->filterSingle('message', XenForo_Input::STRING);

			$dw = XenForo_DataWriter::create('PirateProfile_DataWriter_PirateComment');
			$dw->setExistingData($commentId);
			$dw->set('message', $inputMessage);
			$dw->save();

			return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::SUCCESS,
					XenForo_Link::buildPublicLink('pirates/card', $pirate)
			);
		}
		else
		{
			$viewParams = array(
				'comment' => $comment,
				'pirate'  => $pirate,
				'user'    => $user
			);

			return $this->responseView(
				'PirateProfile_ViewPublic_Pirate_CommentEdit',
				'pirateProfile_pirate_comment_edit',
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

	public function actionCommentLike()
	{
		$commentId = $this->_input->filterSingle('comment', XenForo_Input::UINT);

		list($comment, $pirate, $user) = $this->_assertPirateCommentValidAndViewable($commentId);

		$pirateId = $this->_input->filterSingle('id', XenForo_Input::UINT);
		if ($pirateId != $comment['pirate_id'])
		{
			return $this->responseNoPermission();
		}

		if (!$this->_getPirateModel()->canLikePirateComment($comment, $errorPhraseKey))
		{
			throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
		}

		$likeModel = $this->getModelFromCache('XenForo_Model_Like');

		$existingLike = $likeModel->getContentLikeByLikeUser(
			'pirate_comment', $commentId, XenForo_Visitor::getUserId()
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
					'pirate_comment', $commentId, $comment['user_id']
				);
			}

			$liked = ($existingLike ? false : true);

			if ($this->_noRedirect() && $latestUsers !== false)
			{
				$comment['likeUsers'] = $latestUsers;
				$comment['likes'] += ($liked ? 1 : -1);
				$comment['like_date'] = ($liked ? XenForo_Application::$time : 0);

				$viewParams = array(
					'comment' => $comment,
					'pirate'  => $pirate,
					'user'    => $user,
					'liked'   => $liked,
				);

				return $this->responseView(
					'PirateProfile_ViewPublic_Pirate_CommentLikeConfirmed',
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
				'comment' => $comment,
				'pirate'  => $pirate,
				'user'    => $user,
				'like'    => $existingLike
			);

			return $this->responseView(
				'PirateProfile_ViewPublic_Pirate_LikeComment',
				'pirateProfile_comment_like',
				$viewParams
			);
		}
	}

	public function actionCommentLikes()
	{
		$commentId = $this->_input->filterSingle('comment', XenForo_Input::UINT);

		list($comment, $pirate, $user) = $this->_assertPirateCommentValidAndViewable($commentId);

		$likes = $this->getModelFromCache('XenForo_Model_Like')
		              ->getContentLikes('pirate_comment', $commentId);
		if (!$likes)
		{
			return $this->responseError(
				new XenForo_Phrase('pirateProfile_no_one_has_liked_this_comment_yet')
			);
		}

		$viewParams = array(
			'pirate'  => $pirate,
			'comment' => $comment,
			'user'    => $user,
			'likes'   => $likes
		);

		return $this->responseView(
			'PirateProfile_ViewPublic_Pirate_CommentLikes',
			'pirateProfile_comment_likes',
			$viewParams
		);
	}

	public function actionAdd()
	{
		$this->_assertCanAddPirate();

		$pirateModel = $this->_getPirateModel();
		$attachmentParams = $pirateModel->getAttachmentParams(array());

		$attachmentConstraints = $this->_getAttachmentModel()->getAttachmentHandler('pirate')->getAttachmentConstraints();

		$skills = $pirateModel::getSkills(false, true);
		foreach ($skills as &$name)
		{
			$name = array(
				'name' => $name,
			);
		}

		$ranks = $pirateModel::getRanks(false, true);
		foreach ($ranks as $type => &$children)
		{
			$children = array(
				'children'   => $children,
				'name'    => new XenForo_Phrase('pirateProfile_' . $type)
			);
		}

		$viewParams = array(
			'weapons'               => $pirateModel::getWeapons(false, true),
			'skills'                => $skills,
			'ranks'                 => $ranks,
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

		$this->_assertCanManagePirate($pirate);
		$this->_assertCanEditPirate();

		$pirateModel = $this->_getPirateModel();

		$weapons = $pirateModel::getWeapons(false, true);
		foreach ($weapons as $key => &$weapon)
		{
			$weapon['current'] = $pirate[$key];
		}

		$skills = $pirateModel::getSkills(false, true);
		foreach ($skills as $skill => &$name)
		{
			$name = array(
				'name'    => $name,
				'current' => $pirate[$skill]
			);
		}

		$ranks = $pirateModel::getRanks(false, true);
		foreach ($ranks as $type => &$children)
		{
			$children = array(
				'children' => $children,
				'name'     => new XenForo_Phrase('pirateProfile_' . $type),
				'current'  => $pirate[$type]
			);
		}

        $pictures = $pirateModel->getPicturesById($pirate['pirate_id']);
		$attachmentParams = $pirateModel->getAttachmentParams(array(
			'pirate_id' => $pirate['pirate_id']
		));

		$attachmentConstraints = $this->_getAttachmentModel()->getAttachmentHandler('pirate')->getAttachmentConstraints();

		$viewParams = array(
			'user'					=> $user,
			'pirate'				=> $pirate,
			'weapons'               => $weapons,
			'skills'                => $skills,
			'ranks'                 => $ranks,
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

		$this->_assertCanManagePirate($pirate);

		$permissions = $this->_getPirateModel()->getPermissions();
		if (!$permissions['delete'])
		{
			throw $this->getNoPermissionResponseException();
		}

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
		$this->_assertCanAddPirate();
		$this->_assertPostOnly();

		$pirateModel = $this->_getPirateModel();

		$filter = array(
			'name'     => XenForo_Input::STRING,
			'level'    => XenForo_Input::UINT,
			'guild'    => XenForo_Input::STRING,
			'extra'    => XenForo_Input::STRING,
			'make_fit' => XenForo_Input::UINT
		);


		$skills = array_merge(
			$pirateModel::getWeapons(true, true),
			$pirateModel::getSkills(true, true)
		);

		foreach ($skills as $skill)
		{
			if (!empty($skill))
			{
				$filter[$skill] = XenForo_Input::UINT;
			}
		}

		$ranks = $pirateModel::getRanks(true, true);
		foreach ($ranks as $type => $children)
		{
			$filter[$type] = XenForo_Input::STRING;
		}

		$input = $this->_input->filter($filter);

		$attachment = $this->_input->filter(array(
			'attachment_hash' => XenForo_Input::STRING)
		);

		$visitor = XenForo_Visitor::getInstance();

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
		$pirateId = $this->_input->filterSingle('id', XenForo_Input::UINT);

		list($pirate, $user) = $this->_assertPirateValidAndViewable($pirateId);

		$this->_assertCanManagePirate($pirate);
		$this->_assertCanEditPirate();

		$this->_assertPostOnly();

		$user = $this->_getUserModel()->getUserById($pirate['user_id']);

		$filter = array(
			'name'     => XenForo_Input::STRING,
			'level'    => XenForo_Input::UINT,
			'guild'    => XenForo_Input::STRING,
			'extra'    => XenForo_Input::STRING,
			'make_fit' => XenForo_Input::UINT
		);

		$skills = array_merge(
			PirateProfile_Model_Pirate::getWeapons(true, true),
			PirateProfile_Model_Pirate::getSkills(true, true)
		);

		foreach ($skills as $skill)
		{
			if (!empty($skill))
			{
				$filter[$skill] = XenForo_Input::UINT;
			}
		}

		$ranks = PirateProfile_Model_Pirate::getRanks(true, true);
		foreach ($ranks as $type => $children)
		{
			$filter[$type] = XenForo_Input::STRING;
		}

		$input = $this->_input->filter($filter);

		$attachment = $this->_input->filter(array(
			'attachment_hash' => XenForo_Input::STRING)
		);

		$dw = XenForo_DataWriter::create('PirateProfile_DataWriter_Pirate');
		$dw->setExistingData($pirateId);

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
				case 'Member':
					if (!isset($activity['params']['id']))
					{
						$viewing = new XenForo_Phrase('pirateProfile_viewing');
						$pirates = new XenForo_Phrase('pirateProfile_pirates');

						$doing = $viewing . ' ' . $pirates;

						return $doing;
					}

					$userModel	= XenForo_Model::create('XenForo_Model_User');
					$pirateUser = $userModel->getUserById($activity['params']['id']);
					$link		= XenForo_Link::buildPublicLink('pirates', $pirateUser);

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
					if (!isset($activity['params']['id']))
					{
						$viewing = new XenForo_Phrase('pirateProfile_viewing');
						$pirates = new XenForo_Phrase('pirateProfile_pirates');

						$doing = $viewing . ' ' . $pirates;

						return $doing;
					}

					$pirateModel = XenForo_Model::create('PirateProfile_Model_Pirate');
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
				case 'Index':
				default:
					return new XenForo_Phrase('pirateProfile_viewing_pirates');
			}
		}
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

		$permissions = $pirateModel->getPermissions();
		if (!$permissions['view'])
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

		$user = $this->_getUserModel()->getUserById($pirate['user_id']);

		if (empty($user))
		{
			throw $this->responseException($this->responseError(
				new XenForo_Phrase('requested_member_not_found'), 404)
			);
		}

		return array($pirate, $user);
	}

	protected function _assertPirateCommentValidAndViewable($commentId)
	{
		$pirateModel = $this->_getPirateModel();

		$permissions = $pirateModel->getPermissions();
		if (!$permissions['view'])
		{
			throw $this->getNoPermissionResponseException();
		}

		$comment = $pirateModel->getPirateCommentById($commentId);

		if (empty($comment))
		{
			throw $this->responseException($this->responseError(
				new XenForo_Phrase('requested_comment_not_found'), 404)
			);
		}

		$pirate = $pirateModel->getPirateById(
			$comment['pirate_id'],
			array('likeUserId' => XenForo_Visitor::getUserId())
		);

		if (empty($pirate))
		{
			throw $this->responseException($this->responseError(
				new XenForo_Phrase('pirateProfile_requested_pirate_not_found'), 404)
			);
		}

		$user = $this->_getUserModel()->getUserById($pirate['user_id']);

		if (empty($user))
		{
			throw $this->responseException($this->responseError(
				new XenForo_Phrase('requested_member_not_found'), 404)
			);
		}

		return array($comment, $pirate, $user);
	}

	protected function _assertCanManagePirate($pirate, &$errorPhraseKey = '')
	{
		$visitor = XenForo_Visitor::getInstance();

		if ($visitor['user_id'])
		{
			$permissions = $this->_getPirateModel()->getPermissions();

			if ($permissions['manage'])
			{
				return true;
			}

			if ($pirate['user_id'] == XenForo_Visitor::getUserId())
			{
				return true;
			}
		}

		throw $this->getNoPermissionResponseException($errorPhraseKey);
	}

	protected function _assertCanAddPirate(&$errorPhraseKey = '')
	{
		$permissions = $this->_getPirateModel()->getPermissions();
		if ($permissions['add'] AND XenForo_Visitor::getUserId())
		{
			return true;
		}

		throw $this->getNoPermissionResponseException($errorPhraseKey);
	}

	protected function _assertCanEditPirate(&$errorPhraseKey = '')
	{
		$permissions = $this->_getPirateModel()->getPermissions();
		if ($permissions['edit'])
		{
			return true;
		}

		throw $this->getNoPermissionResponseException($errorPhraseKey);
	}

	protected function _assertCanView(&$errorPhraseKey = '')
	{
		$permissions = $this->_getPirateModel()->getPermissions();
		if ($permissions['view'])
		{
			return true;
		}

		throw $this->getNoPermissionResponseException($errorPhraseKey);
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
			if (strpos($referrer, $check))
			{
				$match = true;
			}
		}

		if ($match)
		{
			return $fallback;
		}

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

	protected function _getAttachmentModel()
	{
		return $this->getModelFromCache('XenForo_Model_Attachment');
	}
}
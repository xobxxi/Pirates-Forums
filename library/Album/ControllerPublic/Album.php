<?php
/* 1.0.0
[*]
// likes/comments for albums
// reports for comments
// change popstate logic
*/

class Album_ControllerPublic_Album extends XenForo_ControllerPublic_Abstract
{
	public function actionIndex()
	{
		$albumModel = $this->_getAlbumModel();

		$permissions = $albumModel->getPermissions();

		if (!$permissions['view'])
		{
			throw $this->getNoPermissionResponseException();
		}

		$userId = $this->_input->filterSingle('id', XenForo_Input::UINT);

		if (!$userId)
		{
			$userId = XenForo_Visitor::getUserId();
		}

		$user = $this->_getUserModel()->getUserById($userId,
			array('followingUserId' => XenForo_Visitor::getUserId())
		);

		if (!$user)
		{
			throw $this->responseException($this->responseError(
				new XenForo_Phrase('requested_member_not_found'), 404)
			);
		}

		$this->canonicalizeRequestUrl(
			XenForo_Link::buildPublicLink('albums', $user)
		);

		$albums = $albumModel->getUserAlbumsByUserId($userId);

		$filtered = false;
		foreach ($albums as $key => $album)
		{
			if (!$albumModel->canViewAlbum($album, $user, $errorPhraseKey))
			{
				unset($albums[$key]);
				$filtered = true;
			}
		}

		$albums = $albumModel->prepareAlbums($albums);

		$permissions = $albumModel->getPermissions();

		$viewParams = array(
			'user'	      => $user,
			'albums'      => $albums,
			'permissions' => $permissions,
			'filtered'    => $filtered
		);

		return $this->responseView(
			'Album_ViewPublic_Album_Index',
			'album_user',
			$viewParams
		);
	}

	public function actionView()
	{
		$albumId = $this->_input->filterSingle('id', XenForo_Input::UINT);

		list($album, $user) = $this->_assertAlbumValidAndViewable($albumId);

		$this->canonicalizeRequestUrl(
			XenForo_Link::buildPublicLink('albums/view', $album)
		);

		$viewParams = array(
			'user'	=> $user,
			'album' => $album
		);

		return $this->responseView(
			'Album_ViewPublic_Album_View',
			'album_view',
			$viewParams
		);
	}

	public function actionViewPhoto()
	{
		$photoId = $this->_input->filterSingle('id', XenForo_Input::UINT);

		list($photo, $album, $user) = $this->_assertPhotoValidAndViewable($photoId);

        $albumModel = $this->_getAlbumModel();

		$photo = $albumModel->preparePhoto($photo, true, $album);

		$photo = $albumModel->addAlbumPhotoCommentsToPhoto($photo, array(
			'join'       => Album_Model_Album::FETCH_PHOTO_COMMENT_USER,
			'likeUserId' => XenForo_Visitor::getUserId()
		));

		if (isset($photo['comments']))
		{
			foreach ($photo['comments'] as &$comment)
			{
				$comment = $albumModel->prepareAlbumComment($comment, $album, $user);
			}
		}

		$viewParams = array(
			'photo' => $photo,
			'user'	=> $user,
			'album' => $album
		);

		return $this->responseView(
			'Album_ViewPublic_Album_Photo',
			'album_photo',
			$viewParams
		);
	}

	public function actionReportPhoto()
	{
		$photoId = $this->_input->filterSingle('id', XenForo_Input::UINT);

		list($photo, $album, $user) = $this->_assertPhotoValidAndViewable($photoId);

		if (!$this->_getAlbumModel()->canReportAlbum($album, $errorPhraseKey))
		{
			throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
		}

		if ($this->_request->isPost())
		{
			$reportMessage = $this->_input->filterSingle('message', XenForo_Input::STRING);
			if (!$reportMessage)
			{
				return $this->responseError(new XenForo_Phrase('album_please_enter_reason_for_reporting_this_photo'));
			}

			$this->getModelFromCache('XenForo_Model_Report')->reportContent('album_photo', $photo, $reportMessage);

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildPublicLink('albums/view-photo', $photo),
				new XenForo_Phrase('album_thank_you_for_reporting_this_photo')
			);
		}
		else
		{
			$viewParams = array(
				'photo' => $photo,
				'album' => $album,
				'user'  => $user
			);

			return $this->responseView(
				'Album_ViewPublic_Album_Report',
				'album_photo_report',
				$viewParams
			);
		}
	}

	public function actionLikePhoto()
	{
		$photoId = $this->_input->filterSingle('id', XenForo_Input::UINT);

		list($photo, $album, $user) = $this->_assertPhotoValidAndViewable($photoId);

		if (!$this->_getAlbumModel()->canLikeAlbum($album, $errorPhraseKey))
		{
			throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
		}

		$likeModel = $this->getModelFromCache('XenForo_Model_Like');

		$existingLike = $likeModel->getContentLikeByLikeUser(
			'album_photo', $photoId, XenForo_Visitor::getUserId()
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
					'album_photo', $photoId, $album['user_id']
				);
			}

			$liked = ($existingLike ? false : true);

			if ($this->_noRedirect() && $latestUsers !== false)
			{
				$photo['likeUsers'] = $latestUsers;
				$photo['likes'] += ($liked ? 1 : -1);
				$photo['like_date'] = ($liked ? XenForo_Application::$time : 0);

				$viewParams = array(
					'photo' => $photo,
					'album' => $album,
					'user'  => $user,
					'liked' => $liked
				);

				return $this->responseView(
					'Album_ViewPublic_Album_LikeConfirmedPhoto',
					'',
					$viewParams
				);
			}
			else
			{
				return $this->responseRedirect(
						XenForo_ControllerResponse_Redirect::SUCCESS,
						XenForo_Link::buildPublicLink('albums/view-photo', $photo)
				);
			}
		}
		else
		{
			$viewParams = array(
				'photo' => $photo,
				'album' => $album,
				'user'  => $user,
				'like'  => $existingLike
			);

			return $this->responseView(
				'Album_ViewPublic_Album_LikePhoto',
				'album_photo_like',
				$viewParams
			);
		}
	}

	public function actionLikesPhoto()
	{
		$photoId = $this->_input->filterSingle('id', XenForo_Input::UINT);

		list($photo, $album, $user) = $this->_assertPhotoValidAndViewable($photoId);

		$likes =  $this->getModelFromCache('XenForo_Model_Like')
		               ->getContentLikes('album_photo', $photoId);
		if (!$likes)
		{
			return $this->responseError(
				new XenForo_Phrase('album_no_one_has_liked_this_photo_yet')
			);
		}

		$viewParams = array(
			'photo' => $photo,
			'album' => $album,
			'user'  => $user,
			'likes' => $likes
		);

		return $this->responseView(
			'Album_ViewPublic_LikesPhoto',
			'album_photo_likes',
			$viewParams
		);
	}

    public function actionCommentPhoto()
	{
		$photoId = $this->_input->filterSingle('id', XenForo_Input::UINT);

		list($photo, $album, $user) = $this->_assertPhotoValidAndViewable($photoId);

        $albumModel = $this->_getAlbumModel();

		if (!$albumModel->canCommentOnAlbum($album, $errorPhraseKey))
		{
			throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
		}

		if ($this->_request->isPost())
		{
			$message = $this->_input->filterSingle('message', XenForo_Input::STRING);
			$visitor = XenForo_Visitor::getInstance();

			$dw = XenForo_DataWriter::create('Album_DataWriter_AlbumPhotoComment');
			$dw->setExtraData(Album_DataWriter_AlbumPhotoComment::DATA_ALBUM_PHOTO_USER, $user);
			$dw->setExtraData(Album_DataWriter_AlbumPhotoComment::DATA_ALBUM_PHOTO, $album);
			$dw->bulkSet(array(
				'photo_id' => $photoId,
				'user_id'  => $visitor['user_id'],
				'username' => $visitor['username'],
				'message'  => $message
			));
			$dw->save();

			if ($this->_noRedirect())
			{
				$comment = $albumModel->getAlbumPhotoCommentById(
					$dw->get('album_photo_comment_id'),
					array('join' => Album_Model_Album::FETCH_PHOTO_COMMENT_USER)
				);

				$viewParams = array(
					'comment' => $albumModel->prepareAlbumComment($comment, $album, $user),
					'photo'   => $photo,
					'user'    => $user
				);

				return $this->responseView(
					'Album_ViewPublic_Album_CommentPhoto',
					'',
					$viewParams
				);
			}
			else
			{
				return $this->responseRedirect(
						XenForo_ControllerResponse_Redirect::SUCCESS,
						XenForo_Link::buildPublicLink('albums/view-photo', $photo)
				);
			}
		}
		else
		{
			$viewParams = array(
				'photo' => $photo,
				'album' => $album,
				'user'  => $user
			);

			return $this->responseView(
				'Album_ViewPublic_Album_CommentPhoto',
				'album_photo_comment_post',
				$viewParams
			);
		}
	}

	public function actionCommentEditPhoto()
	{
		$photoId   = $this->_input->filterSingle('id', XenForo_Input::UINT);
		$commentId = $this->_input->filterSingle('comment', XenForo_Input::UINT);

		$albumModel = $this->_getAlbumModel();

		$comment = $albumModel->getAlbumPhotoCommentById($commentId);

		if (empty($comment))
		{
			throw $this->responseException($this->responseError(
				new XenForo_Phrase('requested_comment_not_found'), 404)
			);
		}

		list($photo, $album, $user) = $this->_assertPhotoValidAndViewable($photoId);


		if ($photoId != $comment['photo_id'])
		{
			return $this->responseNoPermission();
		}

		if (!$albumModel->canEditAlbumComment($comment, $album, $user, $errorPhraseKey))
		{
			throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
		}

		if ($this->_request->isPost())
		{
			$inputMessage = $this->_input->filterSingle('message', XenForo_Input::STRING);

			$dw = XenForo_DataWriter::create('Album_DataWriter_AlbumPhotoComment');
			$dw->setExistingData($commentId);
			$dw->set('message', $inputMessage);
			$dw->save();

			return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::SUCCESS,
					XenForo_Link::buildPublicLink('albums/view-photo', $photo)
			);
		}
		else
		{
			$viewParams = array(
				'comment' => $comment,
				'photo'   => $photo,
				'album'   => $album,
				'user'    => $user
			);

			return $this->responseView(
				'Album_ViewPublic_Album_CommentEditPhoto',
				'album_photo_comment_edit',
				$viewParams
			);
		}
	}

	public function actionCommentDeletePhoto()
	{
		$photoId = $this->_input->filterSingle('id', XenForo_Input::UINT);
		$commentId = $this->_input->filterSingle('comment', XenForo_Input::UINT);

		$albumModel = $this->_getAlbumModel();

		$comment = $albumModel->getAlbumPhotoCommentById($commentId);

		if (empty($comment))
		{
			throw $this->responseException($this->responseError(
				new XenForo_Phrase('requested_comment_not_found'), 404)
			);
		}

		list($photo, $album, $user) = $this->_assertPhotoValidAndViewable($photoId);

		if ($photoId != $comment['photo_id'])
		{
			return $this->responseNoPermission();
		}

		if (!$albumModel->canDeleteAlbumComment($comment, $photo, $user, $errorPhraseKey))
		{
			throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
		}

		if ($this->isConfirmedPost())
		{
			$dw = XenForo_DataWriter::create('Album_DataWriter_AlbumPhotoComment');
			$dw->setExistingData($commentId);
			$dw->delete();

			return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::SUCCESS,
					XenForo_Link::buildPublicLink('albums/view-photo', $photo)
			);
		}
		else
		{
			$viewParams = array(
				'comment' => $comment,
				'photo'   => $photo,
				'album'   => $album,
				'user'    => $user
			);

			return $this->responseView(
				'Album_ViewPublic_Album_CommentDeletePhoto',
				'album_photo_comment_delete',
				$viewParams
			);
		}
	}

	public function actionCommentsPhoto()
	{
		$photoId = $this->_input->filterSingle('id', XenForo_Input::UINT);

		list($photo, $album, $user) = $this->_assertPhotoValidAndViewable($photoId);

		$beforeDate = $this->_input->filterSingle('before', XenForo_Input::UINT);

		$albumModel = $this->_getAlbumModel();

		$comments = $albumModel->getAlbumPhotoCommentsByPhoto($photoId, $beforeDate, array(
			'join'  => Album_Model_Album::FETCH_PHOTO_COMMENT_USER,
			'limit' => 50,
			'likeUserId' => XenForo_Visitor::getUserId()
		));

		if (!$comments)
		{
			return $this->responseMessage(new XenForo_Phrase('no_comments_to_display'));
		}

		foreach ($comments AS &$comment)
		{
			$comment = $albumModel->prepareAlbumComment($comment, $album, $user);
		}

		$firstCommentShown = reset($comments);
		$lastCommentShown = end($comments);

		$viewParams = array(
			'comments'          => $comments,
			'firstCommentShown' => $firstCommentShown,
			'lastCommentShown'  => $lastCommentShown,
			'photo'             => $photo,
			'album'             => $album,
			'user'              => $user
		);

		return $this->responseView(
			'Album_ViewPublic_Album_CommentsPhoto',
			'album_photo_comments',
			$viewParams
		);
	}

	public function actionCommentLikePhoto()
	{
		$commentId = $this->_input->filterSingle('comment', XenForo_Input::UINT);

		list($comment, $photo, $album, $user) = $this->_assertAlbumPhotoCommentValidAndViewable($commentId);

		$photoId = $this->_input->filterSingle('id', XenForo_Input::UINT);
		if ($photoId != $comment['photo_id'])
		{
			return $this->responseNoPermission();
		}

		if (!$this->_getAlbumModel()->canLikeAlbumComment($comment, $errorPhraseKey))
		{
			throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
		}

		$likeModel = $this->getModelFromCache('XenForo_Model_Like');

		$existingLike = $likeModel->getContentLikeByLikeUser(
			'album_photo_comment', $commentId, XenForo_Visitor::getUserId()
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
					'album_photo_comment', $commentId, $comment['user_id']
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
					'photo'   => $photo,
					'album'   => $album,
					'user'    => $user,
					'liked'   => $liked,
				);

				return $this->responseView(
					'Album_ViewPublic_Album_CommentLikeConfirmedPhoto',
					'',
					$viewParams
				);
			}
			else
			{
				return $this->responseRedirect(
						XenForo_ControllerResponse_Redirect::SUCCESS,
						XenForo_Link::buildPublicLink('albums/view-photo', $photo)
				);
			}
		}
		else
		{
			$viewParams = array(
				'comment' => $comment,
				'photo'   => $photo,
				'album'   => $album,
				'user'    => $user,
				'like'    => $existingLike
			);

			return $this->responseView(
				'Album_ViewPublic_Album_LikeCommentPhoto',
				'album_photo_comment_like',
				$viewParams
			);
		}
	}

	public function actionCommentLikesPhoto()
	{
		$commentId = $this->_input->filterSingle('comment', XenForo_Input::UINT);

		list($comment, $photo, $album, $user) = $this->_assertAlbumPhotoCommentValidAndViewable($commentId);

		$likes = $this->getModelFromCache('XenForo_Model_Like')
		              ->getContentLikes('album_photo_comment', $commentId);
		if (!$likes)
		{
			return $this->responseError(
				new XenForo_Phrase('album_no_one_has_liked_this_comment_yet')
			);
		}

		$viewParams = array(
			'photo'   => $photo,
			'comment' => $comment,
			'album'   => $album,
			'user'    => $user,
			'likes'   => $likes
		);

		return $this->responseView(
			'Album_ViewPublic_Album_CommentLikesPhoto',
			'album_photo_comment_likes',
			$viewParams
		);
	}

	public function actionSetCover()
	{
		$photoId = $this->_input->filterSingle('id', XenForo_Input::UINT);

		list($photo, $album, $user) = $this->_assertPhotoValidAndViewable($photoId);

		$this->_assertCanManage($album);

		if ($this->isConfirmedPost())
		{
			$dw = XenForo_DataWriter::create('Album_DataWriter_Album');
			$dw->setExistingData($album);
			$dw->set('cover_photo_id', $photo['photo_id']);
			$dw->save();

			if ($this->_noRedirect())
			{
				list($photo, $album, $user) = $this->_assertPhotoValidAndViewable($photoId);

				$viewParams = array(
					'photo' => $photo,
					'user'	=> $user,
					'album' => $album
				);

				return $this->responseView('Album_ViewPublic_Album_SetCover', '', $viewParams);
			}
			else
			{
				return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::RESOURCE_UPDATED,
					XenForo_Link::buildPublicLink('albums/view-photo', $photo),
					new XenForo_Phrase('album_the_cover_photo_has_been_changed')
				);
			}
		}

		$viewParams = array(
			'photo' => $photo,
			'album' => $album,
			'user'	=> $user
		);
		return $this->responseView(
			'Album_ViewPublic_Album_SetCover',
			'album_set_cover',
			$viewParams
		);
	}

	public function actionManagePhoto()
	{
		$photoId = $this->_input->filterSingle('id', XenForo_Input::UINT);

		list($photo, $album, $user) = $this->_assertPhotoValidAndViewable($photoId);

		$this->_assertCanManage($album);

		if ($this->_request->isPost())
		{
			$input = $this->_input->filter(array(
				'description' => XenForo_Input::STRING
			));

			$dw = XenForo_DataWriter::create('Album_DataWriter_AlbumPhoto');
			$dw->setExistingData($photoId);
			$dw->set('description', $input['description']);
			$dw->save();

			if ($this->_noRedirect())
			{
				list($photo, $album, $user) = $this->_assertPhotoValidAndViewable($photoId);

				$viewParams = array(
					'photo' => $photo,
					'user'	=> $user,
					'album' => $album
				);

				return $this->responseView('Album_ViewPublic_Album_ManagePhoto', '', $viewParams);
			}
			else
			{
				return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::RESOURCE_UPDATED,
					XenForo_Link::buildPublicLink('albums/view-photo', $photo),
					new XenForo_Phrase('album_the_photo_has_been_saved_successfully')
				);
			}
		}

		$viewParams = array(
			'photo' => $photo,
			'user'	=> $user,
			'album' => $album
		);

		return $this->responseView(
			'Album_ViewPublic_Album_ManagePhoto',
			'album_photo_manage',
			$viewParams
		);
	}

	public function actionValidateField()
	{
		$this->_assertPostOnly();

		return $this->_validateField('Album_DataWriter_AlbumPhoto');
	}

	public function actionDeletePhoto()
	{
		$photoId = $this->_input->filterSingle('id', XenForo_Input::UINT);

		list($photo, $album, $user) = $this->_assertPhotoValidAndViewable($photoId);

		$this->_assertCanManage($album);

		if ($this->isConfirmedPost())
		{
			$dw = XenForo_DataWriter::create('Album_DataWriter_AlbumPhoto');
			$dw->setExistingData($photoId);
			$dw->delete();

			return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::SUCCESS,
					XenForo_Link::buildPublicLink('albums/view', $album)
			);
		}

		$viewParams = array(
			'photo' => $photo,
			'album' => $album,
			'user'	=> $user
		);

		return $this->responseView(
			'Album_ViewPublic_Album_DeletePhoto',
			'album_photo_delete',
			$viewParams
		);
	}

	public function actionAdd()
	{
		$albumModel = $this->_getAlbumModel();

		$permissions = $albumModel->getPermissions();

		if (!$permissions['upload'])
		{
			return $this->responseNoPermission();
		}

		$attachmentParams	   = $albumModel->getAttachmentParams(array());
		$attachmentConstraints = $this->_getAttachmentModel()->getAttachmentHandler('album')->getAttachmentConstraints();

		$viewParams = array(
			'user'					=> XenForo_Visitor::getInstance(),
			'attachmentParams'		=> $attachmentParams,
			'attachmentConstraints' => $attachmentConstraints
		);

		return $this->responseView(
			'Album_ViewPublic_Album_Add',
			'album_add',
			$viewParams
		);
	}

	public function actionManage()
	{
		$albumId = $this->_input->filterSingle('id', XenForo_Input::UINT);

		list($album, $user) = $this->_assertAlbumValidAndViewable($albumId);

		$this->_assertCanManage($album);

		$attachmentParams	   = $this->_getAlbumModel()->getAttachmentParams(array('album_id' => $album['album_id']));
		$attachmentConstraints = $this->_getAttachmentModel()->getAttachmentHandler('album')->getAttachmentConstraints();

		$viewParams = array(
			'user'					=> $user,
			'album'					=> $album,
			'attachmentParams'		=> $attachmentParams,
			'attachmentConstraints' => $attachmentConstraints
		);

		return $this->responseView(
			'Album_ViewPublic_Album_Manage',
			'album_manage',
			$viewParams
		);
	}

	public function actionDelete()
	{
		$albumId = $this->_input->filterSingle('id', XenForo_Input::UINT);

		list($album, $user) = $this->_assertAlbumValidAndViewable($albumId);

		$this->_assertCanManage($album);

		if ($this->isConfirmedPost())
		{
			$dw = XenForo_DataWriter::create('Album_DataWriter_Album');
			$dw->setExistingData($albumId);
			$dw->delete();

			return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::SUCCESS,
					XenForo_Link::buildPublicLink('albums', $user)
			);
		}

		$viewParams = array(
			'album' => $album,
			'user'	=> $user
		);

		return $this->responseView(
			'Album_ViewPublic_Album_Delete',
			'album_delete',
			$viewParams
		);
	}

	public function actionCreate()
	{
		$this->_assertPostOnly();

		$permissions = $this->_getAlbumModel()->getPermissions();
		if (!$permissions['upload'])
		{
			return $this->responseNoPermission();
		}

		$input = $this->_input->filter(array(
			'name' => XenForo_Input::STRING,
			'allow_view' => XenForo_Input::STRING
		));

		$attachment = $this->_input->filter(array(
			'attachment_hash' => XenForo_Input::STRING)
		);

		$dw = XenForo_DataWriter::create('Album_DataWriter_Album');
		$dw->set('user_id', XenForo_Visitor::getUserId());
		$dw->set('name', $input['name']);
		$dw->set('date', XenForo_Application::$time);
		$dw->set('allow_view', $input['allow_view']);
		$dw->setExtraData('attachment_hash', $attachment['attachment_hash']);
		$dw->preSave();
		$dw->save();

		$album = $dw->getMergedData();

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::RESOURCE_CREATED,
			XenForo_Link::buildPublicLink('albums/view', $album),
			new XenForo_Phrase('album_the_album_has_been_saved_successfully')
		);
	}

	public function actionSave()
	{
		$this->_assertPostOnly();

		$albumId = $this->_input->filterSingle('id', XenForo_Input::UINT);

		list($album, $user) = $this->_assertAlbumValidAndViewable($albumId);

		$this->_assertCanManage($album);

		$input = $this->_input->filter(array(
			'name'       => XenForo_Input::STRING,
			'allow_view' => XenForo_Input::STRING
		));

		$attachment = $this->_input->filter(array(
			'attachment_hash' => XenForo_Input::STRING)
		);

		$dw = XenForo_DataWriter::create('Album_DataWriter_Album');
		$dw->setExistingData($albumId);
		$dw->set('name', $input['name']);
		$dw->set('date', XenForo_Application::$time);
		$dw->set('allow_view', $input['allow_view']);
		$dw->setExtraData('attachment_hash', $attachment['attachment_hash']);
		$dw->preSave();
		$dw->save();

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::RESOURCE_UPDATED,
			XenForo_Link::buildPublicLink('albums/view', $album),
			new XenForo_Phrase('album_the_album_has_been_saved_successfully')
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
					if (!isset($activity['params']['id']))
					{
						return new XenForo_Phrase('album_viewing_albums');
					}

					$userModel = XenForo_Model::create('XenForo_Model_User');

					$user = $userModel->getUserById($activity['params']['id']);
					$link = XenForo_Link::buildPublicLink('albums', $user);

					if ($activity['params']['id'] != $activity['user_id'])
					{
						return array(
							$key => array(
								new XenForo_Phrase('album_viewing'),
								new XenForo_Phrase('album_xs_albums',
									array('username' => $user['username'])
								),
								$link,
								false
							)
						);
					}

					return array(
						$key => array(
							new XenForo_Phrase('album_viewing'),
							new XenForo_Phrase('album_own_albums'),
							$link,
							false
						)
					);
				case 'View':
					if (!isset($activity['params']['id']))
					{
						return new XenForo_Phrase('album_viewing_albums');
					}

					$albumModel = XenForo_Model::create('Album_Model_Album');

					$album = $albumModel->getAlbumById($activity['params']['id']);
					if (!$albumModel->canViewAlbum($album))
					{
						return new XenForo_Phrase('album_viewing_albums');
					}
					$album = $albumModel->prepareAlbum($album);
					$link  = XenForo_Link::buildPublicLink('albums/view', $album);

					return array(
						$key => array(
							new XenForo_Phrase('album_viewing_album'),
							new XenForo_Phrase('album_x',
								array('album' => $album['name'])
							),
							$link,
							false
						)
					);
				case 'ViewPhoto':
					if (!isset($activity['params']['id']))
					{
						return new XenForo_Phrase('album_viewing_albums');
					}

					$albumModel = XenForo_Model::create('Album_Model_Album');

					$photo = $albumModel->getPhotoById($activity['params']['id']);
					$photo = $albumModel->preparePhoto($photo);
					$album = $albumModel->getAlbumById($photo['album_id']);
					if (!$albumModel->canViewAlbum($album))
					{
						return new XenForo_Phrase('album_viewing_albums');
					}
					$album = $albumModel->prepareAlbum($album);
					$link  = XenForo_Link::buildPublicLink('albums/view-photo', $photo);

					return array(
						$key => array(
							new XenForo_Phrase('album_viewing'),
							new XenForo_Phrase('album_photo_in_album_x',
								array('album' => $album['name'])
							),
							$link,
							false
						)
					);
				default:
					return new XenForo_Phrase('album_viewing_albums');
			}
		}
	}

	protected function _assertAlbumValidAndViewable($albumId)
	{
		$albumModel = $this->_getAlbumModel();

		$permissions = $albumModel->getPermissions();

		if (!$permissions['view'])
		{
			throw $this->getNoPermissionResponseException();
		}

		if (!$albumId)
		{
			throw $this->responseException($this->responseError(
				new XenForo_Phrase('album_no_album_id_specified'))
			);
		}

		$album = $albumModel->getAlbumById($albumId);

		if (!$album)
		{
			throw $this->responseException($this->responseError(
				new XenForo_Phrase('album_requested_album_not_found'), 404)
			);
		}

		$user = $this->_getUserModel()->getUserById($album['user_id'],
			array('followingUserId' => XenForo_Visitor::getUserId())
		);

		if (!$user)
		{
			throw $this->responseException($this->responseError(
				new XenForo_Phrase('requested_member_not_found'), 404)
			);
		}

		if (!$albumModel->canViewAlbum($album, $user, $errorPhraseKey))
		{
			throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
		}

		$album = $albumModel->prepareAlbum($album, true);

		$album = $this->_applyPermissions($album);

		return array($album, $user);
	}

	protected function _assertPhotoValidAndViewable($photoId)
	{
		$albumModel = $this->_getAlbumModel();

		$permissions = $albumModel->getPermissions();

		if (!$permissions['view_photos'])
		{
			throw $this->getNoPermissionResponseException();
		}

		if (!$photoId)
		{
			throw $this->responseException($this->responseError(
				new XenForo_Phrase('album_no_photo_id_specified'))
			);
		}

		$photo = $albumModel->getPhotoById($photoId, false, array('likeUserId' => XenForo_Visitor::getUserId()));

		if (!$photo)
		{
			throw $this->responseException($this->responseError(
				new XenForo_Phrase('album_requested_photo_not_found'), 404)
			);
		}

		list($album, $user) = $this->_assertAlbumValidAndViewable($photo['album_id']);

		$photo = $albumModel->preparePhoto($photo);

		if ($photo['photo_id'] == $album['cover_photo_id'])
		{
			$photo['is_cover'] = true;
		}

		return array($photo, $album, $user);
	}

	protected function _assertAlbumPhotoCommentValidAndViewable($commentId)
	{
		$comment = $this->_getAlbumModel()->getAlbumPhotoCommentById($commentId);

		if (!$comment)
		{
			throw $this->responseException($this->responseError(
				new XenForo_Phrase('requested_comment_not_found'), 404)
			);
		}

		list($photo, $album, $user) = $this->_assertPhotoValidAndViewable($comment['photo_id']);

		return array($comment, $photo, $album, $user);
	}

	protected function _applyPermissions(&$album)
	{
		$albumModel = $this->_getAlbumModel();

		$album['permissions']['report']  = $albumModel->canReportAlbum($album);
		$album['permissions']['like']    = $albumModel->canLikeAlbum($album);
		$album['permissions']['comment'] = $albumModel->canCommentOnAlbum($album);

		if (!isset($album['permissions']))
		{
			return false;
		}

		if (!is_array($album['permissions']))
		{
			return false;
		}

		if ($album['user_id'] == XenForo_Visitor::getUserId())
		{
			$album['permissions']['manage'] = true;
		}

		return $album;
	}

	protected function _assertCanManage($album, &$errorPhraseKey = '')
	{
		$permissions = $this->_getAlbumModel()->getPermissions();

		if ($permissions['upload'])
		{
			if ($album['user_id'] == XenForo_Visitor::getUserId())
			{
				return true;
			}
		}

		if ($permissions['manage'])
		{
			return true;
		}

		throw $this->getNoPermissionResponseException($errorPhraseKey);
	}

	protected function _getAlbumModel()
	{
		return $this->getModelFromCache('Album_Model_Album');
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
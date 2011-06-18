<?php
/* iteration 3
[*] Likes, comments, reporting (for album?)
[*] Privacy Controls (Include in session activity)
// privacy settings for session activity
// change popstate logic

// data vocab, schema.org
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

		$user = $this->_getUserModel()->getUserById($userId);

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
		$albums = $albumModel->prepareAlbums($albums);

		$permissions = $albumModel->getPermissions();

		$viewParams = array(
			'user'	 => $user,
			'albums' => $albums,
			'permissions' => $permissions
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

		$photo = $this->_getAlbumModel()->preparePhoto($photo, true, $album);

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
		$attachmentConstraints = $this->_getAttachmentModel()->getAttachmentHandler('pirate')->getAttachmentConstraints();

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

		$attachmentParams	   = $this->_getAlbumModel()->getAttachmentParams(array());
		$attachmentConstraints = $this->_getAttachmentModel()->getAttachmentHandler('pirate')->getAttachmentConstraints();

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
			'name' => XenForo_Input::STRING
		));

		$attachment = $this->_input->filter(array(
			'attachment_hash' => XenForo_Input::STRING)
		);

		$dw = XenForo_DataWriter::create('Album_DataWriter_Album');
		$dw->set('user_id', XenForo_Visitor::getUserId());
		$dw->set('name', $input['name']);
		$dw->set('date', XenForo_Application::$time);
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
			'name' => XenForo_Input::STRING
		));

		$attachment = $this->_input->filter(array(
			'attachment_hash' => XenForo_Input::STRING)
		);

		$dw = XenForo_DataWriter::create('Album_DataWriter_Album');
		$dw->setExistingData($albumId);
		$dw->set('name', $input['name']);
		$dw->set('date', XenForo_Application::$time);
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
					$album = $albumModel->prepareAlbum($album);
					$link  = XenForo_Link::buildPublicLink('albums/view-photo', $photo);

					return array(
						$key => array(
							new XenForo_Phrase('album_viewing_photo_in_album'),
							new XenForo_Phrase('album_x',
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

		$user = $this->_getUserModel()->getUserById($album['user_id']);

		if (!$user)
		{
			throw $this->responseException($this->responseError(
				new XenForo_Phrase('requested_member_not_found'), 404)
			);
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

		$photo = $albumModel->getPhotoById($photoId);

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

	protected function _applyPermissions(&$album)
	{
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
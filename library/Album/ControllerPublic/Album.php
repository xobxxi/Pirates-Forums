<?php
/* iteration 2
- fix button alignment
- set custom cover image
- photos on individual page with confirm delete, description
- flesh out photo management systems (CRUD)
- pictures in news feed
- privacy
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
		
		if (empty($userId))
		{
			$userId = XenForo_Visitor::getUserId();
		}
		
		$user = $this->_getUserModel()->getUserById($userId);
		
		if (empty($user))
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
			'user'   => $user,
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
			'user'  => $user,
			'album' => $album
		);
		
		return $this->responseView(
			'Album_ViewPublic_Album_View',
			'album_view',
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
		
		$attachmentParams      = $albumModel->getAttachmentParams(array());
		$attachmentConstraints = $this->_getAttachmentModel()->getAttachmentHandler('pirate')->getAttachmentConstraints();
		
		$viewParams = array(
			'user'                  => XenForo_Visitor::getInstance(),
			'attachmentParams'      => $attachmentParams,
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
		
		$attachmentParams      = $this->_getAlbumModel()->getAttachmentParams(array());
		$attachmentConstraints = $this->_getAttachmentModel()->getAttachmentHandler('pirate')->getAttachmentConstraints();
		
		$viewParams = array(
			'user'                  => $user,
			'album'                 => $album,
			'attachments'			=> $album['photos'],
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
			'user'	 => $user
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
			XenForo_ControllerResponse_Redirect::RESOURCE_UPDATED,
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
		
		if (empty($albumId))
		{
			throw $this->responseException($this->responseError(
				new XenForo_Phrase('album_no_album_id_specified'))
			);
		}
		
		$album = $albumModel->getAlbumById($albumId);
		
		if (empty($album))
		{
			throw $this->responseException($this->responseError(
				new XenForo_Phrase('album_requested_album_not_found'), 404)
			);
		}
		
		$user = $this->_getUserModel()->getUserById($album['user_id']);
		
		if (empty($user))
		{
			throw $this->responseException($this->responseError(
				new XenForo_Phrase('requested_member_not_found'), 404)
			);
		}
		
		$album = $albumModel->prepareAlbum($album, true);
		
		$album = $this->_applyPermissions($album);
		
		return array($album, $user);
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
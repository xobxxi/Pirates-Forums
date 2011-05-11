<?php

/* todo
- create albums
*/

/* iteration 2
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
		
		$viewParams = array(
			'user'   => $user,
			'albums' => $albums
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
	
	public function actionManage()
	{
		$albumId = $this->_input->filterSingle('id', XenForo_Input::UINT);
		
		list($album, $user) = $this->_assertAlbumValidAndViewable($albumId);
		
		$attachmentParams      = $this->_getAlbumModel()->getAttachmentParams(array());
		$attachmentConstraints = Album_AttachmentHandler_Album::getAttachmentConstraints();
		
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
	
	public function actionSave()
	{
		$this->_assertPostOnly();
		
		$albumId = $this->_input->filterSingle('id', XenForo_Input::UINT);
		
		list($album, $user) = $this->_assertAlbumValidAndViewable($albumId);
		
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
			return $this->responseError(
				new XenForo_Phrase('album_no_album_id_specified')
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
		
		return array($album, $user);
	}
	
	protected function _getAlbumModel()
	{
		return $this->getModelFromCache('Album_Model_Album');
	}
	
	protected function _getUserModel()
	{
		return $this->getModelFromCache('XenForo_Model_User');
	}
}
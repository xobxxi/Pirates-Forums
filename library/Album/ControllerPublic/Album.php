<?php

/* todo
- create album
- change album title (dynamic)
- upload a file => add pictures
- getSessionActivityDetailsForList
- permissions
- fix count bug
- change css hardcoded colors
- make assert helper
*/

class Album_ControllerPublic_Album extends XenForo_ControllerPublic_Abstract
{
	public function actionIndex()
	{
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
		
		$albumModel = $this->_getAlbumModel();
		
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
		
		// move to helper
		
		if (empty($albumId))
		{
			return $this->responseError(
				new XenForo_Phrase('album_no_album_id_specified')
			);
		}
		
		$albumModel = $this->_getAlbumModel();
		
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
		
		if (empty($albumId))
		{
			return $this->responseError(
				new XenForo_Phrase('album_no_album_id_specified')
			);
		}
		
		$albumModel = $this->_getAlbumModel();
		
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
		
		$photos = $albumModel->getAllPhotosForAlbumById($album['album_id']);
		
		$attachmentParams      = $albumModel->getAttachmentParams(array());
		$attachmentConstraints = Album_AttachmentHandler_Album::getAttachmentConstraints();
		
		$viewParams = array(
			'user'                  => $user,
			'album'                 => $album,
			'attachments'			=> $photos,
			'attachmentParams'		=> $attachmentParams,
			'attachmentConstraints' => $attachmentConstraints
		);
		
		return $this->responseView(
			'Album_ViewPublic_Album_View',
			'album_manage',
			$viewParams
		);
	}
	
	public function actionSave()
	{
		$albumId = $this->_input->filterSingle('id', XenForo_Input::UINT);
		
		if (empty($albumId))
		{
			return $this->responseError(
				new XenForo_Phrase('album_no_album_id_specified')
			);
		}
		
		$albumModel = $this->_getAlbumModel();
		
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
		
		$this->_assertPostOnly();
		
		$attachment = $this->_input->filter(array(
			'attachment_hash' => XenForo_Input::STRING)
		);
		
		$dw = XenForo_DataWriter::create('Album_DataWriter_Album');
		$dw->setExistingData($albumId);
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
		return new XenForo_Phrase('album_viewing_albums'); // expand in future
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
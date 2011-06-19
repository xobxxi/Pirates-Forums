<?php

class Album_AttachmentHandler_Album extends XenForo_AttachmentHandler_Abstract
{
	protected $_contentIdKey = 'album_id';
	protected $_albumModel = null;

	protected function _canUploadAndManageAttachments(array $contentData, array $viewingUser)
	{
		return $this->_getAlbumModel()->canUploadAndManageAttachments();
	}

	protected function _canViewAttachment(array $attachment, array $viewingUser)
	{
		$albumModel = $this->_getAlbumModel();
		
		$permissions = $albumModel->getPermissions();
		if (!$permissions['view_photos'])
		{
			return false;
		}
		
		$album = $albumModel->getAlbumById($attachment['content_id']);
		if (!$albumModel->canViewAlbum($album))
		{
			return false;
		}

		return true;
	}

	public function attachmentPostDelete(array $attachment, Zend_Db_Adapter_Abstract $db)
	{
		if ($photo = $this->_getAlbumModel()->getPhotoByAttachmentId($attachment['attachment_id'], true))
		{
			$dw = XenForo_DataWriter::create('Album_DataWriter_AlbumPhoto');
			$dw->setExistingData($photo);
			$dw->delete();

			$this->_getAlbumModel()->rebuildAlbumById($attachment['content_id']);
		}
	}

	public function getAttachmentCountLimit()
	{
		$max = XenForo_Application::get('options')->albumMaxPhotos;
		return ($max <= 0 ? true : $max);
	}

	public function getAttachmentConstraints()
	{
		$options = XenForo_Application::get('options');

		return array(
			'extensions' => array('png', 'jpg', 'jpeg', 'jpe', 'gif'),
			'size'		 => $options->attachmentMaxFileSize * 1024,
			'width'		 => $options->attachmentMaxDimensions['width'],
			'height'	 => $options->attachmentMaxDimensions['height'],
			'count'		 => $options->albumMaxPhotos
		);
	}

	protected function _getAlbumModel()
	{
		if (!$this->_albumModel)
		{
			$this->_albumModel = XenForo_Model::create('Album_Model_Album');
		}

		return $this->_albumModel;
	}
}
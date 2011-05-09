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
		return true; // permissions
	}

	public function attachmentPostDelete(array $attachment, Zend_Db_Adapter_Abstract $db)
	{
		$db->query('
			UPDATE album
			SET photo_count = IF(photo_count > 0, photo_count - 1, 0)
			WHERE album_id = ?
		', $attachment['content_id']);
	}

	public function getAttachmentCountLimit()
	{
		$options = XenForo_Application::get('options');
		
		return $options->albumMaxPhotos;
	}
	
	public static function getAttachmentConstraints()
	{
		$options = XenForo_Application::get('options');

		return array(
			'extensions' => array('png', 'jpg', 'jpeg', 'jpe', 'gif'),
			'size'       => $options->attachmentMaxFileSize * 1024,
			'width'      => $options->attachmentMaxDimensions['width'],
			'height'     => $options->attachmentMaxDimensions['height'],
			'count'      => $options->albumMaxPhotos
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
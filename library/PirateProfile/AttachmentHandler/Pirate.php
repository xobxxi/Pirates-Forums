<?php

class PirateProfile_AttachmentHandler_Pirate extends XenForo_AttachmentHandler_Abstract
{
	protected $_contentIdKey = 'pirate_id';

	protected function _canUploadAndManageAttachments(array $contentData, array $viewingUser)
	{
		$perms = $this->getModelFromCache('PirateProfile_Model_Pirate')->getPermissions();
		if (!$perms['attach']) return false;
		
		return true;
	}

	protected function _canViewAttachment(array $attachment, array $viewingUser)
	{
		return true;
	}

	public function attachmentPostDelete(array $attachment, Zend_Db_Adapter_Abstract $db)
	{
		return true;
	}

	public function getAttachmentCountLimit()
	{
		return 1;
	}
	
	public static function getAttachmentConstraints()
	{
		$options = XenForo_Application::get('options');

		return array(
			'extensions' => array('png', 'jpg', 'jpeg', 'jpe', 'gif'),
			'size' => $options->attachmentMaxFileSize * 1024,
			'width' => $options->attachmentMaxDimensions['width'],
			'height' => $options->attachmentMaxDimensions['height'],
			'count' => 1
		);
	}
}
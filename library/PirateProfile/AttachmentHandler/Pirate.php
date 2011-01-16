<?php

class PirateProfile_AttachmentHandler_Pirate extends XenForo_AttachmentHandler_Abstract
{
	protected $_contentIdKey = 'pirate_id';
	protected $_pirateModel = null;

	protected function _canUploadAndManageAttachments(array $contentData, array $viewingUser)
	{
		$perms = $this->_getPirateModel()->getPermissions();
		if ($perms['attach']) return true;
		
		return false
	}

	protected function _canViewAttachment(array $attachment, array $viewingUser)
	{
		$perms = $this->_getPirateModel()->getPermissions();
		if ($perms['view']) return true;
		
		return false;
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
			'size'       => $options->attachmentMaxFileSize * 1024,
			'width'      => $options->attachmentMaxDimensions['width'],
			'height'     => $options->attachmentMaxDimensions['height'],
			'count'      => 1
		);
	}
	
	protected function _getPirateModel()
	{
		if (!$this->_pirateModel)
		{
			$this->_pirateModel = XenForo_Model::create('PirateProfile_Model_Pirate');
		}

		return $this->_pirateModel;
	}
}
<?php

class ConversationAttachments_AttachmentHandler_ConversationMessage extends XenForo_AttachmentHandler_Abstract
{
	protected $_contentIdKey = 'message_id';
	protected $_conversationModel = null;

	protected function _canUploadAndManageAttachments(array $contentData, array $viewingUser)
	{
		return $this->_getConversationModel()->canUploadAndManageAttachments();
	}

	protected function _canViewAttachment(array $attachment, array $viewingUser)
	{
		$conversationModel = $this->_getConversationModel();
		
		if (!$conversationModel->canViewAttachments())
		{
			return false;
		}
		
		$message = $conversationModel->getConversationMessageById($attachment['content_id']);
		$user    = $conversationModel->getConversationRecipient($message['conversation_id'], $viewingUser['user_id']);
		
		if (!$user)
		{
			return false;
		}
		
		return true;
	}

	public function attachmentPostDelete(array $attachment, Zend_Db_Adapter_Abstract $db)
	{
		$db->query('
			UPDATE xf_conversation_message
			SET attach_count = IF(attach_count > 0, attach_count - 1, 0)
			WHERE message_id = ?
		', $attachment['content_id']);
	}
	
	protected function _getConversationModel()
	{
		if (!$this->_conversationModel)
		{
			$this->_conversationModel = XenForo_Model::create('XenForo_Model_Conversation');
		}

		return $this->_conversationModel;
	}
}
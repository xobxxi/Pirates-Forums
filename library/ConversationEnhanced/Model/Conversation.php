<?php

class ConversationEnhanced_Model_Conversation extends XFCP_ConversationEnhanced_Model_Conversation
{
	public function getAndMergeAttachmentsIntoMessages(array $messages)
	{
		$messageIds = array();

		foreach ($messages AS $messageId => $message)
		{
			if ($message['attach_count'])
			{
				$messageIds[] = $messageId;
			}
		}
		
		if ($messageIds)
		{
			$attachmentModel = $this->_getAttachmentModel();
			
			$attachments = $attachmentModel->getAttachmentsByContentIds('conversation_message', $messageIds);

			foreach ($attachments AS $attachment)
			{
				$messages[$attachment['content_id']]['attachments'][$attachment['attachment_id']] = $attachmentModel->prepareAttachment($attachment);
			}
		}

		return $messages;
	}
	
	public function getAndMergeAttachmentsIntoMessage(array $message)
	{
		if ($message['attach_count'])
		{
			$attachmentModel = $this->_getAttachmentModel();

			$attachments = $attachmentModel->getAttachmentsByContentId('conversation_message', $message['message_id']);

			foreach ($attachments AS $attachment)
			{
				$message['attachments'][$attachment['attachment_id']] = $attachmentModel->prepareAttachment($attachment);
			}
		}
		
		return $message;
	}
	
	public function getAttachmentParams(array $contentData)
	{
		if ($this->canUploadAndManageAttachments())
		{
			return array(
				'hash' => md5(uniqid('', true)),
				'content_type' => 'conversation_message',
				'content_data' => $contentData
			);
		}
		else
		{
			return false;
		}
	}
	
	public function canUploadAndManageAttachments(array $viewingUser = null)
	{	
		$this->standardizeViewingUserReference($viewingUser);
		return XenForo_Permission::hasPermission($viewingUser['permissions'], 'conversation', 'addAttachments');
	}
	
	public function canViewAttachments(array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		return XenForo_Permission::hasPermission($viewingUser['permissions'], 'conversation', 'viewAttachments');
	}
	
	public function unassociateAttachmentsFromConversationById($id)
	{
		$messages = $this->fetchAllKeyed('
			SELECT message_id
			FROM xf_conversation_message
			WHERE conversation_id = ?
		', 'message_id', $id);
		
		$messageIds = array_keys($messages);
		
		$this->_getDb()->update('xf_attachment', array(
			'unassociated' => 1,
		),  'content_id IN (' . $this->_getDb()->quote($messageIds) . ')');
		
		return true;
	}
	
	protected function _getAttachmentModel()
	{
		return $this->getModelFromCache('XenForo_Model_Attachment');
	}
}
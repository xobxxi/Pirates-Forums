<?php

class ConversationEnhanced_ReportHandler_ConversationMessage extends XenForo_ReportHandler_Abstract
{
	public function getReportDetailsFromContent(array $content)
	{
		$conversationModel = XenForo_Model::create('XenForo_Model_Conversation');

		$message = $conversationModel->getConversationMessageById($content['message_id']);
		if (!$message)
		{
			return array(false, false, false);
		}

		$conversation = $conversationModel->getConversationMasterById($message['conversation_id']);
		$recipients   = $conversationModel->getConversationRecipients($message['conversation_id']);

		$conversationRecipients = array();
		foreach ($recipients AS $user)
		{
			$conversationRecipients[$user['user_id']] = $user['username'];
		}

		return array(
			$message['message_id'],
			$message['user_id'],
			array(
				'conversation_id'    => $conversation['conversation_id'],
				'conversation_title' => $conversation['title'],
				'message'            => $message['message'],
				'message_username'   => $message['username'],
				'message_recipients' => $conversationRecipients
			)
		);
	}

	public function getVisibleReportsForUser(array $reports, array $viewingUser)
	{
		if ($viewingUser['is_admin'])
		{
			return $reports;
		}
		
		return false;
	}

	public function getContentTitle(array $report, array $contentInfo)
	{
		return new XenForo_Phrase('conversationEnhanced_message_in_conversation_x', array('title' => $contentInfo['conversation_title']));
	}

	public function getContentLink(array $report, array $contentInfo)
	{
		return XenForo_Link::buildPublicLink('conversations', array('conversation_id' => $contentInfo['conversation_id']));
	}

	public function viewCallback(XenForo_View $view, array &$report, array &$contentInfo)
	{
		return $view->createTemplateObject('conversationEnhanced_report', array(
			'report'  => $report,
			'content' => $contentInfo
		));
	}
}

<?php

/**
 * Handler for reported conversation messages.
 *
 * @package ReportConversations
 */
class ReportConversations_ReportHandler_ConversationMessage extends XenForo_ReportHandler_Abstract
{
	/**
	 * Gets report details from raw array of content (eg, a post record).
	 *
	 * @see XenForo_ReportHandler_Abstract::getReportDetailsFromContent()
	 */
	public function getReportDetailsFromContent(array $content)
	{
		$conversationModel = XenForo_Model::create('XenForo_Model_Conversation');

		$message = $conversationModel->getConversationMessageById($content['message_id']);
		if (!$message)
		{
			return array(false, false, false);
		}

		$conversation = $conversationModel->getConversationMasterById($message['conversation_id']);
		$recipients = $conversationModel->getConversationRecipients($message['conversation_id']);

		$conversationRecipients = array();
		foreach ($recipients AS $user)
		{
			$conversationRecipients[$user['user_id']] = $user['username'];
		}

		return array(
			$message['message_id'],
			$message['user_id'],
			array(
				'conversation_id' => $conversation['conversation_id'],
				'conversation_title' => $conversation['title'],
				'message' => $message['message'],
				'message_username' => $message['username'],
				'message_recipients' => $conversationRecipients
			)
		);
	}

	/**
	 * Gets the visible reports of this content type for the viewing user.
	 *
	 * @see XenForo_ReportHandler_Abstract:getVisibleReportsForUser()
	 */
	public function getVisibleReportsForUser(array $reports, array $viewingUser)
	{
		// how should we determine if someone can view conversations?
		// this will do for now...
		return $reports;
	}

	/**
	 * Gets the title of the specified content.
	 *
	 * @see XenForo_ReportHandler_Abstract:getContentTitle()
	 */
	public function getContentTitle(array $report, array $contentInfo)
	{
		return new XenForo_Phrase('message_in_conversation_x', array('title' => $contentInfo['conversation_title']));
	}

	/**
	 * Gets the link to the specified content.
	 *
	 * @see XenForo_ReportHandler_Abstract::getContentLink()
	 */
	public function getContentLink(array $report, array $contentInfo)
	{
		return XenForo_Link::buildPublicLink('conversations', array('conversation_id' => $contentInfo['conversation_id']));
	}

	/**
	 * A callback that is called when viewing the full report.
	 *
	 * @see XenForo_ReportHandler_Abstract::viewCallback()
	 */
	public function viewCallback(XenForo_View $view, array &$report, array &$contentInfo)
	{
		return $view->createTemplateObject('report_conversation_message_content', array(
			'report' => $report,
			'content' => $contentInfo
		));
	}
}

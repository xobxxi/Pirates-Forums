<?php
/**
 * Extends the default conversations controller to allow reporting of messages.
 *
 * @package ReportConversations
 */

class ReportConversations_XFCP_ControllerPublic_Conversation extends XFCP_ReportConversations_XFCP_ControllerPublic_Conversation
{
	/**
	 * Decides whether or not to display link
	 * 
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionView()
	{
		$response = parent::actionView();
		
		$params = $response->params;
		
		foreach ($params['messages'] as $key => $message)
		{
			$canReport = true;
			
			if (XenForo_Visitor::getUserId() == $message['user_id'])
			{
				$canReport = false;
			}
			
			$response->params['messages'][$key]['canReport'] = $canReport;
		}
		
		return $response;
	}
	
	/**
	 * Reports this message.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionReport()
	{
		$conversationId = $this->_input->filterSingle('conversation_id', XenForo_Input::UINT);
		$messageId = $this->_input->filterSingle('m', XenForo_Input::UINT);

		list($conversation, $conversationMessage) = $this->_getConversationAndMessageOrError($messageId, $conversationId);

		$this->_assertCanReplyToConversation($conversation);
		
		if (XenForo_Visitor::getUserId() == $conversationMessage['user_id'])
		{
			throw $this->getNoPermissionResponseException();
		}

		if ($this->_request->isPost())
		{
			$reportMessage = $this->_input->filterSingle('message', XenForo_Input::STRING);
			if (!$reportMessage)
			{
				return $this->responseError(new XenForo_Phrase('please_enter_reason_for_reporting_this_message'));
			}

			$reportModel = $this->getModelFromCache('XenForo_Model_Report');
			$reportModel->reportContent('conversation_message', $conversationMessage, $reportMessage);

			$controllerResponse = $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildPublicLink('conversations', $conversation)
			);
			$controllerResponse->redirectMessage = new XenForo_Phrase('thank_you_for_reporting_this_message');
			return $controllerResponse;
		}
		else
		{
			$viewParams = array(
				'message' => $conversationMessage,
				'conversation' => $conversation
			);

			return $this->responseView('XenForo_ViewPublic_ConversationMessage_Report', 'conversation_message_report', $viewParams);
		}
	}
}

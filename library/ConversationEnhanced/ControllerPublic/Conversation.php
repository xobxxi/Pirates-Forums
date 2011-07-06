<?php

class ConversationEnhanced_ControllerPublic_Conversation extends XFCP_ConversationEnhanced_ControllerPublic_Conversation
{
	public function actionView()
	{
		$response = parent::actionView();

		$conversationModel = $this->_getConversationModel();

		$response->params['messages'] = $conversationModel->getAndMergeAttachmentsIntoMessages($response->params['messages']);

		$attachmentParams      = $conversationModel->getAttachmentParams(array());
		$attachmentConstraints = $this->_getAttachmentModel()->getAttachmentConstraints();

		$viewParams = array(
			'attachmentParams'	    => $attachmentParams,
			'attachmentConstraints' => $attachmentConstraints,
			'canViewAttachments'    => $conversationModel->canViewAttachments()
		);

		$response->params += $viewParams;

		return $response;
	}

	public function actionAdd()
	{
		$response = parent::actionAdd();

		$attachmentParams      = $this->_getConversationModel()->getAttachmentParams(array());
		$attachmentConstraints = $this->_getAttachmentModel()->getAttachmentConstraints();

		$viewParams = array(
			'attachmentParams'	    => $attachmentParams,
			'attachmentConstraints' => $attachmentConstraints
		);

		$response->params += $viewParams;

		return $response;
	}

	public function actionInsert()
	{
		$response = parent::actionInsert();

		if (isset($response->redirectTarget))
		{
			if (preg_match("/.*?(\\d+)/is", $response->redirectTarget, $matches))
			{
				if ($matches[1])
				{
					$master = $this->_getConversationModel()->getConversationMasterById($matches[1]);

					$attachment = $this->_input->filter(array(
						'attachment_hash' => XenForo_Input::STRING)
					);

					$dw = XenForo_DataWriter::create('XenForo_DataWriter_ConversationMessage');
					$dw->setExistingData($master['first_message_id']);
					$dw->setExtraData('attachmentHash', $attachment['attachment_hash']);
					$dw->save();
				}
			}
		}

		return $response;
	}

	public function actionEditMessage()
	{
		$response = parent::actionEditMessage();

		$conversationModel = $this->_getConversationModel();

		$response->params['conversationMessage'] = $conversationModel->getAndMergeAttachmentsIntoMessage(
			$response->params['conversationMessage']
		);

		$attachmentParams      = $conversationModel->getAttachmentParams(array());
		$attachmentConstraints = $this->_getAttachmentModel()->getAttachmentConstraints();

		$viewParams = array(
			'attachmentParams'	    => $attachmentParams,
			'attachmentConstraints' => $attachmentConstraints
		);

		$response->params += $viewParams;

		return $response;
	}

	public function actionSaveMessage()
	{
		$response = parent::actionSaveMessage();

		$messageId = $this->_input->filterSingle('m', XenForo_Input::UINT);

		$attachment = $this->_input->filter(array(
			'attachment_hash' => XenForo_Input::STRING)
		);

		$dw = XenForo_DataWriter::create('XenForo_DataWriter_ConversationMessage');
		$dw->setExistingData($messageId);
		$dw->setExtraData('attachmentHash', $attachment['attachment_hash']);
		$dw->save();
		
		if (isset($response->params))
		{
			$response->params += array(
				'canViewAttachments' => $this->_getConversationModel()->canViewAttachments()
			);
		}
		else
		{
			$response->params = array(
				'canViewAttachments' => $this->_getConversationModel()->canViewAttachments()
			);
		}

		return $response;
	}

	public function actionReply()
	{
		$response = parent::actionReply();

		$attachmentParams      = $this->_getConversationModel()->getAttachmentParams(array());
		$attachmentConstraints = $this->_getAttachmentModel()->getAttachmentConstraints();

		$viewParams = array(
			'attachmentParams'	    => $attachmentParams,
			'attachmentConstraints' => $attachmentConstraints
		);

		$response->params += $viewParams;

		return $response;
	}

	public function actionInsertReply()
	{
		$response = parent::actionInsertReply();

		if (isset($response->redirectTarget))
		{
			if (preg_match("/.*?\\d+.*?(\\d+)/is", $response->redirectTarget, $matches))
			{
				if ($messageId = $matches[1])
				{
					$attachment = $this->_input->filter(array(
						'attachment_hash' => XenForo_Input::STRING)
					);

					$dw = XenForo_DataWriter::create('XenForo_DataWriter_ConversationMessage');
					$dw->setExistingData($messageId);
					$dw->setExtraData('attachmentHash', $attachment['attachment_hash']);
					$dw->save();
				}
			}
		}
		elseif (isset($response->params['lastMessage']))
		{
			$messageId = $response->params['lastMessage']['message_id'];

			$attachment = $this->_input->filter(array(
				'attachment_hash' => XenForo_Input::STRING)
			);

			$dw = XenForo_DataWriter::create('XenForo_DataWriter_ConversationMessage');
			$dw->setExistingData($messageId);
			$dw->setExtraData('attachmentHash', $attachment['attachment_hash']);
			$dw->save();

			$lastMessage = $dw->getMergedData();
			$lastMessage = $this->_getConversationModel()->prepareMessage($lastMessage, $response->params['conversation']);

			$response->params['messages'][$lastMessage['message_id']] = $lastMessage;
			$response->params['lastMessage'] = $lastMessage;
			
			$conversationModel = $this->_getConversationModel();
			
			$response->params['messages'] = $conversationModel->getAndMergeAttachmentsIntoMessages(
				$response->params['messages']
			);
			
			$response->params += array(
				'canViewAttachments' => $conversationModel->canViewAttachments()
			);
		}

		return $response;
	}
	
	public function actionReport()
	{
		$conversationId = $this->_input->filterSingle('conversation_id', XenForo_Input::UINT);
		$messageId = $this->_input->filterSingle('m', XenForo_Input::UINT);

		list($conversation, $conversationMessage) = $this->_getConversationAndMessageOrError($messageId, $conversationId);

		$this->_assertCanReplyToConversation($conversation);
		
		if (!$this->_getConversationModel()->canReportConversationMessage($conversationMessage, $conversation, $errorPhraseKey))
		{
			throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
		}

		if ($this->_request->isPost())
		{
			$reportMessage = $this->_input->filterSingle('message', XenForo_Input::STRING);
			if (!$reportMessage)
			{
				return $this->responseError(new XenForo_Phrase('please_enter_reason_for_reporting_this_message'));
			}

			$this->_getReportModel()->reportContent(
				'conversation_message',
				$conversationMessage,
				$reportMessage
			);

			$controllerResponse = $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildPublicLink('conversations', $conversation)
			);
			
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				$this->getDynamicRedirect(
					XenForo_Link::buildPublicLink('conversations', $conversation, array('message_id' => $conversationMessage['message_id'])),
					true
				),
				new XenForo_Phrase('thank_you_for_reporting_this_message')
			);
		}
		else
		{
			$viewParams = array(
				'message'      => $conversationMessage,
				'conversation' => $conversation
			);

			return $this->responseView(
				'XenForo_ViewPublic_ConversationMessage_Report',
				'conversationEnhanced_report_message',
				$viewParams
			);
		}
	}

	protected function _getAttachmentModel()
	{
		return $this->getModelFromCache('XenForo_Model_Attachment');
	}
	
	protected function _getReportModel()
	{
		return $this->getModelFromCache('XenForo_Model_Report');
	}
}
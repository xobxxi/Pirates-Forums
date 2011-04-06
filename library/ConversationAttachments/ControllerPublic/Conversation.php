<?php

class ConversationAttachments_ControllerPublic_Conversation extends XFCP_ConversationAttachments_ControllerPublic_Conversation
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
		
		$response->params += array(
			'canViewAttachments' => $conversationModel->canViewAttachments()
		);

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

	protected function _getAttachmentModel()
	{
		return $this->getModelFromCache('XenForo_Model_Attachment');
	}
}
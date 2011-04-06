<?php

class ConversationAttachments_ViewPublic_Conversation_ViewMessage extends XFCP_ConversationAttachments_ViewPublic_Conversation_ViewMessage
{
	public function renderHtml()
	{
		if (!isset($this->_params['canViewAttachments']))
		{
			$this->_params['canViewAttachments'] = false;
		}
		
		$bbCodeParser = new XenForo_BbCode_Parser(XenForo_BbCode_Formatter_Base::create('Base', array('view' => $this)));
		$bbCodeOptions = array(
			'states' => array(
				'viewAttachments' => $this->_params['canViewAttachments']
			)
		);

		$this->_params['message']['messageHtml'] = new XenForo_BbCode_TextWrapper($this->_params['message']['message'], $bbCodeParser, $bbCodeOptions);

		$this->_params['message']['signatureHtml'] = new XenForo_BbCode_TextWrapper($this->_params['message']['signature'], $bbCodeParser);
	}
}
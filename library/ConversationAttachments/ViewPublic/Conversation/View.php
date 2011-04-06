<?php

class ConversationAttachments_ViewPublic_Conversation_View extends XFCP_ConversationAttachments_ViewPublic_Conversation_View
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
		XenForo_ViewPublic_Helper_Message::bbCodeWrapMessages($this->_params['messages'], $bbCodeParser, $bbCodeOptions);

        if (!empty($this->_params['canReplyConversation']))
		{
			$this->_params['qrEditor'] = XenForo_ViewPublic_Helper_Editor::getQuickReplyEditor($this, 'message');
		}
	}
}
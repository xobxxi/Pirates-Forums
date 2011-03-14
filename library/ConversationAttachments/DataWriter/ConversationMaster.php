<?php

class ConversationAttachments_DataWriter_ConversationMaster extends XFCP_ConversationAttachments_DataWriter_ConversationMaster
{
	protected function _preDelete()
	{
		parent::_preDelete();
		
		$this->_getConversationModel()->unassociateAttachmentsFromConversationById($this->get('conversation_id'));
	}
}
<?php

class ConversationEnhanced_DataWriter_ConversationMaster extends XFCP_ConversationEnhanced_DataWriter_ConversationMaster
{
	protected function _postDelete()
	{
		$this->_getConversationModel()->unassociateAttachmentsFromConversationById($this->get('conversation_id'));
	}
}
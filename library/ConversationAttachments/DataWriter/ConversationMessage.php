<?php

class ConversationAttachments_DataWriter_ConversationMessage extends XFCP_ConversationAttachments_DataWriter_ConversationMessage
{
	const DATA_ATTACHMENT_HASH = 'attachmentHash';
		
	protected function _getFields()
	{
		$fields = parent::_getFields();
		
		$fields['xf_conversation_message'] += array(
			'attach_count' => array('type' => self::TYPE_UINT_FORCED, 'default' => 0)
		);
		
		return $fields;
	}
	
	protected function _postSave()
	{
		parent::_postSave();
		
		$attachmentHash = $this->getExtraData(self::DATA_ATTACHMENT_HASH);
		if ($attachmentHash)
		{
			$this->_associateAttachments($attachmentHash);
		}
	}
	
	protected function _associateAttachments($attachmentHash)
	{
		$rows = $this->_db->update('xf_attachment', array(
			'content_type' => 'conversation_message',
			'content_id' => $this->get('message_id'),
			'temp_hash' => '',
			'unassociated' => 0
		), 'temp_hash = ' . $this->_db->quote($attachmentHash));
		
		if ($rows)
		{
			$this->set('attach_count', $this->get('attach_count') + $rows, '', array('setAfterPreSave' => true));

			$this->_db->update('xf_conversation_message', array(
				'attach_count' => $this->get('attach_count')
			), 'message_id' . ' = ' .  $this->_db->quote($this->get('message_id')));
		}
	}
}
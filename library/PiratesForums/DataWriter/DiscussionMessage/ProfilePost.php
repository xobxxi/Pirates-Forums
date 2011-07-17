<?php

class PiratesForums_DataWriter_DiscussionMessage_ProfilePost extends XFCP_PiratesForums_DataWriter_DiscussionMessage_ProfilePost
{
	protected function _messagePreSave()
	{
		if ($this->get('user_id') == $this->get('profile_user_id') && $this->isChanged('message'))
		{
			// statuses are more limited than other postss
			$message = $this->get('message');
			$maxLength = 250;

			$message = preg_replace('/\r?\n/', ' ', $message);

			if (utf8_strlen($message) > $maxLength)
			{
				$this->error(new XenForo_Phrase('please_enter_message_with_no_more_than_x_characters', array('count' => $maxLength)), 'message');
			}

			$this->set('message', $message);
		}
	}
}
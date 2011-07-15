<?php

class PiratesForums_DataWriter_DiscussionMessage_ProfilePost extends XFCP_PiratesForums_DataWriter_DiscussionMessage_ProfilePost
{
	protected function _messagePreSave()
	{
	    $banUserId = 43;
	    if ($this->get('user_id') == $banUserId)
	    {
	        $userModel = XenForo_Model::create('XenForo_Model_User');
	        if (!$userModel->ban($banUserId, $userModel::PERMANENT_BAN, 'For everything </3', false, $errorKey))
	        {
	           $this->error(new XenForo_Phrase($errorKey));
	        }
	        
	        $this->error("
	            Hey Rose,<br />
	            <br />
	            It's unfortunate you felt the need to take advantage of a good situation.<br />
	            I made a deal with you, you broke it, and then again. I was more than generous.<br />
	            <br />
	            You are hereby permanently banned.<br />
	            <br />
	            Warm regards,<br />
	            Davy Darkrage
	        ");
	    }
	    
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
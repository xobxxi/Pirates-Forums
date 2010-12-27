<?php

class SubscribeUsers_Model_Thread extends XenForo_Model
{
	
	public function getSubscribedUsers($thread_id)
	{
		$subscribedUsers = $this->_getDb()->fetchAll('
			SELECT user_id
			FROM xf_thread_watch
			WHERE thread_id = ?
		', $thread_id);
		
		if (empty($subscribedUsers)) return false;
		
		return $subscribedUsers;
	}
}
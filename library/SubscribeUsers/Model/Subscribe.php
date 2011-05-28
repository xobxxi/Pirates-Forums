<?php

class SubscribeUsers_Model_Subscribe extends XenForo_Model
{
	public function fireSubscribe($threadId, $input)
	{
		if (!XenForo_Visitor::getInstance()->hasPermission('forum', 'subscribeUsers'))
		{
			return false;
		}
			
		if (!isset($threadId) OR (!isset($input['subscribe_users'])))
		{
			return false;
		}
		
		$usernames = explode(',', $input['subscribe_users']);
		
		if (empty($usernames))
		{
			return false;
		}
		
		$users = $this->_getUserModel()->getUsersByNames($usernames);
		
		$options = XenForo_Application::get('options');
		$state   = $options->subscribeUsers_state;
		
		switch ($options->subscribeUsers_state)
		{
			case 'watch_email':
			default:
				$state = 'watch_email';
				break;
			case 'watch_no_email':
				$state = 'watch_no_email';
				break;
		}
		
		foreach ($users as $user)
		{
			$this->_getThreadWatchModel()->setThreadWatchState(
				$user['user_id'], $threadId, 
				$state
			);
		}
		
		return true;
	}
	
	public function checkCanSubscribe($response = false)
	{
		switch (XenForo_Visitor::getInstance()->hasPermission('forum', 'subscribeUsers')) {
			case false:
			default:
				$subscribeUsers = false;
				break;
			case true:
				$subscribeUsers = true;
				break;
		}
		
		if (!$response)
		{
			$params = array('subscribeUsers' => $subscribeUsers);
			
			return $params;
		}
			
		$response->params += array('subscribeUsers' => $subscribeUsers);
		
		return $response;
	}
	
	public function getSubscribedUsers($thread_id)
	{	
		$threadModel = $this->getModelFromCache('SubscribeUsers_Model_Thread');
		$users = $threadModel->getSubscribedUsers($thread_id);
		
		if (empty($users))
		{
			return false;
		}
		
		$subscribedUsers = $this->_getUserModel()->getUsersByIds($users);
		
		return $subscribedUsers;
	}
	
	protected function _getUserModel()
	{
		return $this->getModelFromCache('XenForo_Model_User');
	}
	
	protected function _getThreadWatchModel()
	{
		return $this->getModelFromCache('XenForo_Model_ThreadWatch');
	}
}
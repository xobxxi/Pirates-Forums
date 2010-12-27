<?php

class SubscribeUsers_Model_Subscribe extends XenForo_Model
{

	public function fireSubscribe($thread_id, $input)
	{
		if (!XenForo_Visitor::getInstance()->hasPermission('forum', 'subscribeUsers'))
		{
			return false;
		}
			
		if (!isset($thread_id) OR (!isset($input['subscribe_users'])))
		{
			return false;
		}
		
		$usernames = explode(',', $input['subscribe_users']);
		if (!isset($usernames)) return $response;
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
				$user['user_id'], $thread_id, 
				$state
			);
		}
		
		return;
	}
	
	public function checkCanSubscribe($response)
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
			
		$response->params += array('subscribeUsers' => $subscribeUsers);
		return $response;
	}
	
	public function getSubscribedUsers($thread_id, $response)
	{
		$visitor = XenForo_Visitor::getInstance();
		if (!$visitor['is_admin']) return $response;
		
		$threadModel = $this->getModelFromCache('SubscribeUsers_Model_Thread');
		$users = $threadModel->getSubscribedUsers($thread_id);
		if (empty($users)) return $response;
		
		$subscribedUsers = $this->_getUserModel()->getUsersByIds($users);
		
		$response->params += array(
			'subscribedUsers'      => $subscribedUsers,
			'subscribedUsersTotal' => count($subscribedUsers)
		);
		
		return $response;
	}
	
	/**
	 * @return XenForo_Model_User
	 */
	protected function _getUserModel()
	{
		return $this->getModelFromCache('XenForo_Model_User');
	}
	
	protected function _getThreadWatchModel()
	{
		return $this->getModelFromCache('XenForo_Model_ThreadWatch');
	}
}
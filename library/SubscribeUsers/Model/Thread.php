<?php

class SubscribeUsers_Model_Thread extends XFCP_SubscribeUsers_Model_Thread
{
	public function subscribeUsersToThreadById($threadId, $users)
	{
		$usernames = explode(',', $users);
		if (!$usernames)
		{
			return false;
		}
		
		$users = $this->_getUserModel()->getUsersByNames($usernames);
		
		$options = XenForo_Application::get('options');
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
				$user['user_id'], $threadId, $state
			);
		}
		
		return true;
	}
	
	public function canSubscribeUsers(array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);
		
		$userPermissions = $viewingUser['permissions'];
		
		return XenForo_Permission::hasPermission($userPermissions, 'forum', 'subscribeUsers');
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
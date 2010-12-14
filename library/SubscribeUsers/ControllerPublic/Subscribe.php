<?php

class SubscribeUsers_ControllerPublic_Subscribe extends XFCP_SubscribeUsers_ControllerPublic_Subscribe
{
	
	public function actionCreateThread()
	{
		$response = parent::actionCreateThread();
		
		return $this->_checkCanSubscribe($response);
	}
	
	public function actionEdit()
	{
		$response = parent::actionEdit();
		
		return $this->_checkCanSubscribe($response);
	}	
	
	public function actionAddThread()
	{
		$response = parent::actionAddThread();
		
		if (!isset($response->redirectTarget)) return $response;
		
	  	preg_match("/.*?(\\d+)/is", $response->redirectTarget, $matches);
		$thread_id = $matches[1];
		
		$this->_fireSubscribe($thread_id);
		
		return $response;	
	}
	
	public function actionSave()
	{
		$response = parent::actionSave();
		
	  	$thread_id = $this->_input->filterSingle('thread_id', XenForo_Input::UINT);
		
		$this->_fireSubscribe($thread_id);
		
		return $response;
	}
	
	protected function _fireSubscribe($thread_id)
	{
		if (!XenForo_Visitor::getInstance()->hasPermission('forum', 'subscribeUsers'))
		{
			return false;
		}
			
		$input = $this->_input->filter(array(
			'subscribe_users' => XenForo_Input::STRING));
			
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
	
	protected function _checkCanSubscribe($response)
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
	
	/**
	 * @return XenForo_Model_User
	 */
	protected function _getUserModel()
	{
		return $this->getModelFromCache('XenForo_Model_User');
	}
}
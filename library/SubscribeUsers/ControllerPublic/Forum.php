<?php

class SubscribeUsers_ControllerPublic_Forum extends XFCP_SubscribeUsers_ControllerPublic_Forum
{
	public function actionCreateThread()
	{
		$response = parent::actionCreateThread();
		
		if (isset($response->params))
		{
			$response->params += array('canSubscribeUsers' => $this->_getThreadModel()->canSubscribeUsers());
		}
		
		return $response;
	}
	
	public function actionAddThread()
	{
		$response = parent::actionAddThread();
		
		$subscribeModel = $this->_getThreadModel();
		
		if (isset($response->redirectTarget) AND $subscribeModel->canSubscribeUsers())
		{		
	  		if (preg_match("/.*?(\\d+)/is", $response->redirectTarget, $matches))
			{
				$threadId = $matches[1];
		
				$users = $this->_input->filterSingle('subscribe_users', XenForo_Input::STRING);
		
				$subscribeModel->subscribeUsersToThreadById($threadId, $users);
			}
		}
		
		return $response;	
	}
}
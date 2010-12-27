<?php

class SubscribeUsers_ControllerPublic_Forum extends XFCP_SubscribeUsers_ControllerPublic_Forum
{
	
	public function actionCreateThread()
	{
		$response = parent::actionCreateThread();
		
		return $this->getSubscribeModel()->checkCanSubscribe($response);
	}
	
	public function actionAddThread()
	{
		$response = parent::actionAddThread();
		
		if (!isset($response->redirectTarget)) return $response;
		
	  	preg_match("/.*?(\\d+)/is", $response->redirectTarget, $matches);
		$thread_id = $matches[1];
		
		$input = $this->_input->filter(array(
			'subscribe_users' => XenForo_Input::STRING
		));
		
		$this->getSubscribeModel()->fireSubscribe($thread_id, $input);
		
		return $response;	
	}
	
	protected function getSubscribeModel()
	{
		return $this->getModelFromCache('SubscribeUsers_Model_Subscribe');
	}
}
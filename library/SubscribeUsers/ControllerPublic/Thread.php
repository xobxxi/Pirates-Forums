<?php

class SubscribeUsers_ControllerPublic_Thread extends XFCP_SubscribeUsers_ControllerPublic_Thread
{
	
	public function actionIndex()
	{
		$response = parent::actionIndex();

		if (is_a($response, 'XenForo_ControllerResponse_View'))
		{
			$canViewSubscribed = false;
			$visitor = XenForo_Visitor::getInstance();
			if ($visitor['is_admin']) $canViewSubscribed = true;
		
			$response->params += array('canViewSubscribed' => $canViewSubscribed);
		}
		
		return $response;
	}
		
	public function actionEdit()
	{
		$response = parent::actionEdit();
		
		return $this->getSubscribeModel()->checkCanSubscribe($response);
	}
	
	public function actionSave()
	{
		$response = parent::actionSave();
		
	  	$thread_id = $this->_input->filterSingle('thread_id', XenForo_Input::UINT);
		
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
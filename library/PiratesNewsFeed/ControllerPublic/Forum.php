<?php

class PiratesNewsFeed_ControllerPublic_Forum extends XFCP_PiratesNewsFeed_ControllerPublic_Forum
{
	public function actionIndex()
	{
		$response = parent::actionIndex();
		
		if ($forumId = $response->params['forum']['node_id'])
		{
			$options = XenForo_Application::get('options');
			$newsForumId = $options->piratesNewsFeed_forumId;
			if ($forumId == $newsForumId)
			{
				if ($this->_getPiratesNewsFeedModel()->canManageNews())
				{
					$response->params += array(
						'showNewsManager' => true
					);
				}
			}
		}
		
		return $response;
	}
	
	protected function _getPiratesNewsFeedModel()
	{
		return $this->getModelFromCache('PiratesNewsFeed_Model_PiratesNewsFeed');
	}
}
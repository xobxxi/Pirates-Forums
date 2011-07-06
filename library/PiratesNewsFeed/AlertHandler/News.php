<?php

class PiratesNewsFeed_AlertHandler_News extends XenForo_AlertHandler_Abstract
{
	protected $_piratesNewsFeedModel = null;
	
	public function getContentByIds(array $contentIds, $model, $userId, array $viewingUser)
	{
		 return $model->getModelFromCache('PiratesNewsFeed_Model_PiratesNewsFeed')->getNews();
	}
	
	public function canViewAlert(array $alert, $content, array $viewingUser)
	{
		$piratesNewsFeedModel = $this->_getPiratesNewsFeedModel();
		
		if (!$piratesNewsFeedModel->canManageNews($viewingUser))
		{
			return false;
		}
		
		return true;
	}
	
	protected function _getPiratesNewsFeedModel()
	{
		if (!$this->_piratesNewsFeedModel)
		{
			$this->_piratesNewsFeedModel = XenForo_Model::create('PiratesNewsFeed_Model_PiratesNewsFeed');
		}

		return $this->_piratesNewsFeedModel;
	}
}
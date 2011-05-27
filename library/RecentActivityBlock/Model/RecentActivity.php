<?php

class RecentActivityBlock_Model_RecentActivity extends XenForo_Model
{
	public function getRecentActivity()
	{
		$options = XenForo_Application::get('options');
		
		if (!$options->enableNewsFeed)
		{
			return false;
		}

		$newsFeed = $this->_getNewsFeedModel()->getNewsFeed(array(), 0);
		
		$i = 1;
		$activity = array();
		foreach ($newsFeed['newsFeed'] as $item)
		{
			if ($i > $options->recentActivity_max)
			{
				break;
			}
			
			$activity[] = $item;
			$i++;
		}
		
		return array('newsFeed' => $activity);
	}
	
	protected function _getNewsFeedModel()
	{
		return $this->getModelFromCache('XenForo_Model_NewsFeed');
	}
}
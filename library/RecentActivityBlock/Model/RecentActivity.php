<?php

class RecentActivityBlock_Model_RecentActivity extends XenForo_Model
{	
	/**
	 * Gets the global news feed
	 */
	public function getRecentActivity()
	{
		if(!$this->_checkNewsFeedEnabled()) return false;
		
		$options = XenForo_Application::get('options');

		$newsFeed = $this->getModelFromCache('XenForo_Model_NewsFeed')->getNewsFeed(array(), 0);
		
		$i = 1;
		foreach ($newsFeed['newsFeed'] as $item)
		{
			if ($i <= $options->recentActivity_max) $activity[] = $item;
			$i++;
		}
		
		//var_dump($activity);
		
		if (!empty($activity)) $params = array('newsFeed' => $activity);
		return $params;
	}

	/**
	 * Check if the news feed is available
	 */
	protected function _checkNewsFeedEnabled()
	{
		if (!XenForo_Application::get('options')->enableNewsFeed) return false;
		
		return true;
	}
}
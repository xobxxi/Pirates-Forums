<?php

class RecentActivityBlock_Listener
{
	public static function templateCreate(&$name, array &$params, XenForo_Template_Abstract $template)
	{
		switch ($name)
		{
			case 'forum_list':
				$template->preloadTemplate('sidebar_recent_activity');
				break;
		}
	}
	
	public static function templateHook($name, &$contents, array $params, XenForo_Template_Abstract $template)
	{
		switch ($name)
		{
			case 'forum_list_sidebar':
				$recentActivity = XenForo_Model::create('RecentActivityBlock_Model_RecentActivity');
				$activity       = $recentActivity->getRecentActivity();
				if (empty($activity)) return $contents;
				$params        += $activity;
				$search         = '<!-- block: forum_stats -->';
				$replace        = $template->create('sidebar_recent_activity', $params)->render();
				$contents       = str_replace($search, $replace . $search, $contents);
			return $contents;
		}
	}
}
<?php

class RecentActivityBlock_Listener
{
	public static function templateCreate(&$templateName, array &$params, XenForo_Template_Abstract $template)
	{
		switch ($templateName)
		{
			case 'forum_list':
				$template->preloadTemplate('sidebar_recent_activity');
				break;
		}
	}
	
	public static function templateHook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
	{
		switch ($hookName)
		{
			case 'forum_list_sidebar':
				$recentActivity = XenForo_Model::create('RecentActivityBlock_Model_RecentActivity');
				$activity       = $recentActivity->getRecentActivity();
				if (empty($activity)) return $contents;
				$hookParams    += $activity;
				$search         = '<!-- block: forum_stats -->';
				$replace        = $template->create('sidebar_recent_activity', $hookParams)->render();
				$contents       = str_replace($search, $replace . $search, $contents);
			return $contents;
		}
	}
}
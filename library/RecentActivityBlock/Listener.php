<?php

class RecentActivityBlock_Listener
{

	public static function template_hook($name, &$contents, array $params, XenForo_Template_Abstract $template)
	{
		switch ($name)
		{
			case 'forum_list_sidebar':
				// Get recent activity
				$recentActivity = XenForo_Model::create('RecentActivityBlock_Model_RecentActivity');
				$params         = $recentActivity->getRecentActivity();
				if (empty($params)) return $contents;
				$search         = '<!-- block: forum_stats -->';
				$replace        = $template->create('sidebar_recent_activity', $params)->render();
				$contents       = str_replace($search, $replace . $search, $contents);
			return $contents;
		}
	}
}
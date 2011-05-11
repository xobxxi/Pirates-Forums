<?php

class Album_Listener
{
	public static function templateCreate(&$templateName, array &$params, XenForo_Template_Abstract $template)
	{
		switch ($templateName)
		{
			case 'member_view':
				$template->preloadTemplate('album_profile_tab');
				$template->preloadTemplate('album_profile_tab_content');
				break;
			case 'forum_list':
				$template->preloadTemplate('album_recent_activity_block_items');
				break;
		}
	}
	
	public static function templateHook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
	{
		switch ($hookName)
		{
			case 'member_view_tabs_heading':
				$permissions = XenForo_Model::create('Album_Model_Album')->getPermissions();
				
				if ($permissions['view'])
				{
					$contents .= $template->create('album_profile_tab', $template->getParams())->render();
				}
				
				return $contents;
			case 'member_view_tabs_content':
				$permissions = XenForo_Model::create('Album_Model_Album')->getPermissions();;
				
				if ($permissions['view'])
				{
					$contents .= $template->create('album_profile_tab_content', $hookParams)->render();
				}
				
				return $contents;
			case 'recentActivityBlock_items':
				$contents .= $template->create('album_recent_activity_block_items', $hookParams)->render();
				return $contents;
		}
	}
}
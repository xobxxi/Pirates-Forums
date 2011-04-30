<?php

class Album_Listener
{
	public static function templateCreate(&$templateName, array &$params, XenForo_Template_Abstract $template)
	{
		switch ($templateName)
		{
			case 'member_view':
				$template->preloadTemplate('pirateProfile_profile_tab');
				$template->preloadTemplate('pirateProfile_profile_tab_content');
				break;
		}
	}
	
	public static function templateHook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
	{
		switch ($hookName)
		{
			case 'member_view_tabs_heading':
				$contents .= $template->create('album_profile_tab', $template->getParams())->render();
				return $contents;
			case 'member_view_tabs_content':
				$contents .= $template->create('album_profile_tab_content', $hookParams)->render();
				return $contents;
		}
	}
}
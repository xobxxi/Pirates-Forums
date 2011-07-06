<?php

class PollsList_Listener
{
	public static function templateCreate(&$templateName, array &$params, XenForo_Template_Abstract $template)
	{
		switch ($templateName)
		{
			case 'PAGE_CONTAINER':
				$template->preloadTemplate('pollsList_navigation_list_item');
				break;
		}
	}
	
	public static function templateHook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
	{
		switch ($hookName)
		{
			case 'navigation_tabs_forums':
				$template = $template->create('pollsList_navigation_list_item', $hookParams)->render();
				$contents = $template . $contents;
				break;
		}
	}
	
	public static function fileHealthCheck(XenForo_ControllerAdmin_Abstract $controller, array &$hashes)
	{
	    $hashes += PollsList_FileSums::getHashes();
	}
}
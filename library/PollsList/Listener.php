<?php

class PollsList_Listener
{
	public static function templateCreate(&$name, array &$params, XenForo_Template_Abstract $template)
	{
		switch ($name)
		{
			case 'PAGE_CONTAINER':
				$template->preloadTemplate('pollsList_navigation_list_item');
				break;
		}
	}
	
	public static function templateHook($name, &$contents, array $params, XenForo_Template_Abstract $template)
	{
		switch ($name)
		{
			case 'navigation_tabs_forums':
				$template = $template->create('pollsList_navigation_list_item', $params)->render();
				$contents = $template . $contents;
				return $contents;
		}
	}
}
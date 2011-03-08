<?php

class GoogleAdsense_Listener
{
	public static function templateCreate(&$name, array &$params, XenForo_Template_Abstract $template)
	{
		switch ($name)
		{
			case 'forum_list':
				$template->preloadTemplate('googleAdsense_rightbar');
				break;
			case 'PAGE_CONTAINER':
				$template->preloadTemplate('googleAdsense_footer');
				break;
		}
	}

	public static function templateHook($name, &$contents, array $params, XenForo_Template_Abstract $template)
	{
		switch ($name)
		{
			case 'forum_list_sidebar':
				$contents .= $template->create('googleAdsense_rightbar', $params)->render();
				return $contents;
			case 'page_container_breadcrumb_bottom':
				$contents .= $template->create('googleAdsense_footer', $params)->render();
				return $contents;
		}
	}
}
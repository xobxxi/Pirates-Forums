<?php

class PiratesForums_Listener
{
	public static function templateCreate(&$name, array &$params, XenForo_Template_Abstract $template)
	{
		switch ($name)
		{
			case 'PAGE_CONTAINER':
				$template->preloadTemplate('piratesForums_logo_block');
				$template->preloadTemplate('piratesForums_siteStatusMessage');
				$template->preloadTemplate('piratesForums_welcome');
				break;
		}
	}
	
	public static function templateHook($name, &$contents, array $params, XenForo_Template_Abstract $template)
	{
		switch ($name)
		{
			case 'header_logo':
				$search   = 'alt="Pirates of the Caribbean Online - Pirates Forums" ';
				$replace  = $template->create('piratesForums_logo_block', $params)->render();
				$contents = str_replace($search, $search . $replace, $contents);
				return $contents;
			case 'page_container_content_title_bar':
				$contents .= $template->create('piratesForums_siteStatusMessage', $params)->render();
				return $contents;
			case 'page_container_notices':
				$contents .= $template->create('piratesForums_welcome', $params)->render();
				return $contents;
		}
	}
}
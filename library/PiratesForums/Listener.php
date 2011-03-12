<?php

class PiratesForums_Listener
{
	public static function templateCreate(&$templateName, array &$params, XenForo_Template_Abstract $template)
	{
		switch ($templateName)
		{
			case 'PAGE_CONTAINER':
				$template->preloadTemplate('piratesForums_logo_block');
				$template->preloadTemplate('piratesForums_siteStatusMessage');
				$template->preloadTemplate('piratesForums_welcome');
				break;
		}
	}
	
	public static function templateHook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
	{
		switch ($hookName)
		{
			case 'header_logo':
				$search   = 'alt="Pirates of the Caribbean Online - Pirates Forums" ';
				$replace  = $template->create('piratesForums_logo_block', $hookParams)->render();
				$contents = str_replace($search, $search . $replace, $contents);
				return $contents;
			case 'page_container_content_title_bar':
				$contents .= $template->create('piratesForums_siteStatusMessage', $hookParams)->render();
				return $contents;
			case 'page_container_notices':
				$contents .= $template->create('piratesForums_welcome', $template->getParams())->render();
				return $contents;
		}
	}
	
	public static function loadClassDatawriter($class, array &$extend)
	{
		switch ($class)
		{
			case 'XenForo_DataWriter_DiscussionMessage_ProfilePost':
				$extend[] = 'PiratesForums_DataWriter_DiscussionMessage_ProfilePost';
				break;
		}
	}
}
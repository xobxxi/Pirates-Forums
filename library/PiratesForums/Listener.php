<?php

class PiratesForums_Listener
{
	public static function loadClassController($class, array &$extend)
	{
		switch ($class)
		{
			case 'XenForo_ControllerPublic_Member':
				$extend[] = 'PiratesForums_ControllerPublic_Member';
				break;
		}
	}
	
	public static function loadClassDatawriter($class, array &$extend)
	{
		switch ($class)
		{
			case 'XenForo_DataWriter_DiscussionMessage_ProfilePost':
				$extend[] = 'PiratesForums_DataWriter_DiscussionMessage_ProfilePost';
				break;
			case 'XenForo_DataWriter_ReportComment':
				$extend[] = 'PiratesForums_DataWriter_ReportComment';
				break;
		}
	}
	
	public static function loadClassModel($class, array &$extend)
	{
		switch ($class)
		{
			case 'XenForo_Model_Report':
				$extend[] = 'PiratesForums_Model_Report';
				break;
		}
	}
	
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
				break;
			case 'page_container_content_title_bar':
				$contents .= $template->create('piratesForums_siteStatusMessage', $hookParams)->render();
				break;
			case 'page_container_notices':
				$contents .= $template->create('piratesForums_welcome', $template->getParams())->render();
				break;
		}
	}
	
	public static function fileHealthCheck(XenForo_ControllerAdmin_Abstract $controller, array &$hashes)
	{
	    $hashes += PiratesForums_FileSums::getHashes();
	}
}
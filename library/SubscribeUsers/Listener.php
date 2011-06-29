<?php

class SubscribeUsers_Listener
{
	public static function loadClassController($class, array &$extend)
    {
		switch ($class)
		{
			case 'XenForo_ControllerPublic_Forum':
				$extend[] = 'SubscribeUsers_ControllerPublic_Forum';
				break;
			case 'XenForo_ControllerPublic_Thread':
				$extend[] = 'SubscribeUsers_ControllerPublic_Thread';
				break;
		}
    }

	public static function loadClassModel($class, array &$extend)
    {
		switch ($class)
		{
			case 'XenForo_Model_Thread':
				$extend[] = 'SubscribeUsers_Model_Thread';
				break;
		}
    }
	
	public static function templateCreate(&$templateName, array &$params, XenForo_Template_Abstract $template)
	{
		switch ($templateName)
		{
			case 'thread_create':
				$template->preloadTemplate('subscribeUsers_input');
				break;
		}
	}
	
	public static function templateHook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
	{
		switch ($hookName)
		{
			case 'thread_create_fields_main':
				$contents      .= $template->create('subscribeUsers_input', $template->getParams())->render();
				return $contents;
		}
	}
}
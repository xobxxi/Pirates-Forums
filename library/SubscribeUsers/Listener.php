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
	
	public static function templateCreate(&$name, array &$params, XenForo_Template_Abstract $template)
	{
		switch ($name)
		{
			case 'thread_create':
				$template->preloadTemplate('subscribeUsers_input');
				break;
		}
	}
	
	public static function templateHook($name, &$contents, array $params, XenForo_Template_Abstract $template)
	{
		switch ($name)
		{
			case 'thread_create_fields_main':
				$subscribeModel = XenForo_Model::create('SubscribeUsers_Model_Subscribe');
				$params        += $subscribeModel->checkCanSubscribe();
				$contents      .= $template->create('subscribeUsers_input', $params)->render();
				return $contents;
		}
	}
}
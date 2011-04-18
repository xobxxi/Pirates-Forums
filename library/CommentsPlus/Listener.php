<?php

class CommentsPlus_Listener
{
	public static function loadClassController($class, array &$extend)
	{
		switch ($class)
		{
			case 'XenForo_ControllerPublic_ProfilePost':
				$extend[] = 'CommentsPlus_ControllerPublic_ProfilePost';
				break;
			case 'XenForo_ControllerPublic_Member':
				$extend[] = 'CommentsPlus_ControllerPublic_Member';
				break;
		}
	}
	
	public static function loadClassModel($class, array &$extend)
	{
		switch ($class)
		{
			case 'XenForo_Model_ProfilePost':
				$extend[] = 'CommentsPlus_Model_ProfilePost';
				break;
		}
	}
	
	public static function loadClassDataWriter($class, array &$extend)
	{
		switch ($class)
		{
			case 'XenForo_DataWriter_ProfilePostComment':
				$extend[] = 'CommentsPlus_DataWriter_ProfilePostComment';
				break;
		}
	}
	
	public static function templateCreate(&$templateName, array &$params, XenForo_Template_Abstract $template)
	{
		switch ($templateName)
		{
			case 'account_alert_preferences':
				$template->preloadTemplate('commentsPlus_alert_preferences');
				break;
		}
	}
	
	public static function templateHook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
	{
		switch ($hookName)
		{
			case 'account_alerts_messages_on_profile_pages':
				$contents .= $template->create('commentsPlus_alert_preferences', $template->getParams())->render();
				return $contents;
		}
	}
}

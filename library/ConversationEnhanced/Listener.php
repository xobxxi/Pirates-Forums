<?php

class ConversationEnhanced_Listener
{
	public static function loadClassController($class, array &$extend)
	{
		switch ($class)
		{
			case 'XenForo_ControllerPublic_Conversation':
				$extend[] = 'ConversationEnhanced_ControllerPublic_Conversation';
				break;
		}
	}

	public static function loadClassModel($class, array &$extend)
	{
		switch ($class)
		{
			case 'XenForo_Model_Conversation':
				$extend[] = 'ConversationEnhanced_Model_Conversation';
				break;
		}
	}

	public static function loadClassDatawriter($class, array &$extend)
	{
		switch ($class)
		{
			case 'XenForo_DataWriter_ConversationMessage':
				$extend[] = 'ConversationEnhanced_DataWriter_ConversationMessage';
				break;
			case 'XenForo_DataWriter_ConversationMaster':
				$extend[] = 'ConversationEnhanced_DataWriter_ConversationMaster';
				break;
		}
	}

	public static function loadClassView($class, array &$extend)
	{
		switch ($class)
		{
			case 'XenForo_ViewPublic_Conversation_View':
				$extend[] = 'ConversationEnhanced_ViewPublic_Conversation_View';
				break;
			case 'XenForo_ViewPublic_Conversation_ViewMessage':
				$extend[] = 'ConversationEnhanced_ViewPublic_Conversation_ViewMessage';
				break;
			case 'XenForo_ViewPublic_Conversation_ViewNewMessages':
				$extend[] = 'ConversationEnhanced_ViewPublic_Conversation_ViewNewMessages';
				break;
		}
	}
	
	public static function templateCreate(&$templateName, array &$params, XenForo_Template_Abstract $template)
	{
		switch ($templateName)
		{
			case 'conversation_view':
				$template->preloadTemplate('conversationEnhanced_message_control_report');
				break;
		}
	}
	
	public static function templateHook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
	{
		switch ($hookName)
		{
			case 'conversation_message_private_controls':
				$search = "/data-messageselector=\"#message-([0-9]+)\"/is";
				if (preg_match($search, $contents, $match))
				{
					$templateParams = $template->getParams();
					$hookParams['conversation']          = $templateParams['conversation'];
					$hookParams['message']['message_id'] = $match[1];
					$contents .=  $template->create('conversationEnhanced_message_control_report', $hookParams)->render();
				}
				return $contents;
		}
	}
}
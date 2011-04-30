<?php

class ConversationAttachments_Listener
{
	public static function loadClassController($class, array &$extend)
	{
		switch ($class)
		{
			case 'XenForo_ControllerPublic_Conversation':
				$extend[] = 'ConversationAttachments_ControllerPublic_Conversation';
				break;
		}
	}

	public static function loadClassModel($class, array &$extend)
	{
		switch ($class)
		{
			case 'XenForo_Model_Conversation':
				$extend[] = 'ConversationAttachments_Model_Conversation';
				break;
		}
	}

	public static function loadClassDatawriter($class, array &$extend)
	{
		switch ($class)
		{
			case 'XenForo_DataWriter_ConversationMessage':
				$extend[] = 'ConversationAttachments_DataWriter_ConversationMessage';
				break;
			case 'XenForo_DataWriter_ConversationMaster':
				$extend[] = 'ConversationAttachments_DataWriter_ConversationMaster';
				break;
		}
	}

        public static function loadClassView($class, array &$extend)
        {
            switch ($class)
            {
                case 'XenForo_ViewPublic_Conversation_View':
                    $extend[] = 'ConversationAttachments_ViewPublic_Conversation_View';
                    break;
				case 'XenForo_ViewPublic_Conversation_ViewMessage':
		    		$extend[] = 'ConversationAttachments_ViewPublic_Conversation_ViewMessage';
		    		break;
				case 'XenForo_ViewPublic_Conversation_ViewNewMessages':
		    		$extend[] = 'ConversationAttachments_ViewPublic_Conversation_ViewNewMessages';
		    		break;
            }
        }
}
<?php
/**
 * Report Conversations listener for load_class_controller code event.
 *
 * @package ReportConversations
 */

class ReportConversations_Listener_LoadClassController
{
	/**
	 * Initialise the code event
	 *
	 * @param string The name of the class to be created
	 * @param array A modifiable list of classes that wish to extend the class.
	 *
	 * @return void
	 */
	public static function init($class, array &$extend)
	{
		new self($class, $extend);
	}

	/**
	 * Construct and execute code event.
	 *
	 * @param string The name of the class to be created
	 * @param array A modifiable list of classes that wish to extend the class.
	 *
	 * @return void
	 */
	protected function __construct($class, array &$extend)
	{
		if ($class == 'XenForo_ControllerPublic_Conversation')
		{
			$this->_extendConversationController($extend);
		}
	}

	/**
	 * Extends XenForo_ControllerPublic_Conversation with Report Conversations functionality.
	 *
	 * @param array A modifiable list of classes that wish to extend the class.
	 *
	 * @return void
	 */
	protected function _extendConversationController(array &$extend)
	{
		$extend[] = 'ReportConversations_XFCP_ControllerPublic_Conversation';
	}
}

<?php

class PiratesNewsFeed_Model_PiratesNewsFeed  extends XenForo_Model {

	
	public function getUpdates($thread_id, $input)
	{
		//Q: how to set these permissions?.
		
		//the members that have access to this forum are news reporters or admins/mods
		if (!XenForo_Visitor::getInstance()->hasPermission('forum', 'check4Updates')) {
			return array();
		}
		
		//Make a remote call to get the list
		
		//check news date
		
		//get setting which tells the lastest date of new lastest news on the boards.
		
		//compare news dates or titles, etc..
		
		//filter out any existing news
		
		//list the newest news on overlay
	
	}
	
	
	
	/**
	 * @return XenForo_Model_User
	 */
	protected function _getUserModel()
	{
		return $this->getModelFromCache('XenForo_Model_User');
	}
}
<?php

class PollsList_ControllerPublic_Polls extends XenForo_ControllerPublic_Abstract
{
	public function actionIndex()
	{
		$this->canonicalizeRequestUrl(XenForo_Link::buildPublicLink('polls'));
		
		$visitor = XenForo_Visitor::getInstance();

		$ftpHelper = $this->getHelper('ForumThreadPost');
		$threadFetchOptions = array('readUserId' => $visitor['user_id'], 'watchUserId' => $visitor['user_id']);
		$forumFetchOptions  = array('readUserId' => $visitor['user_id']);
		
		$max = XenForo_Application::get('options')->pollsList_max;
		
		$polls  = $this->_getPollsModel()->getRecentPolls($max);
		$finals = array();
				
		foreach ($polls as $poll) {
			try {
				list($thread, $forum) = $ftpHelper->assertThreadValidAndViewable($poll['content_id'], $threadFetchOptions, $forumFetchOptions);

				$poll['userInfo'] = $this->_getUserModel()->getUserById($thread['user_id']);
				
				$finals[] = array_merge_recursive($poll, $thread);
			} catch (XenForo_ControllerResponse_Exception $e) {}
		}
		
		$pollsTotal = count($finals);
		$viewParams = array(
			'polls'      => $finals,
			'pollsTotal' => $pollsTotal
		);
		
		return $this->responseView('PollsList_ViewPublic_Polls', 'pollsList_list', $viewParams);
	}
	
	public static function getSessionActivityDetailsForList(array $activities)
	{
		return new XenForo_Phrase('pollsList_viewing_recent_polls');
	}
	
	protected function _getPollsModel()
	{
		return $this->getModelFromCache('PollsList_Model_Poll');
	}
	
	protected function _getThreadModel()
	{
		return $this->getModelFromCache('XenForo_Model_Thread');
	}
	
	protected function _getUserModel()
	{
		return $this->getModelFromCache('XenForo_Model_User');
	}
}
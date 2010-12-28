<?php

class SubscribeUsers_ControllerPublic_Subscribed extends XenForo_ControllerPublic_Abstract
{
	
	public function actionIndex()
	{
		$visitor = XenForo_Visitor::getInstance();
		if (!$visitor['is_admin']) throw $this->getNoPermissionResponseException();
		
		$threadId        = $this->_input->filterSingle('thread_id', XenForo_Input::UINT);
		
		$ftpHelper = $this->getHelper('ForumThreadPost');
		$threadFetchOptions = array('readUserId' => $visitor['user_id'], 'watchUserId' => $visitor['user_id']);
		$forumFetchOptions = array('readUserId' => $visitor['user_id']);
		list($thread, $forum) = $ftpHelper->assertThreadValidAndViewable($threadId, $threadFetchOptions, $forumFetchOptions);
		
		if (empty($thread)) 
		{
			throw $this->responseException($this->responseError(
				new XenForo_Phrase('requested_thread_not_found'), 404)
			);
		}
		
		$this->canonicalizeRequestUrl(
			XenForo_Link::buildPublicLink('subscribed', $thread)
		);
		
		$subscribedUsers = $this->getModelFromCache('SubscribeUsers_Model_Subscribe')
		                        ->getSubscribedUsers($threadId);
		
		$viewParams = array(
			'nodeBreadCrumbs' => $ftpHelper->getNodeBreadCrumbs($forum),
			'thread'          => $thread,
			'subscribedUsers' => $subscribedUsers
		);
		return $this->responseView(
			'SubscribeUsers_ViewPublic_Subscribed', 'subscribeUsers_subscribed_users', $viewParams
		);
	}
}
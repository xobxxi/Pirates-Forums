<?php

class SubscribeUsers_ControllerPublic_Thread extends XFCP_SubscribeUsers_ControllerPublic_Thread
{
	public function actionIndex()
	{
		$response = parent::actionIndex();

		if (isset($response->params))
		{
			$response->params += array('canSubscribeUsers' => $this->_getThreadModel()->canSubscribeUsers());
		}
		
		return $response;
	}
		
	public function actionEdit()
	{
		$response = parent::actionEdit();
		
		if (isset($response->params))
		{
			$response->params += array('canSubscribeUsers' => $this->_getThreadModel()->canSubscribeUsers());
		}
		
		return $response;
	}
	
	public function actionSave()
	{
		$response = parent::actionSave();
		
		$subscribeModel = $this->_getThreadModel();
		
		if ($subscribeModel->canSubscribeUsers())
		{
	  		$threadId = $this->_input->filterSingle('thread_id', XenForo_Input::UINT);
		
			$users = $this->_input->filterSingle('subscribe_users', XenForo_Input::STRING);
			
			$subscribeModel->subscribeUsersToThreadById($threadId, $users);
		}
		
		return $response;
	}
	
	public function actionViewSubscribed()
	{	
		if (!$this->_getThreadModel()->canSubscribeUsers())
		{
			throw $this->getNoPermissionResponseException();
		}
		
		$threadId = $this->_input->filterSingle('thread_id', XenForo_Input::UINT);
		$visitorId = XenForo_Visitor::getUserId();
		
		$ftpHelper = $this->getHelper('ForumThreadPost');
		$threadFetchOptions = array('readUserId' => $visitorId, 'watchUserId' => $visitorId);
		$forumFetchOptions  = array('readUserId' => $visitorId);
		list($thread, $forum) = $ftpHelper->assertThreadValidAndViewable($threadId, $threadFetchOptions, $forumFetchOptions);
		
		$this->canonicalizeRequestUrl(
			XenForo_Link::buildPublicLink('threads/view-subscribed', $thread)
		);
		
		$subscribedUsers = $this->_getThreadWatchModel()->getUsersWatchingThread($thread['thread_id'], $forum['node_id']);
		
		$viewParams = array(
			'nodeBreadCrumbs' => $ftpHelper->getNodeBreadCrumbs($forum),
			'thread'          => $thread,
			'subscribedUsers' => $subscribedUsers
		);
		
		return $this->responseView(
			'SubscribeUsers_ViewPublic_Subscribed',
			'subscribeUsers_subscribed_users',
			$viewParams
		);
	}
}
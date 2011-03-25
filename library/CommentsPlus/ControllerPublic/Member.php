<?php

class CommentsPlus_ControllerPublic_Member extends XFCP_CommentsPlus_ControllerPublic_Member
{
	public function actionMember()
	{
		$response = parent::actionMember();
		
		$profilePosts = $response->params['profilePosts'];
		
		$profilePosts = $this->_getProfilePostModel()->addProfilePostCommentsToProfilePosts($profilePosts, array(
			'join' => XenForo_Model_ProfilePost::FETCH_COMMENT_USER,
			'likeUserId' => XenForo_Visitor::getUserId()
		));
		
		$response->params['profilePosts'] = $profilePosts;
		
		return $response;
	}
}
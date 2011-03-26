<?php

class CommentsPlus_ControllerPublic_Member extends XFCP_CommentsPlus_ControllerPublic_Member
{
	public function actionMember()
	{
		$response = parent::actionMember();
		
		if (!empty($response->params['profilePosts']))
		{
			$userId = $this->_input->filterSingle('user_id', XenForo_Input::UINT);
			$userFetchOptions = array(
				'join' => XenForo_Model_User::FETCH_LAST_ACTIVITY
			);
			$user = $this->getHelper('UserProfile')->assertUserProfileValidAndViewable($userId, $userFetchOptions);
		
			$profilePosts = $response->params['profilePosts'];
		
			$profilePosts = $this->_getProfilePostModel()->addProfilePostCommentsToProfilePosts($profilePosts, array(
				'join' => XenForo_Model_ProfilePost::FETCH_COMMENT_USER,
				'likeUserId' => XenForo_Visitor::getUserId()
			));
		}
		
		foreach ($profilePosts AS &$profilePost)
		{
			if (empty($profilePost['comments']))
			{
				continue;
			}

			foreach ($profilePost['comments'] AS &$comment)
			{
				$comment = $this->_getProfilePostModel()->prepareProfilePostComment($comment, $profilePost, $user);
			}
		}
		
		$response->params['profilePosts'] = $profilePosts;
		
		return $response;
	}
}
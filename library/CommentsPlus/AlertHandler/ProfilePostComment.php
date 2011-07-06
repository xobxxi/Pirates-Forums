<?php

class CommentsPlus_AlertHandler_ProfilePostComment extends XenForo_AlertHandler_Abstract
{
	public function getContentByIds(array $contentIds, $model, $userId, array $viewingUser)
	{
		$profilePostModel = $model->getModelFromCache('XenForo_Model_ProfilePost');
		
		$comments = $profilePostModel->getProfilePostCommentsByIds(
			$contentIds, array('join' => XenForo_Model_ProfilePost::FETCH_COMMENT_USER)
		);
		
		$profilePostIds = array();
		foreach ($comments as $comment)
		{
			$profilePostIds[] = $comment['profile_post_id'];
		}
		
		$profilePosts = $profilePostModel->getProfilePostsByIds($profilePostIds);
		
		foreach ($comments as &$comment)
		{
			$comment['profile_post'] = $profilePosts[$comment['profile_post_id']];
		}
		
		return $comments;
	}
}
<?php

class CommentsPlus_LikeHandler_ProfilePostComment extends XenForo_LikeHandler_Abstract
{
	public function incrementLikeCounter($contentId, array $latestLikes, $adjustAmount = 1)
	{
		$dw = XenForo_DataWriter::create('XenForo_DataWriter_ProfilePostComment');
		$dw->setExistingData($contentId);
		$dw->set('likes', $dw->get('likes') + $adjustAmount);
		$dw->set('like_users', $latestLikes);
		$dw->save();
	}

	public function getContentData(array $contentIds, array $viewingUser)
	{
		$profilePostModel = XenForo_Model::create('XenForo_Model_ProfilePost');
	
		$comments = $profilePostModel->getProfilePostCommentsByIds($contentIds);
		
		return $comments; 
	}

	public function getListTemplateName()
	{
		return false;
	}
}
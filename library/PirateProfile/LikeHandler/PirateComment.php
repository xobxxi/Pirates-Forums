<?php

class PirateProfile_LikeHandler_PirateComment extends XenForo_LikeHandler_Abstract
{
	public function incrementLikeCounter($contentId, array $latestLikes, $adjustAmount = 1)
	{
		$dw = XenForo_DataWriter::create('PirateProfile_DataWriter_PirateComment');
		$dw->setExistingData($contentId);
		$dw->set('likes', $dw->get('likes') + $adjustAmount);
		$dw->set('like_users', $latestLikes);
		$dw->save();
	}

	public function getContentData(array $contentIds, array $viewingUser)
	{
		$pirateModel = XenForo_Model::create('PirateProfile_Model_Pirate');
		
		$permissions = $pirateModel->getPermissions($viewingUser);
		if (!$permissions['view'])
		{
			return false;
		}
		
		return $pirateModel->getPirateCommentsByIds($contentIds);
	}

	public function getListTemplateName()
	{
		return false;
	}
}
<?php

class PirateProfile_LikeHandler_Pirate extends XenForo_LikeHandler_Abstract
{
	public function incrementLikeCounter($contentId, array $latestLikes, $adjustAmount = 1)
	{
		$dw = XenForo_DataWriter::create('PirateProfile_DataWriter_Pirate');
		$dw->setExistingData($contentId);
		$dw->set('likes', $dw->get('likes') + $adjustAmount);
		$dw->set('like_users', $latestLikes);
		$dw->save();
	}

	public function getContentData(array $contentIds, array $viewingUser)
	{
		$pirateModel = XenForo_Model::create('XenForo_Model_Pirate');
		
		$permissions = $pirateModel->getPermissions($viewingUser);
		if (!$permissions['canView']) return $pirates;
	
		$pirates = $pirateModel->getPiratesByIds($contentIds);
		
		return $pirates; 
	}

	public function getListTemplateName()
	{
		return 'news_feed_item_pirate_like';
	}
}
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
		
		$pirates = array();
		$pirateModel = XenForo_Model::create('XenForo_Model_ProfilePost');
		
		$permissions = $pirateModel->getPermissions($viewingUser);
		if (!$permissions['view']) return $pirates;
	
		foreach ($contentIds as $contentId)
		{
			if (!isset($pirates[$contentId]))
			{
				$pirate = $pirateModel->getPirateById($contentId);
				
				if (!empty($pirate)) $pirates[$contentId] = $pirate;
			}
		}
		
		return $pirates; 
	}

	public function getListTemplateName()
	{
		return 'news_feed_item_pirate_like';
	}
}
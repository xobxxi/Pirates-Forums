<?php

class PirateProfile_AlertHandler_PirateComment extends XenForo_AlertHandler_Abstract
{
	public function getContentByIds(array $contentIds, $model, $userId, array $viewingUser)
	{
		$pirateModel = $model->getModelFromCache('PirateProfile_Model_Pirate');
		
		$permissions = $pirateModel->getPermissions();
		if (!$permissions['view'])
		{
			return false;
		}
		
		$comments = $pirateModel->getPirateCommentsByIds(
			$contentIds, array('join' => PirateProfile_Model_Pirate::FETCH_COMMENT_USER)
		);
		
		$pirateIds = array();
		foreach ($comments as $comment)
		{
			$pirateIds[] = $comment['pirate_id'];
		}
		
		$pirates = $pirateModel->getPiratesByIds($pirateIds);
		
		$userIds = array();
		foreach ($pirates as $pirate)
		{
			$userIds[] = $pirate['user_id'];
		}
		
		$users = $model->getModelFromCache('XenForo_Model_User')->getUsersByIds($userIds);
		
		foreach ($pirates as &$pirate)
		{
			$pirate['user'] = $users[$pirate['user_id']];
		}
		
		foreach ($comments as &$comment)
		{
			$comment['pirate'] = $pirates[$comment['pirate_id']];
		}
		
		return $comments;
	}
}
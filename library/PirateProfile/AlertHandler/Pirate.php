<?php

class PirateProfile_AlertHandler_Pirate extends XenForo_AlertHandler_Abstract
{
	public function getContentByIds(array $contentIds, $model, $userId, array $viewingUser)
	{
		$pirateModel = $model->getModelFromCache('PirateProfile_Model_Pirate');
		
		$permissions = $pirateModel->getPermissions();
		if (!$permissions['view'])
		{
			return false;
		}
		
		return $pirateModel->getPiratesByIds(
			$contentIds, array('join' => PirateProfile_Model_Pirate::FETCH_PIRATE_USER)
		);
	}
}
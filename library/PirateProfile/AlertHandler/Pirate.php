<?php

class PirateProfile_AlertHandler_Pirate extends XenForo_AlertHandler_Abstract
{
	public function getContentByIds(array $contentIds, $model, $userId, array $viewingUser)
	{
		return $model->getModelFromCache('PirateProfile_Model_Pirate')->getPiratesByIds($contentIds);
	}
}
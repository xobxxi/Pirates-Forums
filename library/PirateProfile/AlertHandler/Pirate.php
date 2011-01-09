<?php

class PirateProfile_AlertHandler_Pirate extends XenForo_AlertHandler_Abstract
{
	
	public function getContentByIds(array $contentIds, $model, $userId, array $viewingUser)
	{
		$pirates = array();
		
		$permissions = $model->getModelFromCache('PirateProfile_Model_Pirate')
		                     ->getPermissions($viewingUser);
		if (!$permissions['view']) return $pirates;
		
		foreach ($contentIds as $contentId)
		{
			if (!isset($pirates[$contentId]))
			{
				$pirate = $model->getModelFromCache('PirateProfile_Model_Pirate')
				                ->getPirateById($contentId);
				
				if (!empty($pirate)) $pirates[$contentId] = $pirate;
			}
		}
		
		return $pirates;
	}
}
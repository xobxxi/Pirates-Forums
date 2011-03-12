<?php

class PiratesForums_AlertHandler_Report extends XenForo_AlertHandler_Abstract
{
	public function getContentByIds(array $contentIds, $model, $userId, array $viewingUser)
	{
		 return $model->getModelFromCache('XenForo_Model_Report')->getReportsByIds($contentIds);
	}
	
	public function canViewAlert(array $alert, $content, array $viewingUser)
	{
		return true;
	}
}
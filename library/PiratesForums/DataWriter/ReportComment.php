<?php

class PiratesForums_DataWriter_ReportComment extends XFCP_PiratesForums_DataWriter_ReportComment
{
	protected function _postSave()
	{
		parent::_postSave();
		
		$reportId = $this->get('report_id');
		
		$reportModel = $this->_getReportModel();
		
		$otherCommenterIds = $reportModel->getReportCommentUserIds($reportId);

		$otherCommenters = $this->_getUserModel()->getUsersByIds($otherCommenterIds, array(
			'join' => XenForo_Model_User::FETCH_USER_PERMISSIONS
		));

		foreach ($otherCommenters AS $otherCommenter)
		{
			if ($otherCommenter['user_id'] == $this->get('user_id'))
			{
				continue;
			}
			
			if ($otherCommenter['is_moderator'])
			{
				$otherCommenter['permissions'] = unserialize($otherCommenter['global_permission_cache']);
				
				$report = $reportModel->getReportById($reportId);
				$handler = $reportModel->getReportHandler($report['content_type']);
				$reports = $handler->getVisibleReportsForUser(array($reportId => $report), $otherCommenter);
				
				if (!empty($reports))
				{
					XenForo_Model_Alert::alert(
						$otherCommenter['user_id'],
						$this->get('user_id'),
						$this->get('username'),
						'report',
						$reportId,
						'comment'
					);
				}
			}
		}
	}
}
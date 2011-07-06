<?php

class PiratesForums_Model_Report extends XFCP_PiratesForums_Model_Report
{
	public function getReportsByIds($ids)
	{
		return $this->fetchAllKeyed('
			SELECT report.*,
				user.*,
				assigned.username AS assigned_username
			FROM xf_report AS report
			LEFT JOIN xf_user AS assigned ON (assigned.user_id = report.assigned_user_id)
			LEFT JOIN xf_user AS user ON (user.user_id = report.content_user_id)
			WHERE report.report_id IN (' . $this->_getDb()->quote($ids) . ')
		', 'report_id');
	}
	
	public function getReportCommentUserIds($reportId)
	{
		return $this->_getDb()->fetchCol('
			SELECT DISTINCT user_id
			FROM xf_report_comment
			WHERE report_id = ?
		', $reportId);
	}
}
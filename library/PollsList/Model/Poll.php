<?php

class PollsList_Model_Poll extends XenForo_Model
{

	public function getRecentPolls($max = 10)
	{
		$polls = $this->_getDb()->fetchAll("
			SELECT *
			FROM xf_poll
			ORDER BY poll_id DESC
			LIMIT {$max}
		");
		
		if (empty($polls)) return false;
		
		return $polls;
	}
}

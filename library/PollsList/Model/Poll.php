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
	
	/* sloppy code */
	public function cleanUpTermsPages()
	{
		$termsPages = $this->_getDb()->fetchAll('
			SELECT *
			FROM dark_azucloud_terms_pages
		');
		
		$ids = array();
		foreach ($termsPages as $termPage)
		{
			$ids[] = $termPage['term_id'];
		}
		
		if (empty($ids)) return true;
		
		$terms = $this->fetchAllKeyed('
			SELECT *
			FROM dark_azucloud_terms
			WHERE dark_azucloud_terms.id IN (' . $this->_getDb()->quote($ids) . ')
		', 'id');
		
		foreach ($termsPages as $termPage)
		{
			if (!isset($terms[$termPage['term_id']]))
			{
				$this->_getDb()->query('
					DELETE FROM dark_azucloud_terms_pages
					WHERE dark_azucloud_terms_pages.term_id = ?
				', $termPage['term_id']);
			}
		}
		
		return true;
	}
}

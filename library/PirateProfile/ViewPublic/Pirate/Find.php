<?php

class PirateProfile_ViewPublic_Pirate_Find extends XenForo_ViewPublic_Base
{
	public function renderJson()
	{
		$results = array();
		foreach ($this->_params['pirates'] AS $pirate)
		{
			$results[$pirate['name']] = htmlspecialchars($pirate['name']);
		}

		return array(
			'results' => $results
		);
	}
}
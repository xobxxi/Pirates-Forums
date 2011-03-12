<?php

class PirateProfile_ViewPublic_Pirate_Find extends XenForo_ViewPublic_Base
{
	public function renderJson()
	{
		if (is_array($this->_params['pirates']))
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
		
		return false;
	}
}
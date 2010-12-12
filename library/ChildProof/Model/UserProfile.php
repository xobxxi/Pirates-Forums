<?php

class ChildProof_Model_UserProfile extends XFCP_ChildProof_Model_UserProfile
{
	
	public function getUserBirthdayDetails(array $user, $force = false)
	{
		$user['show_dob_year'] = 0;
		
		return parent::getUserBirthdayDetails($user, $force);
	}
}

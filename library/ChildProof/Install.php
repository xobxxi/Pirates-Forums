<?php

class ChildProof_Install
{
	public static function install()
	{
		$database = XenForo_Application::get('db');
		
		$database->query("UPDATE  `xf_user_option` SET  `show_dob_year` =  '0'");
		
		$database->query("UPDATE `xf_user_profile` SET `homepage` = '', `location` = '', 
			`occupation` = ''");
		
		return true;
	}
}
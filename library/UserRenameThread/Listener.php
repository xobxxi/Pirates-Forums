<?php

class UserRenameThread_Listener
{
	public static function loadClassModel($class, array &$extend)
	{
		switch ($class)
		{
			case 'XenForo_Model_Thread':
				$extend[] = 'UserRenameThread_Model_Thread';
				break;
		}
	}
	
	public static function fileHealthCheck(XenForo_ControllerAdmin_Abstract $controller, array &$hashes)
	{
	    $hashes += UserRenameThread_FileSums::getHashes();
	}
}
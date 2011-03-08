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
}
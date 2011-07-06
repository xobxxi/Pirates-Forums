<?php

class Welcome_Listener
{
    public static function loadClassController($class, &$extend)
    {
		switch ($class) 
		{
			case 'XenForo_ControllerPublic_Register':
            	$extend[] = 'Welcome_ControllerPublic_Register';
            	break;
		}
    }
        
    public static function fileHealthCheck(XenForo_ControllerAdmin_Abstract $controller, array &$hashes)
	{
	    $hashes += Welcome_FileSums::getHashes();
	}
}
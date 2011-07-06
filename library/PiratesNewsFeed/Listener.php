<?php

class PiratesNewsFeed_Listener
{
	public static function loadClassController($class, array &$extend)
	{
		switch ($class)
		{
			case 'XenForo_ControllerPublic_Forum':
				$extend[] = 'PiratesNewsFeed_ControllerPublic_Forum';
				break;
		}
	}
	
	public static function fileHealthCheck(XenForo_ControllerAdmin_Abstract $controller, array &$hashes)
	{
	    $hashes += PiratesNewsFeed_FileSums::getHashes();
	}
}
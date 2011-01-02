<?php

class SubscribeUsers_Listener
{
	
	public static function loadClassController($class, array &$extend)
    {
		switch ($class)
		{
			case 'XenForo_ControllerPublic_Forum':
				$extend[] = 'SubscribeUsers_ControllerPublic_Forum';
			break;
			case 'XenForo_ControllerPublic_Thread':
				$extend[] = 'SubscribeUsers_ControllerPublic_Thread';
			break;
		}
    }
}
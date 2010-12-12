<?php

class SubscribeUsers_Listener
{
	
	public static function loadClassController($class, array &$extend)
    {
		if (($class == 'XenForo_ControllerPublic_Forum') OR ($class == 'XenForo_ControllerPublic_Thread'))
			$extend[] = 'SubscribeUsers_ControllerPublic_Subscribe';
    }
}
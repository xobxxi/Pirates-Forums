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
}
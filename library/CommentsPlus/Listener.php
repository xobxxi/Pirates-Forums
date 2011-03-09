<?php

class CommentsPlus_Listener
{
	public static function loadClassController($class, array &$extend)
	{
		switch ($class)
		{
			case 'XenForo_ControllerPublic_ProfilePost':
				$extend[] = 'CommentsPlus_ControllerPublic_ProfilePost';
				break;
		}
	}
	
	public static function loadClassModel($class, array &$extend)
	{
		switch ($class)
		{
			case 'XenForo_Model_ProfilePost':
				$extend[] = 'CommentsPlus_Model_ProfilePost';
				break;
		}
	}
	
	public static function loadClassDataWriter()
	{
		
	}
}
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
			case 'XenForo_ControllerPublic_Member':
				$extend[] = 'CommentsPlus_ControllerPublic_Member';
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
	
	public static function loadClassDataWriter($class, array &$extend)
	{
		switch ($class)
		{
			case 'XenForo_DataWriter_ProfilePostComment':
				$extend[] = 'CommentsPlus_DataWriter_ProfilePostComment';
				break;
		}
	}
}
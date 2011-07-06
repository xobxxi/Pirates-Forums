<?php

class Skeleton_Listener
{
	// In the ACP, make a new addon with the id 'skeleton' and name 'Skeleton'
	// Then, create an event listener for load_class_controller
	// Make it use the callback class 'Skeleton_Listener' and method 'loadClassController'
	
	
	// An addon's id should always be camel back (camelBack) (For example: pirateNewsFeed)
	// Any options/permissions/phrases related to the addon should be prefixed with its id
	
	public static function loadClassController($class, array &$extend)
    {
		switch ($class)
		{
			case 'XenForo_ControllerPublic_Forum': // This is the class we're extending
				$extend[] = 'Skeleton_ControllerPublic_Forum'; // This is the class we're extending it with
			break;
		}
    }
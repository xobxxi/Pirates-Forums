<?php

class PollsList_Route_Prefix_Polls implements XenForo_Route_Interface
{
	public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
    {
	
		return $router->getRouteMatch('PollsList_ControllerPublic_Polls', $routePath, 'forums');
    }
}
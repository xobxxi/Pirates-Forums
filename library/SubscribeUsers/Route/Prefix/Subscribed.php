<?php

class SubscribeUsers_Route_Prefix_Subscribed implements XenForo_Route_Interface
{
	public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
	{
		$action = $router->resolveActionWithIntegerParam($routePath, $request, 'thread_id');
		return $router->getRouteMatch('SubscribeUsers_ControllerPublic_Subscribed', $action, 'forums');
	}

	public function buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, array &$extraParams)
	{
		return XenForo_Link::buildBasicLinkWithIntegerParam($outputPrefix, $action, $extension, $data, 'thread_id', 'title');
	}
}
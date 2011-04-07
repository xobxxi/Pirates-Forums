<?php

class PiratesNewsFeed_Route_Prefix_News implements XenForo_Route_Interface
{
	public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
	{
		$action = $router->resolveActionWithIntegerParam($routePath, $request, 'news_id');
		
		return $router->getRouteMatch('PiratesNewsFeed_ControllerPublic_News', $action, 'pirates');
	}

	public function buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, array &$extraParams)
	{
		return XenForo_Link::buildBasicLinkWithIntegerParam($outputPrefix, $action, $extension, $data, 'id', 'title');
	}
}
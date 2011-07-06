<?php

class PirateProfile_Route_Prefix_Pirates implements XenForo_Route_Interface
{
	public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router)
	{
		$action = $router->resolveActionWithIntegerParam($routePath, $request, 'id');
		
		return $router->getRouteMatch('PirateProfile_ControllerPublic_Pirate', $action, 'pirates');
	}

	public function buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, array &$extraParams)
	{	
		if (isset($data['pirate_id']))
		{
			return XenForo_Link::buildBasicLinkWithIntegerParam($outputPrefix, $action, $extension, $data, 'pirate_id', 'name');
		}

		return XenForo_Link::buildBasicLinkWithIntegerParam($outputPrefix, $action, $extension, $data, 'user_id', 'username');
	}
}
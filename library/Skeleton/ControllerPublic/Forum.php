<?php

class Skeleton_ControllerPublic_Forum extends XFCP_Skeleton_ControllerPublic_Forum 
{
	// Please note that when extending controllers with a code listener, the class has to extend XFCP_Your_Class_Name
	// XFCP stands for XenForo Class Proxy - it is a container that makes sure everything works together properly
	
	
	public function actionYo()
	{
		// A link to this action could be represented by XenForo_Link::buildPublicLink('forums/yo');
		
		$viewParams = array(); // This is an array of variables you want to be available *IN* the template defined below
		
		return $this->responseView(
			'Skeleton_ViewPublic_Forum_Yo' // This is a fictional class, don't worry about why I guess lol
			'skeleton_templateName' /* This is a template created in the ACP and attached to this addon (As noted in the listener, 
				                       templates should also be prefixed with the addon id) */
			$viewParams
		);
	}
}
<?php

class PiratesNewsFeed_Listener
{
	public static function loadClassListener($class, &$extend)
	{
		if ($class == 'XenForo_ControllerPublic_Forum') {
			$extend[] = 'PiratesNewsFeed_ControllerPublic_PiratesNewsFeed';
		}
	}
        
	public static function checkNews ($name, $contents, $params , XenForo_Template_Abstract $template)
	{
        switch($name) {
        	case 'forum_view_pagenav_before':
        	
        	$model = XenForo_Model::create('PiratesNewsFeed_Model_PiratesNewsFeed');
        	
        	//Forum ID is hardcoded for now
			if($params['forum']['node_id']==2) {
				
				//MAKE THE AJAX LINK LOAD/FIRE PiratesNewsFeed_Model_PiratesNewsFeed::getUpdates(); or overlay to  load/fire that method.
				$contents .= "<a href=''>Check For Updates</a>";
				return $contents;
			
			}
        	break;
        }
        
	}

}

<?php

class PiratesNewsFeed_Listener
{
	public static function loadClassListener($class, &$extend)
	{
		if ($class == 'XenForo_ControllerPublic_Forum') {
			$extend[] = 'PiratesNewsFeed_ControllerPublic_Forum';
		}
	}

/*	function yoAction()
	{
		echo "Test";

		return $this->responseView(
		    'PirateNewsFeed_ViewPublic_ListNews_ListNews', // Fill in appropriately, class does not have to exist
		    'pirateNewsFeed_templateName', // Fill in appropriately, this is the name of the template. Always prefix template names
		    $viewParams // An array of variables you want available to the template. If none, set $viewParams to an empty array
		);


	}*/

	public static function checkNews ($name, $contents, $params , XenForo_Template_Abstract $template)
	{
        switch($name) {
        	case 'forum_view_pagenav_before':

        		//$model = XenForo_Model::create('PiratesNewsFeed_Model_PiratesNewsFeed');

	        	//Forum ID is hardcoded for now
				//if($params['forum']['node_id']==2) {

				//MAKE THE AJAX LINK LOAD/FIRE PiratesNewsFeed_Model_PiratesNewsFeed::getUpdates(); or overlay to  load/fire that method.
				//$contents .= "<a href=''>aaaCheck For Updates</a>";
				//$newsModel = XenForo_Model::create('PiratesNewsFeed_Model_PiratesNewsFeed');
				$params        += array('check4updates' => true);//$newsModel->x();

				$href = XenForo_Link::buildPublicLink('forums/yo');

				$link = '<a href="' . $href . '" class="OverlayTrigger">Click Here</a>';



				//die("<pre>".print_r($params,1));
				/*return $this->responseView(
				    'PirateNewsFeed_ViewPublic_ControllerShortName_ControllerAction', // Fill in appropriately, class does not have to exist
				    'pirateNewsFeed_templateName', // Fill in appropriately, this is the name of the template. Always prefix template names
				    $viewParams // An array of variables you want available to the template. If none, set $viewParams to an empty array
				);*/


				$contents      .= $link.$template->create('check4updates_link', $params)->render();

				//$contents      .= 'xxxxxxx'.$template->create('check4Updates_input', $params)->render();





				return $contents;

			//}
        	break;
        }

	}

}

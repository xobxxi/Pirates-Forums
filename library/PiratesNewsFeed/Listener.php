<?php

class PiratesNewsFeed_Listener
{	
	public static function templateCreate(&$templateName, array &$params, XenForo_Template_Abstract $template)
	{
		switch ($templateName)
		{
			case 'forum_view':
				$template->preloadTemplate('piratesNewsFeed_forum_link');
				break;
		}
	}

	public static function templateHook($hookName, &$contents, array $hookParams , XenForo_Template_Abstract $template)
	{
        switch ($hookName) {
        	case 'forum_view_pagenav_before':
				$piratesNewsFeedModel = XenForo_Model::create('PiratesNewsFeed_Model_PiratesNewsFeed');
	        	if ($piratesNewsFeedModel->canManageNews()) {
					$contents .= $template->create('piratesNewsFeed_forum_link', $template->getParams())->render();
				}
				
        		break;
        }
	}
}

<?php

class PiratesNewsFeed_Listener
{
	public static function loadClassController($class, &$extend)
	{
		if ($class == 'XenForo_ControllerPublic_Forum')
		{
			$extend[] = 'PiratesNewsFeed_ControllerPublic_Forum';
		}
	}

	public static function template_hook($name, $contents, $params , XenForo_Template_Abstract $template)
	{
        switch($name) {
        	case 'forum_view_pagenav_before':
				// we need to check for permissions (in the model once implemented) and pass to the template
				$options = XenForo_Application::get('options');
				
				$forumModel = XenForo_Model::create('XenForo_Model_Forum');
				$forum      = $forumModel->getForumById($options->piratesNewsFeed_news_forum_id);

				$params += array(
					'forum' => $forum
				);
				
				$link      = $template->create('piratesNewsFeed_link', $params)->render();
				$contents .= $link;
				
			return $contents;
        }
	}
}

<?php

class PiratesNewsFeed_Listener
{
	private static $model;
	public static function loadClassListener($class, &$extend)
	{
		if ($class == 'XenForo_ControllerPublic_Forum') {
			$extend[] = 'PiratesNewsFeed_ControllerPublic_Forum';
		}
	}


	public static function checkNews ($name, $contents, $params , XenForo_Template_Abstract $template)
	{
        switch($name) {
        	case 'forum_view_pagenav_before':
	        	if (!XenForo_Visitor::getInstance()->hasPermission('forum', 'check4Updates')) {
					return $contents;
				}

				$model = XenForo_Model::create('XenForo_Model_Forum');
				$forum = $model->getForumById(2);

				$params        += array(
					'check4updates' => true,
					'refreshLink' => XenForo_Link::buildPublicLink('forums/refreshNews',$forum)
				);//$newsModel->x();



				$href = XenForo_Link::buildPublicLink('forums/DisplayNews',$forum);

				$link = "<a href=\"$href\" class=\"OverlayTrigger\"><img src=\"http://piratesforums.com/data/check4news.png\" border=\"0\"/></a>";
				$contents      .= $link;//.$template->create('check4updates_link', $params)->render();

				return $contents;
        	break;
        }

	}

}

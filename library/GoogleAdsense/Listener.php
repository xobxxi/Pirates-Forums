<?php

class GoogleAdsense_Listener
{

	public static function template_hook($name, &$contents, array $params, XenForo_Template_Abstract $template)
	{
		switch ($name)
		{
			case 'forum_list_sidebar':
				$template  = $template->create('googleAdsense_rightbar', $params)->render();
				$contents .= $template;
				return $contents;
			case 'page_container_breadcrumb_bottom':
				$template = $template->create('googleAdsense_footer', $params)->render();
				$contents = $template . $contents;
				return $contents;
		}
	}
}
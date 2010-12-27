<?php

class GoogleAdsense_Listener
{

	public static function template_hook($name, &$contents, array $params, XenForo_Template_Abstract $template)
	{
		switch ($name)
		{
			case 'forum_list_sidebar':
				$rightBar  = $template->create('googleAdsense_rightbar')->render();
				if (empty($rightbar)) return $contents;
				$contents .= $rightBar;
			return $contents;
		}
	}
}
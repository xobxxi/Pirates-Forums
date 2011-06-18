<?php

class Album_Listener
{
	public static function templateCreate(&$templateName, array &$params, XenForo_Template_Abstract $template)
	{
		switch ($templateName)
		{
			case 'account_wrapper':
				$template->preloadTemplate('album_account_management_list_item');
				break;
			case 'member_card':
				$template->preloadTemplate('album_member_card_link_item');
				break;
			case 'member_view':
				$template->preloadTemplate('album_profile_tab');
				$template->preloadTemplate('album_profile_tab_content');
				break;
			case 'PAGE_CONTAINER':
				$template->preloadTemplate('album_navigation_list_item_member');
				break;
			case 'forum_list':
				$template->preloadTemplate('album_recent_activity_block_items');
				break;
		}
	}

	public static function templateHook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
	{
		$permissions = XenForo_Model::create('Album_Model_Album')->getPermissions();
		
		if ($permissions['view'])
		{
			switch ($hookName)
			{
				case 'account_wrapper_sidebar':
					$search = "/<li><a[ \n\r\t]+class=\"primaryContent\"[ \n\r\t]+href=\"(index\.php\?)?account\/signature\">/is";
					if (preg_match($search, $contents, $matches))
					{
						$search	  = $matches[0];
						$prefix	  = $template->create('album_account_management_list_item', $template->getParams())->render();
						$contents = str_replace($search, $prefix . "\n" . $search, $contents);
					}
					return $contents;
				case 'member_card_links':
					$template = $template->create('album_member_card_link_item', $template->getParams())->render();
					$search = "/<a href=\"(index\.php\?)?conversations\/.*?\"/is";
					if (preg_match($search, $contents, $matches))
					{
						$search	  = $matches[0];
						$contents = str_replace($search, $template . "\n" . $search, $contents);
					}
					else
					{
						$contents .= $template;
					}
					return $contents;
				case 'member_view_tabs_heading':
					$contents .= $template->create('album_profile_tab', $template->getParams())->render();
					return $contents;
				case 'member_view_tabs_content':
					$contents .= $template->create('album_profile_tab_content', $hookParams)->render();
					return $contents;
				case 'navigation_visitor_tab_links2':
					if ($permissions['upload'])
					{
						$search = "/<li><a[ \n\r\t]+href=\"(index\.php\?)?account\/news-feed\">/is";
						if (preg_match($search, $contents, $matches))
						{
							$search	  = $matches[0];
							$prefix	  = $template->create('album_navigation_list_item_member', $template->getParams())->render();
							$contents = str_replace($search, $prefix . "\n" . $search, $contents);
						}
					}
					return $contents;
				case 'recentActivityBlock_items':
					$contents .= $template->create('album_recent_activity_block_items', $hookParams)->render();
					return $contents;
			}
		}
		
	}
}
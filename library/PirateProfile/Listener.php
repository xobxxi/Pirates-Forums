<?php

class PirateProfile_Listener
{
	public static function navigationTabs(array &$extraTabs, $selectedTabId)
	{
		$extraTabs['pirates'] = array(
			'title'    => new XenForo_Phrase('pirateProfile_pirates'),
			'href'     => XenForo_Link::buildPublicLink('pirates'),
			'position' => 'middle',
			'linksTemplate' => 'pirateProfile_navigation_tab_links'
		);
	}
	
	public static function templateCreate(&$templateName, array &$params, XenForo_Template_Abstract $template)
	{
		switch ($templateName)
		{
			case 'account_wrapper':
				$template->preloadTemplate('pirateProfile_account_management_list_item');
				break;
			case 'account_alert_preferences':
				$template->preloadTemplate('pirateProfile_alert_preferences');
				break;
			case 'member_view':
				$template->preloadTemplate('pirateProfile_profile_tab');
				$template->preloadTemplate('pirateProfile_profile_tab_content');
				break;
			case 'member_card':
				$template->preloadTemplate('pirateProfile_member_card_link_item');
				break;
			case 'PAGE_CONTAINER':
				$template->preloadTemplate('pirateProfile_navigation_list_item_member');
				break;
			case 'forum_list':
				$template->preloadTemplate('pirateProfile_recent_activity_block_items');
				break;
		}
	}
	
	public static function templateHook($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template)
	{
		switch ($hookName)
		{
			case 'account_wrapper_sidebar':
				$search   = 'Personal Details</a></li>';
				$replace  = $template->create('pirateProfile_account_management_list_item', $template->getParams())->render();
				$contents = str_replace($search, $search . "\n" . $replace, $contents);
				return $contents;
			case 'account_alerts_extra':
				$contents .= $template->create('pirateProfile_alert_preferences', $template->getParams())->render();
				return $contents;
			case 'member_view_tabs_heading':
				$contents .= $template->create('pirateProfile_profile_tab', $template->getParams())->render();
				return $contents;
			case 'member_view_tabs_content':
				$contents .= $template->create('pirateProfile_profile_tab_content', $hookParams)->render();
				return $contents;
			case 'member_card_links':
				$search   = 'Profile Page</a>';
				$replace  = $template->create('pirateProfile_member_card_link_item', $template->getParams())->render();
				$contents = str_replace($search, $search . "\n" . $replace, $contents);
				return $contents;
			case 'navigation_visitor_tab_links2':
				$search   = '<ul class="col2 blockLinksList">';
				$replace  = $template->create('pirateProfile_navigation_list_item_member', $template->getParams())->render();
				$contents = str_replace($search, $search . "\n" . $replace, $contents);
				return $contents;
			case 'recentActivityBlock_items':
				$contents .= $template->create('pirateProfile_recent_activity_block_items', $hookParams)->render();
				return $contents;
		}
	}
}
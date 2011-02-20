<?php

class PirateProfile_Listener
{
	public static function navigation_tabs(array &$extraTabs, $selectedTabId)
	{
		$extraTabs['pirates'] = array(
			'title'    => new XenForo_Phrase('pirateProfile_pirates'),
			'href'     => XenForo_Link::buildPublicLink('pirates'),
			'position' => 'middle',
			'linksTemplate' => 'pirateProfile_navigation_tab_links'
		);
	}
	
	public static function template_hook($name, &$contents, array $params, XenForo_Template_Abstract $template)
	{
		switch ($name)
		{
			case 'account_wrapper_sidebar':
				$params  .= array('visitor' => XenForo_Visitor::getInstance());
				$search   = 'Personal Details</a></li>';
				$replace  = $template->create('pirateProfile_account_management_list_item', $params)->render();
				$contents = str_replace($search, $search . "\n" . $replace, $contents);
				return $contents;
			case 'member_view_tabs_heading':
				$contents .= $template->create('pirateProfile_profile_tab', $params)->render();
				return $contents;
			case 'member_view_tabs_content':
				$contents .= $template->create('pirateProfile_profile_tab_content', $params)->render();
				return $contents;
			case 'recentActivityBlock_items':
				$contents .= $template->create('pirateProfile_recent_activity_block_items', $params)->render();
				return $contents;
		}
	}
}
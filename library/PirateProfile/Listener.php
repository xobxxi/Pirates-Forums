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
}
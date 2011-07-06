<?php

class PiratesNewsFeed_CronEntry_News
{
	public static function updateNews()
	{
		XenForo_Model::create('PiratesNewsFeed_Model_PiratesNewsFeed')->updateNews(false, true);
	}
}
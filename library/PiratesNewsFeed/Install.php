<?php

class PiratesNewsFeed_Install
{
	public static function uninstall()
	{
		$dataRegistryModel = XenForo_Model::create('XenForo_Model_DataRegistry');
		$dataRegistryModel->delete('PiratesNewsFeed');
		
		return true;
	}
}
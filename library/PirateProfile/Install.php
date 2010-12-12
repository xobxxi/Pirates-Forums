<?php

class PirateProfile_Install
{
	public static function install($existingAddOn, $addOnData)
	{
		$db = XenForo_Application::get('db');

		$db->query("
			CREATE TABLE IF NOT EXISTS `pirates` (
			  `pirate_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Pirate id',
			  `user_id` int(11) NOT NULL COMMENT 'User this pirate belongs to',
			  `modified_date` int(11) NOT NULL COMMENT 'Date last modified',
			  `name` text NOT NULL COMMENT 'Pirate name',
			  `level` int(11) NOT NULL DEFAULT '1' COMMENT 'Pirate level',
			  `guild` text NOT NULL COMMENT 'Pirate guild',
			  `extra` text NOT NULL COMMENT 'Extra text',
			  `cannon` int(11) NOT NULL DEFAULT '1' COMMENT 'Cannon level',
			  `sailing` int(11) NOT NULL DEFAULT '1' COMMENT 'Sailing level',
			  `sword` int(11) NOT NULL DEFAULT '1' COMMENT 'Sword level',
			  `shooting` int(11) NOT NULL DEFAULT '1' COMMENT 'Shooting level',
			  `doll` int(11) NOT NULL DEFAULT '1' COMMENT 'Doll level',
			  `dagger` int(11) NOT NULL DEFAULT '1' COMMENT 'Dagger level',
			  `grenade` int(11) NOT NULL DEFAULT '1' COMMENT 'Grenades level',
			  `staff` int(11) NOT NULL DEFAULT '1' COMMENT 'Staff level',
			  `potions` int(11) NOT NULL DEFAULT '1' COMMENT 'Potions level',
			  `fishing` int(11) NOT NULL DEFAULT '1' COMMENT 'Fishing level',
			  PRIMARY KEY (`pirate_id`)
			) ENGINE=MyISAM	 DEFAULT CHARSET=utf8;
		");
		
		if ($existingAddOn['version_id'] < 8)
		{
			$db->query("
				INSERT INTO `xf_content_type_field` (`content_type`, `field_name`, `field_value`) VALUES
				('pirate', 'attachment_handler_class', 'PirateProfile_AttachmentHandler_Pirate');
			");
		}
		
		if ($existingAddOn['version_id'] < 9)
		{
			$db->query("
				INSERT INTO `xf_content_type_field` (`content_type`, `field_name`, `field_value`) VALUES
				('pirate', 'news_feed_handler_class', 'PirateProfile_NewsFeedHandler_Pirate');
			");
		}

		return true;
	}

	public static function uninstall()
	{
		$db = XenForo_Application::get('db');

		$db->query("
			DROP TABLE IF EXISTS
				`pirates`;
		");

		return true;
	}
}
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
			  `modified_date` int(11) NOT NULL COMMENT 'Modified date',
			  `name` text NOT NULL COMMENT 'Pirate name',
			  `level` int(11) NOT NULL DEFAULT '1' COMMENT 'Pirate level',
			  `guild` text NOT NULL COMMENT 'Pirate guild',
			  `likes` int(10) NOT NULL DEFAULT '0',
			  `like_users` blob NOT NULL,
			  `extra` text NOT NULL COMMENT 'Extra text',
			  `cannon` int(11) NOT NULL COMMENT 'Cannon level',
			  `sailing` int(11) NOT NULL COMMENT 'Sailing level',
			  `sword` int(11) NOT NULL COMMENT 'Sword level',
			  `shooting` int(11) NOT NULL COMMENT 'Shooting level',
			  `doll` int(11) NOT NULL COMMENT 'Doll level',
			  `dagger` int(11) NOT NULL COMMENT 'Dagger level',
			  `grenade` int(11) NOT NULL COMMENT 'Grenades level',
			  `staff` int(11) NOT NULL COMMENT 'Staff level',
			  `potions` int(11) NOT NULL COMMENT 'Potions level',
			  `fishing` int(11) NOT NULL COMMENT 'Fishing level',
			  `make_fit` int(11) NOT NULL DEFAULT '0' COMMENT 'Make picture fit',
			  PRIMARY KEY (`pirate_id`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
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
		
		if ($existingAddOn['version_id'] < 17)
		{
			$db->query("
				INSERT INTO `xf_content_type_field` (`content_type`, `field_name`, `field_value`) VALUES
				('pirate', 'like_handler_class', 'PirateProfile_LikeHandler_Pirate');
			");
			
			$db->query("
				INSERT INTO `xf_content_type_field` (`content_type`, `field_name`, `field_value`) VALUES
				('pirate', 'alert_handler_class', 'PirateProfile_AlertHandler_Pirate');
			");
			
			$fields = array(
				'attachment_handler_class' => 'PirateProfile_AttachmentHandler_Pirate',
				'news_feed_handler_class'  => 'PirateProfile_NewsFeedHandler_Pirate',
				'like_handler_class'       => 'PirateProfile_LikeHandler_Pirate',
				'alert_handler_class'      => 'PirateProfile_AlertHandler_Pirate',
			);
			
			$fields = serialize($fields);
			
			$db->query("
				INSERT INTO `xf_content_type` (`content_type`, `addon_id`, `fields`) VALUES
				('pirate', 'pirateProfile', ?);
			", $fields);
		}
		
		$model = new XenForo_Model('XenForo_Model_ContentType');
		$model->rebuildContentTypeCache();

		return true;
	}

	public static function uninstall()
	{
		$db = XenForo_Application::get('db');

		$db->query("
			DROP TABLE IF EXISTS
				`pirates`;
		");
		
		$db->query("
			DELETE FROM `xf2`.`xf_content_type_field`
			WHERE `xf_content_type_field`.`content_type` = 'pirate'
			AND `xf_content_type_field`.`field_name` = 'attachment_handler_class'
		");
		
		$db->query("
			DELETE FROM `xf2`.`xf_content_type_field`
			WHERE `xf_content_type_field`.`content_type` = 'pirate' 
			AND `xf_content_type_field`.`field_name` = 'news_feed_handler_class'
		");
		
		$db->query("
			DELETE FROM `xf2`.`xf_content_type_field`
			WHERE `xf_content_type_field`.`content_type` = 'pirate' 
			AND `xf_content_type_field`.`field_name` = 'like_handler_class'
		");
		
		$db->query("
			DELETE FROM `xf2`.`xf_content_type_field`
			WHERE `xf_content_type_field`.`content_type` = 'pirate' 
			AND `xf_content_type_field`.`field_name` = 'alert_handler_class'
		");
		
		$db->query("
			DELETE FROM `xf2`.`xf_content_type`
			WHERE `xf_content_type`.`addon_id` = 'pirateProfile'
		");

		return true;
	}
}
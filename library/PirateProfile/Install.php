<?php

class PirateProfile_Install
{
	public static function install($existingAddOn, $addOnData)
	{
		$db = XenForo_Application::get('db');

		$db->query("
			CREATE TABLE IF NOT EXISTS `pirate` (
			  `pirate_id` int(11) NOT NULL AUTO_INCREMENT,
			  `user_id` int(11) NOT NULL,
			  `modified_date` int(11) NOT NULL,
			  `name` text NOT NULL,
			  `level` int(11) NOT NULL DEFAULT '1',
			  `guild` text NOT NULL,
			  `likes` int(10) NOT NULL DEFAULT '0',
			  `like_users` blob NOT NULL,
			  `extra` text NOT NULL,
			  `make_fit` int(11) NOT NULL DEFAULT '0',
			  `comment_count` int(10) NOT NULL DEFAULT '0',
			  `first_comment_date` int(10) NOT NULL DEFAULT '0',
			  `last_comment_date` int(10) NOT NULL DEFAULT '0',
			  `latest_comment_ids` varbinary(100) NOT NULL,
			  PRIMARY KEY (`pirate_id`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
		");
		
		$skills = array_merge(
			PirateProfile_Model_Pirate::getWeapons(true, true),
			PirateProfile_Model_Pirate::getSkills(true, true)
		);
		foreach ($skills as $skill)
		{
			self::addColumnIfNotExist(
				$db,
				'pirate',
				$skill,
				'int(11) NOT NULL DEFAULT  \'0\''
			);
		}
		
		$ranks = PirateProfile_Model_Pirate::getRanks(true, true);
		foreach ($ranks as $type => $children)
		{
			self::addColumnIfNotExist(
				$db,
				'pirate',
				$type,
				'text NOT NULL'
			);
		}
		
		$db->query("
			CREATE TABLE IF NOT EXISTS pirate_comment (
			  pirate_comment_id int(10) unsigned NOT NULL AUTO_INCREMENT,
			  pirate_id int(10) unsigned NOT NULL,
			  user_id int(10) unsigned NOT NULL,
			  username varchar(50) NOT NULL,
			  comment_date int(10) unsigned NOT NULL,
			  message mediumtext NOT NULL,
			  PRIMARY KEY (pirate_comment_id),
			  KEY profile_post_id_comment_date (pirate_id,comment_date)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
			
		");
		
		$fields = self::getFields();
		foreach ($fields as $name => $value)
		{
			$row = array(
				'table'      => 'xf_content_type_field',
				'identifier' => "xf_content_type_field.content_type = 'pirate'
					AND xf_content_type_field.field_name = '{$name}'",
				'fields'     => '`content_type`, `field_name`, `field_value`',
				'values'     => "'pirate', '{$name}', '{$value}'"
			);
			
			self::insertRow($db, $row, true);
		}
		
		$fields = serialize($fields);
		
		$contentTypeRow = array(
			'table'      => 'xf_content_type',
			'identifier' => "xf_content_type.content_type = 'pirate'",
			'fields'     => '`content_type`, `addon_id`, `fields`',
			'values'     => "'pirate', 'pirateProfile', '{$fields}'"
		);
		
		self::insertRow($db, $contentTypeRow, true);
		
		$contentTypeModel = XenForo_Model::create('XenForo_Model_ContentType');
		$contentTypeModel->rebuildContentTypeCache();

		return true;
	}

	public static function uninstall()
	{
		$db = XenForo_Application::get('db');

		$db->query("
			DROP TABLE IF EXISTS
				pirate;
		");
		
		$db->query("
			DROP TABLE IF EXISTS
				pirate_comment;
		");
		
		$db->query("
			DELETE FROM xf_content_type_field
			WHERE xf_content_type_field.content_type = 'pirate'
		");
		
		$db->query("
			DELETE FROM xf_content_type
			WHERE xf_content_type.addon_id = 'pirateProfile'
		");
		
		$db->query("
			DELETE FROM xf_user_alert
			WHERE xf_user_alert.content_type = 'pirate'
		");
		
		/* this data will be retained until likes can be removed from the user count
		$db->query("
			DELETE FROM xf_liked_content
			WHERE xf_liked_content.content_type = 'pirate'
		");
		*/
		
		$db->query("
			UPDATE xf_attachment 
			SET    unassociated =  '1' 
			WHERE  xf_attachment.content_type = 'pirate';
		");

		return true;
	}
	
	public static function getFields()
	{
		$fields = array();
		
		$fields['attachment_handler_class'] = 'PirateProfile_AttachmentHandler_Pirate';
		$fields['news_feed_handler_class']  = 'PirateProfile_NewsFeedHandler_Pirate';
		$fields['like_handler_class']       = 'PirateProfile_LikeHandler_Pirate';
		$fields['alert_handler_class']      = 'PirateProfile_AlertHandler_Pirate';
		$fields['report_handler_class']     = 'PirateProfile_ReportHandler_Pirate';
		
		return $fields;
	}
	
	public static function insertRow($db, $row, $overwrite = false)
	{
		if ($overwrite)
		{
			$existing = $db->fetchRow("
				SELECT {$row['table']}.*
				FROM {$row['table']}
				WHERE {$row['identifier']}
			");
			
			if (!empty($existing))
			{
				$db->query("
					DELETE FROM {$row['table']}
					WHERE {$row['identifier']}
				");
			}
		}
		
		$db->query("
			INSERT INTO `{$row['table']}` ({$row['fields']}) VALUES
			({$row['values']})
		");
	}
	
	public static function addColumnIfNotExist($db, $table, $field, $attr)
	{
	    if ($db->fetchRow('SHOW COLUMNS FROM ' . $table . ' WHERE Field = ?', $field))
	    {
	        return;
	    }

	    return $db->query('ALTER TABLE ' . $table . ' ADD ' . $field . ' ' . $attr);
	}
}
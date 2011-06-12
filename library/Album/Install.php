<?php

class Album_Install
{
	public static function install($existingAddOn, $addOnData)
	{
		$db = XenForo_Application::get('db');

		$db->query("
			CREATE TABLE IF NOT EXISTS album (
			  album_id int(11) NOT NULL AUTO_INCREMENT,
			  user_id int(11) NOT NULL,
			  name text NOT NULL,
			  date int(11) NOT NULL,
			  photo_count int(11) NOT NULL DEFAULT '0',
			  cover_photo_id int(11) NOT NULL DEFAULT '0',
			  last_position int(11) NOT NULL DEFAULT '0',
			  PRIMARY KEY (album_id)
			) ENGINE=InnoDB	 DEFAULT CHARSET=utf8;
		");

		$db->query("
			CREATE TABLE IF NOT EXISTS album_photo (
			  photo_id int(11) NOT NULL AUTO_INCREMENT,
			  album_id int(11) NOT NULL,
			  attachment_id int(11) NOT NULL,
			  position int(11) NOT NULL,
			  description text CHARACTER SET utf8,
			  PRIMARY KEY (photo_id)
			) ENGINE=MyISAM	 DEFAULT CHARSET=utf8;
		");

		$fields = self::getFields();
		foreach ($fields as $name => $value)
		{
			$row = array(
				'table'		 => 'xf_content_type_field',
				'identifier' => "xf_content_type_field.content_type = 'album'
					AND xf_content_type_field.field_name = '{$name}'",
				'fields'	 => '`content_type`, `field_name`, `field_value`',
				'values'	 => "'album', '{$name}', '{$value}'"
			);

			self::insertRow($db, $row, true);
		}

		$fields = serialize($fields);

		$contentTypeRow = array(
			'table'		 => 'xf_content_type',
			'identifier' => "xf_content_type.content_type = 'album'",
			'fields'	 => '`content_type`, `addon_id`, `fields`',
			'values'	 => "'album', 'album', '{$fields}'"
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
				album;
		");

		$db->query("
			DELETE FROM xf_content_type_field
			WHERE xf_content_type_field.content_type = 'album'
		");

		$db->query("
			DELETE FROM xf_content_type
			WHERE xf_content_type.addon_id = 'album'
		");

		/*$db->query("
			DELETE FROM xf_user_alert
			WHERE xf_user_alert.content_type = 'album'
		");*/

		/* this data will be retained until likes can be removed from the user count
		$db->query("
			DELETE FROM xf_liked_content
			WHERE xf_liked_content.content_type = 'album'
		");
		*/

		$db->query("
			UPDATE xf_attachment
			SET	   unassociated =  '1'
			WHERE  xf_attachment.content_type = 'album';
		");

		return true;
	}

	public static function getFields()
	{
		$fields = array();

		$fields['attachment_handler_class'] = 'Album_AttachmentHandler_Album';
		$fields['news_feed_handler_class']	= 'Album_NewsFeedHandler_Album';

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

			if ($existing)
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
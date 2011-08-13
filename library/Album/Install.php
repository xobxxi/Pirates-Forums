<?php

class Album_Install
{
	public static function install($existingAddOn, $addOnData)
	{
		$db = XenForo_Application::get('db');

		$db->query("
			CREATE TABLE IF NOT EXISTS album (
			  album_id int(10) NOT NULL AUTO_INCREMENT,
			  user_id int(10) NOT NULL,
			  allow_view enum('everyone','members','followed','none') NOT NULL DEFAULT 'everyone',
			  name text NOT NULL,
			  date int(10) NOT NULL,
			  photo_count int(10) NOT NULL DEFAULT '0',
			  cover_photo_id int(10) NOT NULL DEFAULT '0',
			  likes int(10) NOT NULL DEFAULT '0',
			  like_users blob NOT NULL,
			  PRIMARY KEY (album_id)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
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
		
		self::installPhotos($db);

		$contentTypeModel = XenForo_Model::create('XenForo_Model_ContentType');
		$contentTypeModel->rebuildContentTypeCache();

		return true;
	}
	
	public static function installPhotos($db)
	{
		$db->query("
			CREATE TABLE IF NOT EXISTS album_photo (
			  photo_id int(10) NOT NULL AUTO_INCREMENT,
			  album_id int(10) NOT NULL,
			  attachment_id int(10) NOT NULL,
			  position int(10) NOT NULL,
			  description text CHARACTER SET utf8,
			  likes int(10) NOT NULL DEFAULT '0',
			  like_users blob NOT NULL,
			  PRIMARY KEY (photo_id)
			) ENGINE=InnoDB	 DEFAULT CHARSET=utf8;
		");
		
		$fields = self::getPhotoFields();
		foreach ($fields as $name => $value)
		{
			$row = array(
				'table'		 => 'xf_content_type_field',
				'identifier' => "xf_content_type_field.content_type = 'album_photo'
					AND xf_content_type_field.field_name = '{$name}'",
				'fields'	 => '`content_type`, `field_name`, `field_value`',
				'values'	 => "'album_photo', '{$name}', '{$value}'"
			);

			self::insertRow($db, $row, true);
		}

		$fields = serialize($fields);

		$contentTypeRow = array(
			'table'		 => 'xf_content_type',
			'identifier' => "xf_content_type.content_type = 'album_photo'",
			'fields'	 => '`content_type`, `addon_id`, `fields`',
			'values'	 => "'album_photo', 'album', '{$fields}'"
		);

		self::insertRow($db, $contentTypeRow, true);
		
		self::installPhotoComments($db);
	}
	
	public static function installPhotoComments($db)
	{
	    $db->query("
	        CREATE TABLE IF NOT EXISTS album_photo_comment (
              album_photo_comment_id int(10) NOT NULL AUTO_INCREMENT,
              photo_id int(10) NOT NULL,
              user_id int(10) NOT NULL,
              username varchar(50) CHARACTER SET utf8 NOT NULL,
              comment_date int(10) NOT NULL,
              likes int(10) NOT NULL DEFAULT '0',
              like_users blob NOT NULL,
              message mediumtext CHARACTER SET utf8 NOT NULL,
              PRIMARY KEY (album_photo_comment_id)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
	    ");
	    
	    $fields = self::getPhotoCommentFields();
	    foreach ($fields as $name => $value)
		{
			$row = array(
				'table'		 => 'xf_content_type_field',
				'identifier' => "xf_content_type_field.content_type = 'album_photo_comment'
					AND xf_content_type_field.field_name = '{$name}'",
				'fields'	 => '`content_type`, `field_name`, `field_value`',
				'values'	 => "'album_photo_comment', '{$name}', '{$value}'"
			);

			self::insertRow($db, $row, true);
		}

		$fields = serialize($fields);

		$contentTypeRow = array(
			'table'		 => 'xf_content_type',
			'identifier' => "xf_content_type.content_type = 'album_photo_comment'",
			'fields'	 => '`content_type`, `addon_id`, `fields`',
			'values'	 => "'album_photo_comment', 'album', '{$fields}'"
		);

		self::insertRow($db, $contentTypeRow, true);
	}

	public static function uninstall()
	{
		$db = XenForo_Application::get('db');

		$db->query("
			DROP TABLE IF EXISTS
				album;
		");
		
		$db->query("
			DROP TABLE IF EXISTS
				album_photo;
		");

		$db->query("
			DELETE FROM xf_content_type_field
			WHERE xf_content_type_field.content_type = 'album'
		");
		
		$db->query("
			DELETE FROM xf_content_type_field
			WHERE xf_content_type_field.content_type = 'album_photo'
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
	
	public static function getPhotoFields()
	{
		$fields = array();
		
		$fields['alert_handler_class']      = 'Album_AlertHandler_AlbumPhoto';
		$fields['like_handler_class']       = 'Album_LikeHandler_AlbumPhoto';
		$fields['news_feed_handler_class']	= 'Album_NewsFeedHandler_AlbumPhoto';
		$fields['report_handler_class']     = 'Album_ReportHandler_AlbumPhoto';
		
		return $fields;
	}
	
	public static function getPhotoCommentFields()
	{
	    $fields = array();
	    
	    $fields['alert_handler_class']     = 'Album_AlertHandler_AlbumPhotoComment';
	    $fields['like_handler_class']      = 'Album_LikeHandler_AlbumPhotoComment';
	    $fields['news_feed_handler_class'] = 'Album_NewsFeedHandler_AlbumPhotoComment';
	    
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
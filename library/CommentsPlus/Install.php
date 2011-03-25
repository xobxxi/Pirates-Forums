<?php

class CommentsPlus_Install
{
	public static function install($existingAddOn, $addOnData)
	{
		$db = XenForo_Application::get('db');
		
		$fields = self::getFields();
		foreach ($fields as $name => $value)
		{
			$row = array(
				'table'      => 'xf_content_type_field',
				'identifier' => "xf_content_type_field.content_type = 'profile_post_comment'
					AND xf_content_type_field.field_name = '{$name}'",
				'fields'     => '`content_type`, `field_name`, `field_value`',
				'values'     => "'profile_post_comment', '{$name}', '{$value}'"
			);
			
			self::insertRow($db, $row, true);
		}
		
		$fields = serialize($fields);
		
		$contentTypeRow = array(
			'table'      => 'xf_content_type',
			'identifier' => "xf_content_type.content_type = 'profile_post_comment'",
			'fields'     => '`content_type`, `addon_id`, `fields`',
			'values'     => "'profile_post_comment', 'commentsPlus', '{$fields}'"
		);
		
		self::insertRow($db, $contentTypeRow, true);
		
		$contentTypeModel = XenForo_Model::create('XenForo_Model_ContentType');
		$contentTypeModel->rebuildContentTypeCache();
		
		self::addColumnIfNotExist(
			$db,
			'xf_profile_post_comment',
			'likes',
			'int(10) NOT NULL DEFAULT \'0\''
		);
		
		self::addColumnIfNotExist(
			$db,
			'xf_profile_post_comment',
			'like_users',
			'blob NOT NULL'
		);

		return true;
	}

	public static function uninstall()
	{
		$db = XenForo_Application::get('db');
		
		$db->query("
			DELETE FROM xf_content_type_field
			WHERE xf_content_type_field.content_type = 'profile_post_comment'
		");
		
		$db->query("
			DELETE FROM xf_content_type
			WHERE xf_content_type.addon_id = 'commentsPlus'
		");
		
		$db->query("
			ALTER TABLE xf_conversation_message DROP likes
		");
		
		$db->query("
			ALTER TABLE xf_conversation_message DROP like_users
		");

		return true;
	}
	
	public static function getFields()
	{
		$fields = array();
		
		$fields['like_handler_class']      = 'CommentsPlus_LikeHandler_ProfilePostComment';
		$fields['alert_handler_class']     = 'CommentsPlus_AlertHandler_ProfilePostComment';
		$fields['news_feed_handler_class'] = 'CommentsPlus_NewsFeedHandler_ProfilePostComment';
		
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
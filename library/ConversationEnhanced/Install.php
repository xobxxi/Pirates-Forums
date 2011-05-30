<?php

class ConversationEnhanced_Install
{
	public static function install($existingAddOn, $addOnData)
	{
		$db = XenForo_Application::get('db');
		
		$fields = self::getFields();
		foreach ($fields as $name => $value)
		{
			$row = array(
				'table'      => 'xf_content_type_field',
				'identifier' => "xf_content_type_field.content_type = 'conversation_message'
					AND xf_content_type_field.field_name = '{$name}'",
				'fields'     => '`content_type`, `field_name`, `field_value`',
				'values'     => "'conversation_message', '{$name}', '{$value}'"
			);
			
			self::insertRow($db, $row, true);
		}
		
		$fields = serialize($fields);
		
		$contentTypeRow = array(
			'table'      => 'xf_content_type',
			'identifier' => "xf_content_type.content_type = 'conversation_message'",
			'fields'     => '`content_type`, `addon_id`, `fields`',
			'values'     => "'conversation_message', 'conversationEnhanced', '{$fields}'"
		);
		
		self::insertRow($db, $contentTypeRow, true);
		
		$contentTypeModel = XenForo_Model::create('XenForo_Model_ContentType');
		$contentTypeModel->rebuildContentTypeCache();
		
		self::addColumnIfNotExist(
			$db,
			'xf_conversation_message',
			'attach_count',
			'SMALLINT( 5 ) UNSIGNED NOT NULL DEFAULT  \'0\''
		);

		return true;
	}

	public static function uninstall()
	{
		$db = XenForo_Application::get('db');
		
		$db->query("
			DELETE FROM xf_content_type_field
			WHERE xf_content_type_field.content_type = 'conversation_message'
		");
		
		$db->query("
			DELETE FROM xf_content_type
			WHERE xf_content_type.addon_id = 'conversationEnhanced'
		");
		
		$db->query("
			ALTER TABLE xf_conversation_message DROP attach_count
		");
		
		$db->query("
			UPDATE xf_attachment 
			SET    unassociated =  '1' 
			WHERE  xf_attachment.content_type = 'conversation_message';
		");

		return true;
	}
	
	public static function getFields()
	{
		$fields = array();
		
		$fields['attachment_handler_class'] = 'ConversationEnhanced_AttachmentHandler_ConversationMessage';
		$fields['report_handler_class']     = 'ConversationEnhanced_ReportHandler_ConversationMessage';
		
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
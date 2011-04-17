<?php

class PiratesNewsFeed_Install
{
	public static function install($existingAddOn, $addOnData)
	{
		$db = XenForo_Application::get('db');
		
		$fields = self::getFields();
		foreach ($fields as $name => $value)
		{
			$row = array(
				'table'      => 'xf_content_type_field',
				'identifier' => "xf_content_type_field.content_type = 'news'
					AND xf_content_type_field.field_name = '{$name}'",
				'fields'     => '`content_type`, `field_name`, `field_value`',
				'values'     => "'news', '{$name}', '{$value}'"
			);
			
			self::insertRow($db, $row, true);
		}
		
		$fields = serialize($fields);
		
		$contentTypeRow = array(
			'table'      => 'xf_content_type',
			'identifier' => "xf_content_type.content_type = 'report'",
			'fields'     => '`content_type`, `addon_id`, `fields`',
			'values'     => "'news', 'piratesNewsFeed', '{$fields}'"
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
			DELETE FROM xf_content_type_field
			WHERE xf_content_type_field.content_type = 'news'
		");
		
		$db->query("
			DELETE FROM xf_content_type
			WHERE xf_content_type.addon_id = 'piratesNewsFeed'
		");
		
		$dataRegistryModel = XenForo_Model::create('XenForo_Model_DataRegistry');
		$dataRegistryModel->delete('PiratesNewsFeed');
		
		return true;
	}
	
	public static function getFields()
	{
		$fields = array();
		
		$fields['alert_handler_class'] = 'PiratesNewsFeed_AlertHandler_News';
		
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
}
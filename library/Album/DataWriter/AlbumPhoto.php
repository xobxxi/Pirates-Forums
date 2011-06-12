<?php

class Album_DataWriter_AlbumPhoto extends XenForo_DataWriter
{
	protected $_username;

	protected function _getFields()
	{
		$fields = array(
			'album_photo' => array(
				'photo_id' => array(
					'type'			=> self::TYPE_UINT,
					'autoIncrement' => true
				),
				'album_id' => array(
					'type'	   => self::TYPE_UINT,
					'required' => true
				),
				'attachment_id' => array(
					'type'	   => self::TYPE_UINT,
					'required' => true
				),
				'position' => array(
					'type'	   => self::TYPE_UINT,
					'required' => true
				),
				'description' => array(
					'type'			=> self::TYPE_STRING,
					'maxLength'		=> 250
				)
			)
		);

		return $fields;
	}

	protected function _getExistingData($data)
	{
		if (!$photoId = $this->_getExistingPrimaryKey($data))
		{
			return false;
		}

		if (!$photo = $this->_getAlbumModel()->getPhotoById($photoId, true))
		{
			return false;
		}

		return $this->getTablesDataFromArray($photo);
	}

	protected function _getUpdateCondition($tableName)
	{
		return 'photo_id = ' . $this->_db->quote($this->getExisting('photo_id'));
	}

	protected function _postDelete()
	{
		$dw = XenForo_DataWriter::create('XenForo_DataWriter_Attachment');
		if ($dw->setExistingData($this->get('attachment_id'), true))
		{
			$dw->delete();

			$this->_getAlbumModel()->rebuildAlbumById($this->get('album_id'));
		}
	}

	protected function _getAlbumModel()
	{
		return $this->getModelFromCache('Album_Model_Album');
	}
}
<?php

class Album_DataWriter_Album extends XenForo_DataWriter
{
	
	protected function _getFields()
	{	
		$fields = array(
			'album' => array(
				'album_id' => array(
					'type'			=> self::TYPE_UINT,
					'autoIncrement' => true
				),
				'user_id' => array(
					'type'	   => self::TYPE_UINT,
					'required' => true
				),
				'date' => array(
					'type'	   => self::TYPE_UINT,
					'required' => true,
					'default'  => XenForo_Application::$time
				),
				'name' => array(
					'type'			=> self::TYPE_STRING,
					'maxLength'		=> 32,
					'required'		=> true,
					'requiredError' => 'album_please_enter_album_name'
				),
				'photo_count' => array(
					'type' => self::TYPE_UINT,
					'max'  => 100
				),
				'cover_attachment_id' => array(
					'type' => self::TYPE_UINT
				)
			)
		);
		
		return $fields;
	}

	protected function _getExistingData($data)
	{
		if (!$albumId = $this->_getExistingPrimaryKey($data))
		{
			return false;
		}

		if (!$album = $this->_getAlbumModel()->getAlbumById($albumId))
		{
			return false;
		}

		return $this->getTablesDataFromArray($album);
	}

	protected function _getUpdateCondition($tableName)
	{
		return 'album_id = ' . $this->_db->quote($this->getExisting('album_id'));
	}
	
	protected final function _postSave()
	{
		$photoHash = $this->getExtraData('attachment_hash');

		if ($photoHash)
		{
			$this->_associatePhotos($photoHash);
			$this->_setCoverPhoto();
		}
		
		return true;
	}
	
	protected function _associatePhotos($attachmentHash)
	{
		$rows = $this->_db->update('xf_attachment', array(
			'content_type' => 'album',
			'content_id' => $this->get('album_id'),
			'temp_hash' => '',
			'unassociated' => 0
		),  'temp_hash = ' . $this->_db->quote($attachmentHash));
		
		if ($rows)
		{
			$this->set('photo_count', $this->get('photo_count') + $rows, '', array('setAfterPreSave' => true));

			$this->_db->update('album', array(
				'photo_count' => $this->get('photo_count')
			), 'album_id' . ' = ' .  $this->_db->quote($this->get('album_id')));
		}
		
		return true;
	}
	
	protected function _setCoverPhoto()
	{
		$photos = $this->_getAlbumModel()->getAllPhotosForAlbumById($this->get('album_id'));
		
		$cover = end($photos);
		
		$this->set('cover_attachment_id', $cover['attachment_id'], '', array('setAfterPreSave' => true));
		
		$this->_db->update('album', array(
			'cover_attachment_id' => $this->get('cover_attachment_id')
			), 'album_id' . ' = ' . $this->_db->quote($this->get('album_id')));
	}
	
	protected function _getAlbumModel()
	{
		return $this->getModelFromCache('Album_Model_Album');
	}
}
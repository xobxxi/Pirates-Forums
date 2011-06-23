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
					'maxLength'		=> 250,
					'default'       => ''
				),
				'likes' => array(
					'type' => self::TYPE_UINT_FORCED,
					'default' => 0
				),
				'like_users' => array(
					'type' => self::TYPE_SERIALIZED,
					'default' => 'a:0:{}'
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
		
		if ($likes = $this->get('likes'))
		{
			$this->getModelFromCache('XenForo_Model_Like')->deleteContentLikes(
				'album_photo', $this->get('photo_id')
			);
			
			if ($userId = $this->get('user_id'))
			{
				$this->_db->query('
					UPDATE xf_user
					SET like_count = IF(like_count > ?, like_count - ?, 0)
					WHERE user_id = ?
				', array($likes, $likes, $userId));
			}
		}
	}

	protected function _getAlbumModel()
	{
		return $this->getModelFromCache('Album_Model_Album');
	}
}
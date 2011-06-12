<?php

class Album_DataWriter_Album extends XenForo_DataWriter
{
	protected $_username;
	
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
					'type'    => self::TYPE_UINT,
					'max'     => 100,
					'default' => 0
				),
				'cover_photo_id' => array(
					'type'    => self::TYPE_UINT,
					'default' => 0
				),
				'last_position' => array(
					'type'    => self::TYPE_UINT,
					'default' => 0
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
	
	protected function _preSave()
	{
		$photoHash = $this->getExtraData('attachment_hash');
		
		if ($photoHash)
		{
			if ($this->isInsert() && ($this->get('photo_count') < 1))
			{
				$rows = $this->_db->fetchAll('
					SELECT *
					FROM xf_attachment
					WHERE temp_hash = ?	
				', $photoHash);
		
				if (!$rows)
				{
					$this->error(new XenForo_Phrase('album_albums_must_contain_at_least_one_photo'));
				}
			}
		}
	}
	
	protected function _postSave()
	{
		$photoHash = $this->getExtraData('attachment_hash');

		if ($photoHash)
		{
			$this->_associatePhotos($photoHash);
			
			if ($this->isInsert())
			{
				$this->_setCoverPhoto();
			}
		}
		
		$this->_publishToNewsFeed();
		
		return true;
	}
	
	protected function _postDelete()
	{
		$albumId = $this->get('album_id');
		
		$this->getModelFromCache('XenForo_Model_NewsFeed')->delete(
			'album',
			$albumId
		);
		
		if ($this->get('photo_count') > 0)
		{
			$this->getModelFromCache('XenForo_Model_Attachment')->deleteAttachmentsFromContentIds(
				'album',
				array($albumId)
			);
			
			$this->_getAlbumModel()->removeAllPhotosFromAlbumById($albumId);
		}
	}
	
	protected function _associatePhotos($attachmentHash)
	{
		$photos = $this->_db->fetchAll('
			SELECT *
			FROM xf_attachment
			WHERE xf_attachment.temp_hash = ?
		', $attachmentHash);
		
		if ($photos)
		{
			$new = $this->_db->update('xf_attachment', array(
				'content_type' => 'album',
				'content_id'   => $this->get('album_id'),
				'temp_hash'    => '',
				'unassociated' => 0
			),  'temp_hash = ' . $this->_db->quote($attachmentHash));
			
			foreach ($photos as $photo)
			{
				$position = $this->get('last_position') + 1;
				
				$photoDw = XenForo_DataWriter::create('Album_DataWriter_AlbumPhoto');
				
				$photoDw->set('album_id', $this->get('album_id'));
				$photoDw->set('attachment_id', $photo['attachment_id']);
				$photoDw->set('position', $position);
				$photoDw->save();
				
				$this->set('last_position', $position, '', array('setAfterPreSave' => true));
			}
			
			$this->set('photo_count', $this->get('photo_count') + $new, '', array('setAfterPreSave' => true));

			$this->_db->update('album', array(
				'photo_count'   => $this->get('photo_count'),
				'last_position' => $this->get('last_position')
			), 'album_id' . ' = ' .  $this->_db->quote($this->get('album_id')));
		}
		
		return true;
	}
	
	protected function _setCoverPhoto()
	{
		$photos = $this->_getAlbumModel()->getAllPhotosForAlbumById($this->get('album_id'));
		
		$cover = end($photos);
		
		$this->set('cover_photo_id', $cover['photo_id'], '', array('setAfterPreSave' => true));
		
		$this->_db->update('album', array(
			'cover_photo_id' => $this->get('cover_photo_id')
			), 'album_id' . ' = ' . $this->_db->quote($this->get('album_id')));
	}
	
	protected function _publishToNewsFeed()
	{
		$newsFeedModel = $this->_getNewsFeedModel();
		
		if ($this->isInsert())
		{
			$id = $this->get('user_id');

			$newsFeedModel->publish(
				$id,
				$this->_getUsername($id),
				'album',
				$this->get('album_id'),
				'add',
				array()
			);
		}
		
		if ($this->isUpdate())
		{	
			if ($this->isChanged('name'))
			{
				$id = $this->get('user_id');

				$newsFeedModel->publish(
					$id,
					$this->_getUsername($id),
					'album',
					$this->get('album_id'),
					'name',
					array(
						'old' => $this->getExisting('name')
					)
				);
			}
			
			if ($this->isChanged('photo_count') && $this->get('photo_count') > $this->getExisting('photo_count'))
			{
				$new = $this->get('photo_count') - $this->getExisting('photo_count');
				
				$id = $this->get('user_id');

				$newsFeedModel->publish(
					$id,
					$this->_getUsername($id),
					'album',
					$this->get('album_id'),
					'photos',
					array('new' => $new)
				);
			}
		}
	}
	
	protected function _setUsername($id)
	{
		$user = $this->_getUserModel()->getUserById($id);
		$this->_username[$id] = $user['username'];
		
		return $this->_username[$id];
	}
	
	protected function _getUsername($id)
	{
		if (!$username = $this->_username[$id])
		{
			$username = $this->_setUsername($id);
		}
		
		return $username;
	}
	
	protected function _getAlbumModel()
	{
		return $this->getModelFromCache('Album_Model_Album');
	}
	
	protected function _getNewsFeedModel()
	{
		return $this->getModelFromCache('XenForo_Model_NewsFeed');
	}
}
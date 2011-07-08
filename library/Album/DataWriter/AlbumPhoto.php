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
				'user_id' => array(
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
				),
				'comment_count' => array(
					'type'    => self::TYPE_UINT_FORCED,
					'default' => 0
				),
				'first_comment_date' => array(
					'type'    => self::TYPE_UINT,
					'default' => 0
				),
				'last_comment_date' => array(
				'type'    => self::TYPE_UINT,
				'default' => 0
				),
				'latest_comment_ids' => array(
					'type'      => self::TYPE_BINARY,
					'default'   => '',
					'maxLength' => 100
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
	
	public function rebuildAlbumPhotoCommentCounters()
	{
		$db = $this->_db;
		$photoId = $this->get('photo_id');

		$counts = $db->fetchRow('
			SELECT COUNT(*) AS comment_count,
				MIN(comment_date) AS first_comment_date,
				MAX(comment_date) AS last_comment_date
			FROM album_photo_comment
			WHERE photo_id = ?
		', $photoId);

		if ($counts['comment_count'])
		{
			$ids = $db->fetchCol($db->limit(
				'
					SELECT album_photo_comment_id
					FROM album_photo_comment
					WHERE photo_id = ?
					ORDER BY comment_date DESC
				', 3
			), $photoId);
			$ids = array_reverse($ids);
		}
		else
		{
			$ids = array();
		}

		$this->bulkSet($counts);
		$this->set('latest_comment_ids', implode(',', $ids));
	}
	
	public function insertNewComment($commentId, $commentDate)
	{
		$this->set('comment_count', $this->get('comment_count') + 1);
		if (!$this->get('first_comment_date') || $commentDate < $this->get('first_comment_date'))
		{
			$this->set('first_comment_date', $commentDate);
		}
		$this->set('last_comment_date', max($this->get('last_comment_date'), $commentDate));

		$latest = $this->get('latest_comment_ids');
		$ids = ($latest ? explode(',', $latest) : array());
		$ids[] = $commentId;

		if (count($ids) > 3)
		{
			$ids = array_slice($ids, -3);
		}

		$this->set('latest_comment_ids', implode(',', $ids));
	}

	protected function _getAlbumModel()
	{
		return $this->getModelFromCache('Album_Model_Album');
	}
}
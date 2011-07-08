<?php

class Album_DataWriter_AlbumPhotoComment extends XenForo_DataWriter
{
	const DATA_ALBUM_PHOTO_USER = 'photoUser';
	const DATA_ALBUM_PHOTO = 'photo';

	protected $_existingDataErrorPhrase = 'album_requested_photo_not_found';

	protected function _getFields()
	{
		return array(
			'album_photo_comment' => array(
				'album_photo_comment_id' => array(
					'type'          => self::TYPE_UINT,
					'autoIncrement' => true
				),
				'photo_id' => array(
					'type'     => self::TYPE_UINT,
					'required' => true
				),
				'user_id' => array(
					'type'     => self::TYPE_UINT,
					'required' => true
				),
				'username' => array(
					'type'          => self::TYPE_STRING,
					'required'      => true,
					'maxLength'     => 50,
					'requiredError' => 'please_enter_valid_name'
				),
				'comment_date' => array(
					'type'     => self::TYPE_UINT,  
					'required' => true,
					'default'  => XenForo_Application::$time
				),
				'likes' => array(
					'type'    => self::TYPE_UINT_FORCED,
					'default' => 0
				),
				'like_users' => array(
					'type'    => self::TYPE_SERIALIZED,
					'default' => 'a:0:{}'
				),
				'message' => array(
					'type'          => self::TYPE_STRING,
					'required'      => true,
					'requiredError' => 'please_enter_valid_message'
				)
			)
		);
	}

	protected function _getExistingData($data)
	{
		if (!$id = $this->_getExistingPrimaryKey($data))
		{
			return false;
		}

		return array('album_photo_comment' => $this->_getAlbumModel()->getAlbumPhotoCommentById($id));
	}

	protected function _getUpdateCondition($tableName)
	{
		return 'album_photo_comment_id = ' . $this->_db->quote($this->getExisting('album_photo_comment_id'));
	}

	protected function _preSave()
	{
		if ($this->isChanged('message'))
		{
			$maxLength = 420;
			if (utf8_strlen($this->get('message')) > $maxLength)
			{
				$this->error(new XenForo_Phrase('please_enter_message_with_no_more_than_x_characters', array('count' => $maxLength)), 'message');
			}
		}
	}

	protected function _postSave()
	{
		 $photoId = $this->get('photo_id');

		if ($this->isInsert())
		{
			$dw = XenForo_DataWriter::create('Album_DataWriter_AlbumPhoto');
			$dw->setExistingData($photoId);
			$dw->insertNewComment($this->get('album_photo_comment_id'), $this->get('comment_date'));
			$dw->save();

			$photoUser = $this->getExtraData(self::DATA_ALBUM_PHOTO_USER);
			if ($photoUser && $photoUser['user_id'] != $this->get('user_id'))
			{
				if (XenForo_Model_Alert::userReceivesAlert($photoUser, 'album_photo', 'comment_your_photo'))
				{
					XenForo_Model_Alert::alert(
						$photoUser['user_id'],
						$this->get('user_id'),
						$this->get('username'),
						'album',
						$photoId,
						'comment_your_photo'
					);
				}
			}

			$photo = $this->getExtraData(self::DATA_ALBUM_PHOTO);

			$otherCommenterIds = $this->_getAlbumModel()->getAlbumPhotoCommentUserIds($photoId);

			$otherCommenters = $this->_getUserModel()->getUsersByIds($otherCommenterIds, array(
				'join' => XenForo_Model_User::FETCH_USER_OPTION
			));

			$photoUserId = empty($photoUser) ? 0 : $photoUser['user_id'];

			foreach ($otherCommenters AS $otherCommenter)
			{
				switch ($otherCommenter['user_id'])
				{
					case $photoUserId:
					case $this->get('user_id'):
					case 0:
						break;

					default:
						if (XenForo_Model_Alert::userReceivesAlert($otherCommenter, 'album_photo', 'comment_other_commenter'))
						{
							XenForo_Model_Alert::alert(
								$otherCommenter['user_id'],
								$this->get('user_id'),
								$this->get('username'),
								'album_photo',
								$photoId,
								'comment_other_commenter'
							);
						}
						break;
				}
			}
		}
	}

	protected function _postDelete()
	{
		$dw = XenForo_DataWriter::create('Album_DataWriter_AlbumPhoto');
		$dw->setExistingData($this->get('photo_id'));
		$dw->rebuildAlbumPhotoCommentCounters();
		$dw->save();
		
		if ($likes = $this->get('likes'))
		{
			$this->getModelFromCache('XenForo_Model_Like')->deleteContentLikes(
				'album_photo_comment', $this->get('album_photo_comment_id')
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
	
	protected function _getUserModel()
	{
		return $this->getModelFromCache('XenForo_Model_User');
	}
}
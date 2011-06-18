<?php

class Album_Model_Album extends XenForo_Model
{
	const FETCH_ALBUM_USER	= 0x01;

	public function prepareAlbumConditions(array $conditions, array &$fetchOptions)
	{
		$db = $this->_getDb();
		$sqlConditions = array();

		if (isset($conditions['empty']))
		{
			if (!$conditions['empty'])
			{
				$sqlConditions[] = 'album.photo_count > 0';
			}
		}
		else
		{
			$sqlConditions[] = 'album.photo_count > 0';
		}

		return $this->getConditionsForClause($sqlConditions);
	}

	public function prepareAlbumFetchOptions(array $fetchOptions)
	{
		$selectFields = '';
		$joinTables = '';

		$db = $this->_getDb();

		if (isset($fetchOptions['likeUserId']))
		{
			if (empty($fetchOptions['likeUserId']))
			{
				$selectFields .= ',
					0 AS like_date';
			}
			else
			{
				$selectFields .= ',
					liked_content.like_date';
				$joinTables .= '
					LEFT JOIN xf_liked_content AS liked_content
						ON (liked_content.content_type = \'album\'
							AND liked_content.content_id = album.album_id
							AND liked_content.like_user_id = ' .$db->quote($fetchOptions['likeUserId']) . ')';
			}
		}

		if (!empty($fetchOptions['join']))
		{
			if ($fetchOptions['join'] & self::FETCH_ALBUM_USER)
			{
				$selectFields .= ',
					user.*,
					IF(user.username IS NULL, album.user_id, user.user_id) AS user_id';
				$joinTables .= '
					LEFT JOIN xf_user AS user ON
						(user.user_id = album.user_id)';
			}
		}

		return array(
			'selectFields' => $selectFields,
			'joinTables'   => $joinTables,
		);
	}

	public function getUserAlbumsByUserId($userId, array $fetchOptions = array(), array $conditions = array())
	{
		$sqlClauses = $this->prepareAlbumFetchOptions($fetchOptions);
		$whereClause = $this->prepareAlbumConditions($conditions, $fetchOptions);

		return $this->_getDb()->fetchAll('
			SELECT *
			' . $sqlClauses['selectFields'] . '
			FROM album
			' . $sqlClauses['joinTables'] . '
			WHERE album.user_id = ? AND ' . $whereClause
		, $userId);
	}

	public function getAlbumById($albumId, array $fetchOptions = array())
	{
		$sqlClauses = $this->prepareAlbumFetchOptions($fetchOptions);

		return $this->_getDb()->fetchRow('
			SELECT *
			' . $sqlClauses['selectFields'] . '
			FROM album
			' . $sqlClauses['joinTables'] . '
			WHERE album.album_id = ?
		', $albumId);
	}

	public function getAlbumsByIds($albumIds, array $fetchOptions = array(), array $conditions = array())
	{
		$sqlClauses = $this->prepareAlbumFetchOptions($fetchOptions);
		$whereClause = $this->prepareAlbumConditions($conditions, $fetchOptions);

		return $this->fetchAllKeyed('
			SELECT *
			' . $sqlClauses['selectFields'] . '
			FROM album
			' . $sqlClauses['joinTables'] . '
			WHERE album.album_id IN (' . $this->_getDb()->quote($albumIds) . ')
				AND ' . $whereClause . '
		', 'album_id');

	}

	public function getAllPhotosForAlbumById($albumId)
	{
		$photosData = $this->fetchAllKeyed('
			SELECT *
			FROM album_photo
			WHERE album_photo.album_id = ?
		', 'position', $albumId);

		if ($photosData)
		{
			$attachmentModel = $this->_getAttachmentModel();

			$attachments = $attachmentModel->getAttachmentsByContentId('album', $albumId);

			$photos = array();
			foreach ($photosData as $photoData)
			{
				if (isset($attachments[$photoData['attachment_id']]))
				{
					$photos[$photoData['position']] = array_merge($photoData, $attachments[$photoData['attachment_id']]);
				}
			}

			if (empty($photos))
			{
				return false;
			}

			$photos = $attachmentModel->prepareAttachments($photos);

			foreach ($photos as &$photo)
			{
				$photo = $this->preparePhoto($photo);
			}

			return $photos;
		}

		return false;
	}

	public function getNewestPhotosForAlbumById($albumId, $fetchOptions = array())
	{
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

		$photosData = $this->fetchAllKeyed($this->limitQueryResults('
			SELECT *
			FROM album_photo
			WHERE album_photo.album_id = ?
			ORDER BY album_photo.position ASC
			', $limitOptions['limit'], $limitOptions['offset']
		), 'position', $albumId);

		if ($photosData)
		{
			$attachmentModel = $this->_getAttachmentModel();

			$attachments = $attachmentModel->getAttachmentsByContentId('album', $albumId);

			$photos = array();
			foreach ($photosData as $photoData)
			{
				if (isset($attachments[$photoData['attachment_id']]))
				{
					$photos[$photoData['position']] = array_merge($photoData, $attachments[$photoData['attachment_id']]);
				}
			}

			if (empty($photos))
			{
				return false;
			}

			$photos = $attachmentModel->prepareAttachments($photos);

			foreach ($photos as &$photo)
			{
				$photo = $this->preparePhoto($photo);
			}

			return $photos;
		}

		return false;
	}

	public function getCoverPhotoForAlbum($album, $photos = false)
	{
		$cover = $this->getPhotoById($album['cover_photo_id']);

		if ($cover['album_id'] != $album['album_id'])
		{
			if (!$photos)
			{
				$photos = $this->getAllPhotosForAlbumById($album['album_id']);

				if (!$photos)
				{
					return false;
				}
			}

			$cover = end($photos);

			$dw = XenForo_DataWriter::create('Album_DataWriter_Album');
			$dw->setExistingData($album);
			$dw->set('cover_photo_id', $cover['photo_id']);
			$dw->save();
		}

		return $cover;
	}

	public function prepareAlbum($album, $getAllPhotos = false)
	{
		$album['name']	= XenForo_Helper_String::censorString($album['name']);

		if ($getAllPhotos)
		{
			$album['photos'] = $this->getAllPhotosForAlbumById($album['album_id']);
			$album['cover'] = $this->getCoverPhotoForAlbum($album, $album['photos']);
		}
		else
		{
			$album['cover'] = $this->getCoverPhotoForAlbum($album);
		}

		$album['permissions'] = $this->getPermissions();

		return $album;
	}

	public function prepareAlbums($albums)
	{
		foreach ($albums as &$album)
		{
			$album = $this->prepareAlbum($album);
		}

		return $albums;
	}

	public function rebuildAlbumById($albumId)
	{
		$photos = $this->getAllPhotosForAlbumById($albumId);

		$position = 1;

		if ($photos)
		{
			foreach ($photos as $photo)
			{
				$photoDw = XenForo_DataWriter::create('Album_DataWriter_AlbumPhoto');
				$photoDw->setExistingData($photo);
				$photoDw->set('position', $position);
				$photoDw->save();

				$position++;
			}
		}

		$photoCount = $position - 1;

		$albumDw = XenForo_DataWriter::create('Album_DataWriter_Album');
		$albumDw->setExistingData($albumId);
		$albumDw->set('photo_count', $photoCount);
		$albumDw->save();
	}

	public function getPhotoById($photoId, $postDelete = false)
	{
		$photoData = $this->_getDb()->fetchRow('
			SELECT *
			FROM album_photo
			WHERE album_photo.photo_id = ?
		', $photoId);

		if ($photoData)
		{
			$attachmentModel = $this->_getAttachmentModel();

			$attachment = $attachmentModel->getAttachmentById($photoData['attachment_id']);

			if (!$attachment)
			{
				if ($postDelete)
				{
					return $photoData;
				}

				return false;
			}

			$photo = array_merge($photoData, $attachment);
			$photo = $attachmentModel->prepareAttachment($photo);

			return $photo;
		}

		return false;
	}

	public function getPhotoByAttachmentId($attachmentId, $postDelete = false)
	{
		$photoData = $this->_getDb()->fetchRow('
			SELECT *
			FROM album_photo
			WHERE album_photo.attachment_id = ?
		', $attachmentId);

		if ($photoData)
		{
			$attachmentModel = $this->_getAttachmentModel();

			$attachment = $attachmentModel->getAttachmentById($attachmentId);

			if (!$attachment)
			{
				if ($postDelete)
				{
					return $photoData;
				}

				return false;
			}

			$photo = array_merge($photoData, $attachment);
			$photo = $attachmentModel->prepareAttachment($photo);

			return $photo;
		}

		return false;
	}

	public function removeAllPhotosFromAlbumById($albumId)
	{
		return $this->_getDb()->delete(
			'album_photo',
			'album_id =' . $this->_db->quote($albumId));
	}

	public function removeEmptyAlbums()
	{
		return $this->_getDb()->delete('album', 'photo_count < 1');
	}

	public function preparePhoto($photo, $page = false, array $album = array())
	{
		$boxHeight	 = 100;
		$photoHeight = $photo['thumbnail_height'];

		$offset = 0;

		if ($photoHeight < $boxHeight)
		{
			$difference = $boxHeight - $photoHeight;
			$offset		= intval(round($difference / 2));
		}

		if ($photoHeight > $boxHeight)
		{
			$difference = $photoHeight - $boxHeight;
			$offset		= intval(round(-$difference / 2));
		}

		$photo['offset'] = $offset;

		if ($page)
		{
			if ($photo['width'] > 668)
			{
				$ratio = 668 / $photo['width'];
				$photo['width']	 = 668;
				$photo['height'] = intval(round($photo['height'] * $ratio));
			}

			if ($photo['height'] > 720)
			{
				$ratio = 720 / $photo['height'];
				$photo['height'] = 720;
				$photo['width']	 = intval(round($photo['width'] * $ratio));
			}
			else if ($photo['height'] < 453)
			{
				$photo['margin-top'] = intval(round((453 - $photo['height']) / 2));
			}

			$previous = $photo['position'] - 1;
			$next	  = $photo['position'] + 1;
			if (isset($album['photos'][$previous]))
			{
				$photo['prev'] = $album['photos'][$previous];
			}
			else
			{
				$photo['prev'] = end($album['photos']);
			}

			if (isset($album['photos'][$next]))
			{
				$photo['next'] = $album['photos'][$next];
			}
			else
			{
				$photo['next'] = reset($album['photos']);
			}
		}

		$photo['description_raw'] = $photo['description'];
		$photo['description']  = XenForo_Helper_String::censorString($photo['description']);

		return $photo;
	}

	public function canUploadAndManageAttachments(array $viewingUser = null)
	{
		$permissions = $this->getPermissions($viewingUser);

		if (!$permissions['upload'])
		{
			return false;
		}

		return true;
	}

	public function getAttachmentParams(array $contentData)
	{
		if ($this->canUploadAndManageAttachments())
		{
			return array(
				'hash' => md5(uniqid('', true)),
				'content_type' => 'album',
				'content_data' => $contentData
			);
		}
		else
		{
			return false;
		}
	}

	public function getPermissions(array $viewingUser = null)
	{
			$this->standardizeViewingUserReference($viewingUser);

			$userPermissions = $viewingUser['permissions'];

			$permissions = array(
				'view'		  => $this->_hasPermission($userPermissions, 'album', 'view'),
				'view_photos' => $this->_hasPermission($userPermissions, 'album', 'view_photos'),
				'upload'	  => false,
				'manage'	  => false
			);

			if ($viewingUser['user_id'])
			{
				$permissions['upload'] = $this->_hasPermission($userPermissions, 'album', 'upload');
				$permissions['manage'] = $this->_hasPermission($userPermissions, 'album', 'manage');
			}

			return $permissions;
	}

	protected function _hasPermission($permissions, $group, $permission)
	{
		return XenForo_Permission::hasPermission($permissions, $group, $permission);
	}

	protected function _getAttachmentModel()
	{
		return $this->getModelFromCache('XenForo_Model_Attachment');
	}
}
<?php

class Album_Model_Album extends XenForo_Model
{
	const FETCH_ALBUM_USER  = 0x01;
	
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
	
	public function getUserAlbumsByUserId($userId, $fetchOptions = array())
	{
		$sqlClauses = $this->prepareAlbumFetchOptions($fetchOptions);
		
		return $this->_getDb()->fetchAll('
			SELECT *
			' . $sqlClauses['selectFields'] . '
			FROM album
			' . $sqlClauses['joinTables'] . '
			WHERE user_id = ?
		', $userId);
	}
	
	public function getAlbumById($albumId, $fetchOptions = array())
	{
		$sqlClauses = $this->prepareAlbumFetchOptions($fetchOptions);
		
		$album = $this->_getDb()->fetchRow('
			SELECT *
			' . $sqlClauses['selectFields'] . '
			FROM album
			' . $sqlClauses['joinTables'] . '
			WHERE album_id = ?
		', $albumId);

		return $album;
	}
	
	public function getAlbumsByIds($albumIds, array $fetchOptions = array())
	{
		$sqlClauses = $this->prepareAlbumFetchOptions($fetchOptions);
		
		return $this->fetchAllKeyed('
			SELECT *
			' . $sqlClauses['selectFields'] . '
			FROM album
			' . $sqlClauses['joinTables'] . '
			WHERE album.album_id IN (' . $this->_getDb()->quote($albumIds) . ')
		', 'album_id');
		
	}
	
	public function getAllPhotosForAlbumById($albumId)
	{
		$attachmentModel = $this->_getAttachmentModel();
		
		$photos = $attachmentModel->getAttachmentsByContentId('album', $albumId);
		$photos = $attachmentModel->prepareAttachments($photos);
		
		foreach ($photos as &$photo)
		{
			$photo = $this->preparePhoto($photo);
		}
		
		return $photos;
	}
	
	public function prepareAlbum($album, $getAllPhotos = false)
	{
		$album['name']  = XenForo_Helper_String::censorString($album['name']);
		$album['cover'] = $this->getPhotoById($album['cover_attachment_id']);
		
		if ($getAllPhotos)
		{
			$album['photos'] = $this->getAllPhotosForAlbumById($album['album_id']);
		}
		
		return $album;
	}
	
	public function prepareAlbums($albums)
	{
		foreach ($albums as &$album)
		{
			$album['name']  = XenForo_Helper_String::censorString($album['name']);
			$album['cover'] = $this->getPhotoById($album['cover_attachment_id']);
		}
		
		return $albums;
	}
	
	public function getPhotoById($attachmentId)
	{
		$attachmentModel = $this->_getAttachmentModel();
		
		$photo = $attachmentModel->getAttachmentById($attachmentId);
		
		if (empty($photo))
		{
			return false;
		}

		$photo = $attachmentModel->prepareAttachment($photo);

		return $photo;
	}
	
	public function preparePhoto($photo)
	{
		$boxHeight   = 100;
		$photoHeight = $photo['thumbnail_height']; 
		
		$offset = 0;
		
		if ($photoHeight < $boxHeight)
		{
			$difference = $boxHeight - $photoHeight;
			$offset     = intval(round($difference / 2));
		}
		
		if ($photoHeight > $boxHeight)
		{
			$difference = $photoHeight - $boxHeight;
			$offset     = intval(round(-$difference / 2));
		}
		
		$photo['offset'] = $offset;
		
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
				'view'        => $this->_hasPermission($userPermissions, 'album', 'view'),
				'view_photos' => $this->_hasPermission($userPermissions, 'album', 'view_photos'),
				'upload'      => false,
			);
			
			if ($viewingUser['user_id'])
			{
				$permissions['upload'] = $this->_hasPermission($userPermissions, 'album', 'upload');
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
<?php

class Album_NewsFeedHandler_AlbumPhoto extends XenForo_NewsFeedHandler_Abstract
{
	protected $_albumModel = null;
	
	public function getContentByIds(array $contentIds, $model, array $viewingUser)
	{
		$albumModel = $model->getModelFromCache('Album_Model_Album');
		
		$permissions = $albumModel->getPermissions($viewingUser);
		if (!$permissions['view_photos'])
		{
			return false;
		}
		
		$photos = $albumModel->getPhotosByIds($contentIds, false, array('join' => Album_Model_Album::FETCH_PHOTO_USER));
		
		$albumIds = array();
		foreach ($photos as $photo)
		{
			$albumIds[] = $photo['album_id'];
		}
		
		$albums = $albumModel->getAlbumsByIds($albumIds);
		
		foreach ($photos as $key => $photo)
		{
			if (!$albumModel->canViewAlbum($albums[$photo['album_id']], null, $null, $viewingUser))
			{
				unset($albums[$photo['album_id']]);
				unset($photos[$key]);
			}
		}
		
		$content = array();
		foreach ($photos as $photo)
		{
			$album = $albums[$photo['album_id']];
			$user  = $users[$album['user_id']];
			
			$content[$photo['photo_id']] = array(
				'photo' => $photo,
				'album' => $album,
			);
		}
		
		return $content;
	}
	
	protected function _prepareNewsFeedItemBeforeAction(array $item, $content, array $viewingUser)
	{
		$item['content']['attachments'] = array(
			$this->_getAlbumModel()->getPhotoById($content['photo']['photo_id'])
		);

		return $item;
	}
	
	protected function _getAlbumModel($model = null)
	{
		if (!$this->_albumModel)
		{
			if ($model)
			{
				$this->_albumModel = $model->getModelFromCache('Album_Model_Album');
			}
			else
			{
				$this->_albumModel = XenForo_Model::create('Album_Model_Album');
			}
		}
		
		return $this->_albumModel;
	}
}
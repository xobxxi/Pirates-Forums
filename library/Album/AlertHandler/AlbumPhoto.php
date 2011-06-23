<?php

class Album_AlertHandler_AlbumPhoto extends XenForo_AlertHandler_Abstract
{
	public function getContentByIds(array $contentIds, $model, $userId, array $viewingUser)
	{
		$albumModel = $model->getModelFromCache('Album_Model_Album');
		
		$permissions = $albumModel->getPermissions($viewingUser);
		if (!$permissions['view_photos'])
		{
			return false;
		}
		
		$photos = $albumModel->getPhotosByIds($contentIds);
		
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
			
			$content[$photo['photo_id']] = array(
				'photo' => $photo,
				'album' => $album,
			);
		}
		
		return $content;
	}
}
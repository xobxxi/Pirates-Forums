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

		$photos = $albumModel->getPhotosByIds($contentIds, false, array('join' => Album_Model_Album::FETCH_PHOTO_USER));
		
		foreach ($photos as $photo)
		{
		    $albumIds[] = $photo['album_id'];
		}
		
		$albums = $albumModel->getAlbumsByIds($albumIds);
		
		foreach ($photos as &$photo)
		{
		    $photo['album'] = $albums[$photo['album_id']];
		}
		
		return $photos;
	}
}
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

		return $albumModel->getPhotosByIds($contentIds, false, array('join' => Album_Model_Album::FETCH_PHOTO_USER));
	}
}
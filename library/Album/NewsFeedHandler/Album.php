<?php

class Album_NewsFeedHandler_Album extends XenForo_NewsFeedHandler_Abstract
{
	
	public function getContentByIds(array $contentIds, $model, array $viewingUser)
	{
		$albums = array();
		
		$albumModel = $model->getModelFromCache('Album_Model_Album');
		
		$permissions = $albumModel->getPermissions($viewingUser); // make
		
		if (!$permissions['view'])
		{
			return $albums;
		}
		
		return $albumModel->getAlbumsByIds($contentIds);
	}
}
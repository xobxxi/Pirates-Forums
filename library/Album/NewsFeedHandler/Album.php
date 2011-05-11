<?php

class Album_NewsFeedHandler_Album extends XenForo_NewsFeedHandler_Abstract
{
	public function getContentByIds(array $contentIds, $model, array $viewingUser)
	{	
		$albumModel = $model->getModelFromCache('Album_Model_Album');
		
		$permissions = $albumModel->getPermissions($viewingUser);
		
		if (!$permissions['view'])
		{
			return array();
		}
		
		return $albumModel->getAlbumsByIds($contentIds);
	}
	
	protected function _prepareName(array $item)
	{
		$item['name'] = unserialize($item['extra_data']);
		unset($item['extra_data']);
		
		return $item;
	}
	
	protected function _preparePhotos(array $item)
	{
		$item['photos'] = unserialize($item['extra_data']);
		unset($item['extra_data']);
		
		return $item;
	}
}
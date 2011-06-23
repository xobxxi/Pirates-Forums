<?php

class Album_NewsFeedHandler_Album extends XenForo_NewsFeedHandler_Abstract
{
	protected $_albumModel = null;

	public function getContentByIds(array $contentIds, $model, array $viewingUser)
	{
		$albumModel = $this->_getAlbumModel();

		$permissions = $albumModel->getPermissions($viewingUser);

		if (!$permissions['view'])
		{
			return array();
		}

		$albums = $albumModel->getAlbumsByIds($contentIds);
		
		foreach ($albums as $key => $album)
		{
			if (!$albumModel->canViewAlbum($album))
			{
				unset($albums[$key]);
			}
		}
		
		return $albums;
	}
	
	protected function _prepareAdd(array $item)
	{
		$item['content']['attachments'] = $this->_getAlbumModel()->getNewestPhotosForAlbumById(
			$item['content']['album_id'],
			array('limit' => 10)
		);
		
		return $item;
	}

	protected function _prepareName(array $item)
	{
		$item['name'] = unserialize($item['extra_data']);
		unset($item['extra_data']);

		return $item;
	}

	protected function _preparePhotos(array $item)
	{

		$extraData = unserialize($item['extra_data']);
		unset($item['extra_data']);
		
		if ($extraData['new'] > 1)
		{
			$item['single'] = false;
		}
		else
		{
			$item['single'] = true;
		}

		$item['content']['attachments'] = $this->_getAlbumModel()->getNewestPhotosForAlbumById(
			$item['content']['album_id'],
			array('limit' => min($extraData['new'], 10))
		);

		return $item;
	}

	protected function _getAlbumModel()
	{
		if (!$this->_albumModel)
		{
			$this->_albumModel = XenForo_Model::create('Album_Model_Album');
		}

		return $this->_albumModel;
	}
}
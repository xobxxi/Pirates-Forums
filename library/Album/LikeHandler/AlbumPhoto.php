<?php

class Album_LikeHandler_AlbumPhoto extends XenForo_LikeHandler_Abstract
{
	public function incrementLikeCounter($contentId, array $latestLikes, $adjustAmount = 1)
	{
		$dw = XenForo_DataWriter::create('Album_DataWriter_AlbumPhoto');
		$dw->setExistingData($contentId);
		$dw->set('likes', $dw->get('likes') + $adjustAmount);
		$dw->set('like_users', $latestLikes);
		$dw->save();
	}

	public function getContentData(array $contentIds, array $viewingUser)
	{
		$albumModel = XenForo_Model::create('Album_Model_Album');
		
		$permissions = $albumModel->getPermissions($viewingUser);
		if (!$permissions['view_photo'])
		{
			return false;
		}
	
		return $albumModel->getAlbumsByIds($contentIds);
	}
	
	public function getListTemplateName()
	{
		return 'news_feed_album_photo_like';
	}
}
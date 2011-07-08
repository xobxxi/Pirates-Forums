<?php

class Album_AlertHandler_AlbumPhotoComment extends XenForo_AlertHandler_Abstract
{
	public function getContentByIds(array $contentIds, $model, $userId, array $viewingUser)
	{
		$albumModel = $model->getModelFromCache('Album_Model_Album');
		
		$permissions = $albumModel->getPermissions();
		if (!$permissions['view_photos'])
		{
			return false;
		}
		
		$comments = $albumModel->getAlbumPhotoCommentsByIds(
			$contentIds, array('join' => Album_Model_Album::FETCH_PHOTO_COMMENT_USER)
		);
		
		$photoIds = array();
		foreach ($comments as $comment)
		{
			$photoIds[] = $comment['photo_id'];
		}
		
		$photos = $albumModel->getPhotosByIds($photoIds, false, array('join' => Album_Model_Album::FETCH_PHOTO_USER));
		
		foreach ($photos as $photo)
		{
		    $albumIds[] = $photo['album_id'];
		}
		
		$albums = $albumModel->getAlbumsByIds($albumIds);
		
		foreach ($comments as &$comment)
		{
			$comment['photo'] = $photos[$comment['photo_id']];
			$comment['album'] = $albums[$photos[$comment['photo_id']]['album_id']];
		}
		
		return $comments;
	}
}
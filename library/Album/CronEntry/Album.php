<?php

class Album_CronEntry_Album
{
	public static function removeEmptyAlbums()
	{
		$albumModel = XenForo_Model::create('Album_Model_Album');
		return $albumModel->removeEmptyAlbums();
	}
}
<?php

class Album_ReportHandler_AlbumPhoto extends XenForo_ReportHandler_Abstract
{
	protected $_albumModel = null;

	public function getReportDetailsFromContent(array $content)
	{
		$album = $this->_getAlbumModel()->getAlbumById($content['album_id']);
		$user  = XenForo_Model::create('XenForo_Model_User')->getUserById($album['user_id']);

		return array(
			$content['photo_id'],
			$album['user_id'],
			array(
				'photo'             => $content,
				'album'             => $album,
				'user'              => $user
			)
		);
	}

	public function getVisibleReportsForUser(array $reports, array $viewingUser)
	{
		$permissions = $this->_getAlbumModel()->getPermissions($viewingUser);
		
		if ($permissions['manage'])
		{
			return $reports;
		}
		
		return array();
	}

	public function getContentTitle(array $report, array $contentInfo)
	{
		return new XenForo_Phrase('album_photo_in_xs_album_y', array(
			'username' => $contentInfo['user']['username'],
			'album'    => $contentInfo['album']['name'])
		);
	}

	public function getContentLink(array $report, array $contentInfo)
	{
		return XenForo_Link::buildPublicLink('albums/view-photo', $contentInfo['photo']);
	}

	public function viewCallback(XenForo_View $view, array &$report, array &$contentInfo)
	{
		return $view->createTemplateObject('album_photo', array(
			'photo' => $contentInfo['photo'],
			'album' => $contentInfo['album'],
			'user'  => $contentInfo['user']
		));
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

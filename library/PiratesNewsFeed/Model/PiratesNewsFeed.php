<?php

class PiratesNewsFeed_Model_PiratesNewsFeed  extends XenForo_Model
{
	public function updateNews($rebuild = false, $alert = false)
	{
		if (!$rebuild)
		{
			$existing = $this->getNews();
		}
		else
		{
			$existing = array();
		}

		$latestNews = reset($existing);

		$options = XenForo_Application::get('options');
		$itemsCount = $options->piratesNewsFeed_count;

		$feed = 'http://blog.piratesonline.go.com/blog/pirates/feed2/entries/atom?numEntries=' . $itemsCount;
		if (!$data = @simplexml_load_file($feed))
		{
			return $existing;
		}

		$changes = false;
		$updated = array();
		foreach ($data->entry as $entry)
		{
			$id = strtotime((string) $entry->published);

			if (!isset($latestNews['id']) || $id > $latestNews['id'])
			{
				$news = array(
					'id'     => $id,
					'title'  => (string) $entry->title,
					'url'    => (string) $entry->link->attributes()->href,
					'date'   => date('m/d/y', $id),
					'posted' => false
				);

				$updated[$id] = $news;

				$changes = true;
			}
		}

		$blogs = $updated + $existing;

		$extra = count($blogs) - $itemsCount;
		if ($extra != 0)
		{
			$i = 1;
			foreach ($blogs as $id => $news)
			{
				if ($i > $itemsCount)
				{
					unset($blogs[$id]);
					$changes = true;
				}

				$i++;
			}
		}

		if ($changes)
		{
			$this->_getDataRegistryModel()->set('PiratesNewsFeed', $blogs);

			if ($alert)
			{
				$newsMembers = $this->getNewsPosters();
				$latestNews = reset($blogs);
				foreach ($newsMembers as $user)
				{
					XenForo_Model_Alert::alert(
						$user['user_id'],
						$user['user_id'],
						$user['username'],
						'news',
						$latestNews['id'],
						'new'
					);
				}
			}
		}

		return $blogs;
	}

	public function resetData()
	{
		return $this->_getDataRegistryModel()->delete('PiratesNewsFeed');
	}

	public function getNews()
	{
		$dataRegistryModel = $this->_getDataRegistryModel();

		if (!$blogs = $dataRegistryModel->get('PiratesNewsFeed'))
		{
			$blogs = $this->updateNews(true);
		}

		return $blogs;
	}

	public function getNewsContent($newsId)
	{
		$blogs = $this->getNews();

		if (!isset($blogs[$newsId]))
		{
			return false;
		}

		$news = $blogs[$newsId];

		$entry = curl_init();
		curl_setopt($entry, CURLOPT_URL, $news['url']);
		curl_setopt($entry, CURLOPT_RETURNTRANSFER, 1);
		$contents = curl_exec($entry);
		curl_close($entry);

		if (!$contents)
		{
			return false;
		}

		if (!preg_match("/\<div class\=\"news_body\"\>(.+)\t+\s+\<br\>\<br\>/sm", $contents, $matches))
		{
			return false;
		}

		$news['content'] = $matches[1];

		$search = array(
			'<p>'    => '',
			'</p>'   => '<br /><br />',
		);

		$news['content']  = str_replace(array_keys($search), array_values($search), $news['content']);
		$news['content'] .= '<a href="' . $news['url'] . '" target="_blank">' . $news['url'] . '</a>';

		$news['title'] .= " ({$news['date']})";

		return $news;
	}

	public function markPosted($newsId)
	{
		$blogs = $this->getNews();

		if (!$blogs[$newsId])
		{
			return false;
		}

		$blogs[$newsId]['posted'] = true;

		return $this->_getDataRegistryModel()->set('PiratesNewsFeed', $blogs);
	}

	public function markNotPosted($newsId)
	{
		$blogs = $this->getNews();

		if (!$blogs[$newsId])
		{
			return false;
		}

		$blogs[$newsId]['posted'] = false;

		return $this->_getDataRegistryModel()->set('PiratesNewsFeed', $blogs);
	}

	public function getNewsPosters()
	{
		$options = XenForo_Application::get('options');
		$newsGroupId = $options->piratesNewsFeed_newsGroupId;

		$userGroupModel = $this->_getUserGroupModel();
		$newsPosterIds  = array_keys($userGroupModel->getUserIdsInUserGroup($newsGroupId));

		$userModel = $this->_getUserModel();
		return $userModel->getUsersByIds($newsPosterIds);
	}

	public function canManageNews($viewingUser = null, &$errorPhraseKey = '')
	{
		$this->standardizeViewingUserReference($viewingUser);

		if (!$viewingUser['user_id'])
		{
			return false;
		}

		return XenForo_Permission::hasPermission($viewingUser['permissions'], 'general', 'piratesNewsFeed_manage');
	}

	protected function _getDataRegistryModel()
	{
		return $this->getModelFromCache('XenForo_Model_DataRegistry');
	}

	protected function _getUserGroupModel()
	{
		return $this->getModelFromCache('XenForo_Model_UserGroup');
	}

	protected function _getUserModel()
	{
		return $this->getModelFromCache('XenForo_Model_User');
	}
}
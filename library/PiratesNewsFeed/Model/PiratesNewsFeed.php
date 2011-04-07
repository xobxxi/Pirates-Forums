<?php

class PiratesNewsFeed_Model_PiratesNewsFeed  extends XenForo_Model
{	
	public function markPosted($newsId)
	{
		$blogs = $this->getLatestNews();
		
		if (!$blogs[$newsId])
		{
			return false;
		}
		
		$blogs[$newsId]['posted'] = true;
		
		$this->_getDataRegistryModel()->set('PiratesNewsFeed', $blogs);
		
		return true;
	}
	
	public function markNotPosted($newsId)
	{
		$blogs = $this->getLatestNews();
		
		if (!$blogs[$newsId])
		{
			return false;
		}
		
		$blogs[$newsId]['posted'] = false;
		
		$this->_getDataRegistryModel()->set('PiratesNewsFeed', $blogs);
		
		return true;
	}
	
	public function getLatestNews()
	{
		$dataRegistryModel = $this->_getDataRegistryModel();
		
		if (!$blogs = $dataRegistryModel->get('PiratesNewsFeed'))
		{
			$options = XenForo_Application::get('options');

			$itemsCount = $options->piratesNewsFeed_count;
		
			$feed = 'http://blog.piratesonline.go.com/blog/pirates/feed2/entries/atom?numEntries=' . $itemsCount;
			$data = simplexml_load_file($feed);
			
			$news = array();
			foreach ($data->entry as $entry)
			{
				$newsItem['id']     = strtotime((string) $entry->published);
				$newsItem['title']  = (string) $entry->title;
				$newsItem['url']    = (string) $entry->link->attributes()->href;
				$newsItem['date']   = date('m/d/y', $newsItem['id']);
				$newsItem['posted'] = false;
				
				$blogs[$newsItem['id']] = $newsItem;
			}
			
			$dataRegistryModel->set('PiratesNewsFeed', $blogs);
		}
			
		return $blogs;
	}
	
	public function getNewsContent($newsId)
	{
		$blogs = $this->getLatestNews();
		
		if (!$news = $blogs[$newsId])
		{
			return false;
		}
		
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
		
		$contents = $matches[1];
		
		$search = array(
			'<p>'    => '',
			'</p>'   => '<br /><br />'
		);

		$news['content']  = str_replace(array_keys($search), array_values($search), $contents);
		$news['content'] .= '<a href="' . $news['url'] . '" target="_blank">' . $news['url'] . '</a>';
		
		$news['title'] .= " ({$news['date']})";
		
		return $news;
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
}
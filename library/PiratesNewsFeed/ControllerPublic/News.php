<?php

class PiratesNewsFeed_ControllerPublic_News extends XenForo_ControllerPublic_Abstract
{	
	public function actionIndex()
	{
		$piratesNewsFeedModel = $this->_getPiratesNewsFeedModel();
		
		if (!$piratesNewsFeedModel->canManageNews())
		{
			throw $this->getNoPermissionResponseException();
		}
		
		$blogs = $piratesNewsFeedModel->getNews();
		
		if (empty($blogs))
		{
			return $this->responseError(new XenForo_Phrase('piratesNewsFeed_news_could_not_be_fetched'));
		}
		
		$options = XenForo_Application::get('options');
		$newsForumId = $options->piratesNewsFeed_forumId;
		
		$newsForum = $this->_getForumModel()->getForumById($newsForumId);

		$viewParams = array(
			'forum' => $newsForum,
			'blogs' => $blogs
		);
		return $this->responseView(
			'PiratesNewsFeed_ViewPublic_News_Index',
			'piratesNewsFeed_news',
			$viewParams
		);
	}
	
	public function actionRefresh()
	{
		$piratesNewsFeedModel = $this->_getPiratesNewsFeedModel();
		
		if (!$piratesNewsFeedModel->canManageNews())
		{
			throw $this->getNoPermissionResponseException();
		}
		
		$piratesNewsFeedModel->updateNews();
		
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::RESOURCE_UPDATED,
			XenForo_Link::buildPublicLink('news'),
			new XenForo_Phrase('piratesNewsFeed_news_refreshed')
		);
	}
	
	public function actionMarkPosted()
	{
		$piratesNewsFeedModel = $this->_getPiratesNewsFeedModel();
		
		if (!$piratesNewsFeedModel->canManageNews())
		{
			throw $this->getNoPermissionResponseException();
		}
		
		$newsId = $this->_input->filterSingle('news_id', XenForo_Input::UINT);
		
		if (!$newsId)
		{
			return $this->responseError(new XenForo_Phrase('piratesNewsFeed_no_news_id'));
		}

		$piratesNewsFeedModel->markPosted($newsId);

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::RESOURCE_UPDATED,
			XenForo_Link::buildPublicLink('news'),
			new XenForo_Phrase('piratesNewsFeed_news_marked_posted')
		);
	}

	function actionMarkNotposted()
	{
		$piratesNewsFeedModel = $this->_getPiratesNewsFeedModel();
		
		if (!$piratesNewsFeedModel->canManageNews())
		{
			throw $this->getNoPermissionResponseException();
		}
		
		$newsId = $this->_input->filterSingle('news_id', XenForo_Input::UINT);

		if (!$newsId)
		{
			return $this->responseError(new XenForo_Phrase('piratesNewsFeed_no_news_id'));
		}

		$piratesNewsFeedModel->markNotPosted($newsId);

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::RESOURCE_UPDATED,
			XenForo_Link::buildPublicLink('news'),
			new XenForo_Phrase('piratesNewsFeed_news_marked_not_posted')
		);
	}

	public function actionPostNews()
	{
		$piratesNewsFeedModel = $this->_getPiratesNewsFeedModel();
		
		if (!$piratesNewsFeedModel->canManageNews())
		{
			throw $this->getNoPermissionResponseException();
		}
		
		$newsId = $this->_input->filterSingle('news_id', XenForo_Input::INT);
		
		if (!$newsId)
		{
			return $this->responseError(new XenForo_Phrase('piratesNewsFeed_no_news_id'));
		}
		
		$options = XenForo_Application::get('options');
		$newsForumId = $options->piratesNewsFeed_forumId;
		
		if (!$news = $piratesNewsFeedModel->getNewsContent($newsId))
		{
			return $this->responseError(new XenForo_Phrase('piratesNewsFeed_news_could_not_be_fetched'));
		}

		if ($news['posted'])
		{
			return $this->responseError(new XenForo_Phrase('piratesNewsFeed_news_already_posted'));
		}
		
		if ($this->isConfirmedPost()) 
		{
			$news['content'] = trim(XenForo_Html_Renderer_BbCode::renderFromHtml($news['content']));

			$thread = PiratesForums_Helper_Thread::create(
				$newsForumId,
				XenForo_Visitor::getInstance(),
			    $news['title'],
				$news['content']
			);

			$piratesNewsFeedModel->markPosted($news['id']);

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::RESOURCE_CREATED,
				XenForo_Link::buildPublicLink('threads', $thread),
				new XenForo_Phrase('piratesNewsFeed_news_posted')
			);
		}
		
		$newsForum = $this->_getForumModel()->getForumById($newsForumId);
		
		$viewParams = array(
			'forum' => $newsForum,
			'news'  => $news
		);
		return $this->responseView(
			'PiratesNewsFeed_ViewPublic_News_PostNews',
			'piratesNewsFeed_confirm_post',
			$viewParams
		);
	}
	
	protected function _getPiratesNewsFeedModel()
	{
		return $this->getModelFromCache('PiratesNewsFeed_Model_PiratesNewsFeed');
	}
	
	protected function _getForumModel()
	{
		return $this->getModelFromCache('XenForo_Model_Forum');
	}
}
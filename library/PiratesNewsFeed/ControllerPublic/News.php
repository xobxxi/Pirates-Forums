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
		
		$blogs = $piratesNewsFeedModel->getLatestNews();

		$viewParams = array(
			'blogs'          => $blogs
		);
		return $this->responseView(
			'PiratesNewsFeed_ViewPublic_Forum_DisplayNews',
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
			$this->responseError(new XenForo_Phrase('piratesNewsFeed_no_news_id'));
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
			$this->responseError(new XenForo_Phrase('piratesNewsFeed_no_news_id'));
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
			$this->responseError(new XenForo_Phrase('piratesNewsFeed_no_news_id'));
		}
		
		if (!$news = $piratesNewsFeedModel->getNewsContent($newsId))
		{
			$this->responseError(new XenForo_Phrase('piratesNewsFeed_news_could_not_be_fetched'));
		}
		
		$options = XenForo_Application::get('options');
		$newsForumId = $options->piratesNewsFeed_forumId;
		
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
	
	protected function _getPiratesNewsFeedModel()
	{
		return $this->getModelFromCache('PiratesNewsFeed_Model_PiratesNewsFeed');
	}
}
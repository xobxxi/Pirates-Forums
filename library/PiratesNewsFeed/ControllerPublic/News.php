<?php

class PiratesNewsFeed_ControllerPublic_News extends XenForo_ControllerPublic_Abstract
{	
	public function actionIndex()
	{
		$piratesNewsFeedModel = $this->_getPiratesNewsFeedModel();
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
		// fix
		
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::RESOURCE_UPDATED,
			XenForo_Link::buildPublicLink('news'),
			new XenForo_Phrase('piratesNewsFeed_news_refreshed')
		);
	}
	
	public function actionMarkPosted()
	{
		$newsId = $this->_input->filterSingle('news_id', XenForo_Input::UINT);
		
		if (!$newsId)
		{
			$this->responseError(new XenForo_Phrase('piratesNewsFeed_no_news_id'));
		}
		
		$pirateNewsFeedModel = $this->_getPiratesNewsFeedModel();
		$pirateNewsFeedModel->markPosted($newsId);

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::RESOURCE_UPDATED,
			XenForo_Link::buildPublicLink('news'),
			new XenForo_Phrase('piratesNewsFeed_news_marked_posted')
		);
	}

	function actionMarkNotposted()
	{
			$newsId = $this->_input->filterSingle('news_id', XenForo_Input::UINT);

			if (!$newsId)
			{
				$this->responseError(new XenForo_Phrase('piratesNewsFeed_no_news_id'));
			}

			$pirateNewsFeedModel = $this->_getPiratesNewsFeedModel();
			$pirateNewsFeedModel->markNotPosted($newsId);

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::RESOURCE_UPDATED,
				XenForo_Link::buildPublicLink('news'),
				new XenForo_Phrase('piratesNewsFeed_news_marked_not_posted')
			);
	}

	public function actionPostNews()
	{
		$newsId = $this->_input->filterSingle('news_id', XenForo_Input::INT);
		
		if (!$newsId)
		{
			$this->responseError(new XenForo_Phrase('piratesNewsFeed_no_news_id'));
		}
		
		$piratesNewsFeedModel = $this->_getPiratesNewsFeedModel();
		
		if (!$news = $piratesNewsFeedModel->getNewsContent($newsId))
		{
			$this->responseError(new XenForo_Phrase('piratesNewsFeed_no_news_item_found'));
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
	
	protected function _getDataRegistryModel()
	{
		return $this->getModelFromCache('XenForo_Model_DataRegistry');
	}
}
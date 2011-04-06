<?php

class PiratesNewsFeed_ControllerPublic_Forum extends XFCP_PiratesNewsFeed_ControllerPublic_Forum
{
	// TODO
	// Customize templates
	
	Const POSTER_RANDOM = 1; // ?

	/**
	 *
	 * Mark an specific news article as "not posted"
	 */
	function actionMarkNotposted()
	{
		$newsId = $this->_input->filterSingle('news_id', XenForo_Input::INT);

		$pirateNewsFeedModel = $this->_getPiratesNewsFeedModel();

		if (!$newsId) {
			$this->responseError(new XenForo_Phrase('piratesNewsFeed_no_news_id'));
		}
		
		$pirateNewsFeedModel->markNotPosted($newsId);

		return $this->responseView(
			'PiratesNewsFeed_ViewPublic_Forum_MarkNotPosted',
			'PiratesNewsFeed_news_success_generic',
			array()
		);
	}

	/**
	 *
	 * Marks an specific news article as "posted"
	 */
	function actionMarkPosted()
	{
		$newsId = $this->_input->filterSingle('news_id', XenForo_Input::INT);

		if (!$newsId) {
			$this->responseError(new XenForo_Phrase('piratesNewsFeed_no_news_id'));
		}
		$pirateNewsFeedModel = $this->_getPiratesNewsFeedModel();
		$pirateNewsFeedModel->markPosted($newsId);

		return $this->responseView(
			'PiratesNewsFeed_ViewPublic_Forum_MarkPosted',
			'PiratesNewsFeed_news_success_generic',
			array()
		);
	}

	/**
	 *
	 * Display news list
	 */
	public function actionDisplayNews()
	{
		$visitor = XenForo_Visitor::getInstance();
		$options = XenForo_Application::get('options');
		
		$itemsCount = $options->piratesNewsFeed_count;
		$forumId    = $options->piratesNewsFeed_forumId;

		$pirateNewsFeedModel = $this->_getPiratesNewsFeedModel();
		$forumModel          = $this->_getForumModel();

		$blogs = $pirateNewsFeedModel->_modelRegistry();
		if (!$blogs) {
			$blogs = $pirateNewsFeedModel->feed($forumId, $itemsCount);
		}

		$forum = $forumModel->getForumById($forumId);

		$viewParams = array(
			'blog'          => $blogs,
			'canManageNews' => $visitor['is_admin'], // move to actual permissions
		);

		return $this->responseView(
			'PiratesNewsFeed_ViewPublic_Forum_DisplayNews',
			'PiratesNewsFeed_news_template',
			$viewParams
		);
	}

	/**
	 *
	 * Removes news feed from cache so it can be fetched/refreshed again
	 */
	function actionRefreshNews()
	{
		$registryModel = $this->_getDataRegistryModel();

		$registryModel->delete('PiratesNewsFeedCache');

		return $this->responseView(
			'PiratesNewsFeed_ViewPublic_Forum_MarkNotPosted',
			'PiratesNewsFeed_news_success_generic',
			array()
		);
	}

	/**
	 *
	 * Post a news article to the news forum
	 */
	public function actionPostNews()
	{
		$options = XenForo_Application::get('options');
		
		$forumId     = $options->piratesNewsFeed_forumId;
		$userIds     = explode(',', $options->piratesNewsFeed_userIds);
		$poster      = $options->piratesNewsFeed_poster;
		$newsGroupId = $options->piratesNewsFeed_groupId;

		$pirateNewsFeedModel = $this->_getPiratesNewsFeedModel();
		$registryModel       = $this->_getDataRegistryModel();

		$blogs = $registryModel->get('PiratesNewsFeedCache');

		$newsId = $this->_input->filterSingle('news_id', XenForo_Input::INT);

		$news = $blogs[$newsId];

		$message = $pirateNewsFeedModel->fetch($news['url']);

		if (!$message) {
			$this->responseError(new XenForo_Phrase('piratesNewsFeed_no_news_item_found'));
		}
		
		$options    = array('stripLinkPathTraversal' => XenForo_Visitor::isBrowsingWith('firefox'));
		$newMessage = trim(XenForo_Html_Renderer_BbCode::renderFromHtml($message, $options));

		$user = $pirateNewsFeedModel->getNewsPoster();
		
		if (!$user) {
			if ($poster == self::POSTER_RANDOM && !$newsGroupId) {
				$this->responseError(new XenForo_Phrase('piratesNewsFeed_no_news_group_set'));
			} else {
				$this->responseError(new XenForo_Phrase('piratesNewsFeed_no_news_user_available'));
			}

		}

		PiratesForums_Helper_Thread::create(
			$forumId,
			$user,
			str_replace("\\'", "'", $news['title']) .' '. $news['date'],
			$newMessage
		);

		$pirateNewsFeedModel->markPosted($news['stamp']);

		return $this->responseView(
			'PiratesNewsFeed_ViewPublic_Forum_PostNews',
			'PiratesNewsFeed_news_posted',
			array()
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
	
	protected function _getDataRegistryModel()
	{
		return $this->getModelFromCache('XenForo_Model_DataRegistry');
	}
}
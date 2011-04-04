<?php

class PiratesNewsFeed_ControllerPublic_Forum extends XFCP_PiratesNewsFeed_ControllerPublic_Forum
{
	// TODO
	// getSessionActivityDetailsForList()
	// Proper error handling
	// Customize templates
	// Standardize options prefix
	
	Const POSTER_RANDOM = 1; // ?

	/**
	 *
	 * Mark an specific news article as "not posted"
	 */
	function actionMarkNotposted()
	{
		$newsId = $this->_input->filterSingle('news_id', XenForo_Input::INT);

		$pirateNewsFeedModel = $this->_getPiratesNewsFeedModel();

		if ($newsId) {
			return $this->responseView(
				'PiratesNewsFeed_ViewPublic_Forum_MarkNotPosted',
				'PiratesNewsFeed_generic_error',
				array()
			);
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
			return $this->responseView(
				'PiratesNewsFeed_ViewPublic_Forum_MarkPosted',
				'PiratesNewsFeed_generic_error',
				array()
			);
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
		
		$itemsCount = $options->news_count;
		$forumId    = $options->news_forum_id;

		$pirateNewsFeedModel = $this->_getPiratesNewsFeedModel();
		$forumModel          = $this->_getForumModel();

		$blogs = $pirateNewsFeedModel->registry();
		if (!$blogs) {
			$blogs = $pirateNewsFeedModel->feed($forumId, $itemsCount);
		}

		$forum = $forumModel->getForumById($forumId);

		$viewParams = array(
			'blog'          => $blogs,
			'canManageNews' => $visitor['is_admin'], // move to actual permissions
			'refreshLink'   => XenForo_Link::buildPublicLink("forums/refresh-news", $forum) // this can be built in the template
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

		//$this->actionDisplayNews(); ??

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
		
		$forumId     = $options->news_forum_id;
		$userIds     = explode(',', $options->news_users);
		$poster      = $options->news_poster_options;
		$newsGroupId = $options->news_group_id;

		$pirateNewsFeedModel = $this->_getPiratesNewsFeedModel();
		$registryModel       = $this->_getDataRegistryModel();

		$blogs = $registryModel->get('PiratesNewsFeedCache');

		$newsId = $this->_input->filterSingle('news_id', XenForo_Input::INT);

		$news = $blogs[$newsId];

		$message = $pirateNewsFeedModel->fetch($news['url']);

		if (!$message) {
			//$this->error(new XenForo_Phrase('error_msg'), 'group_id'); ??
			return $this->responseView(
				'PiratesNewsFeed_ViewPublic_Forum_PostNews',
				'PiratesNewsFeed_news_error',
				array()
			);
		}
		
		$options    = array('stripLinkPathTraversal' => XenForo_Visitor::isBrowsingWith('firefox'));
		$newMessage = trim(XenForo_Html_Renderer_BbCode::renderFromHtml($message, $options));

		$user = $pirateNewsFeedModel->getNewsPoster();
		
		if (!$user) {
			if ($poster == self::POSTER_RANDOM && !$newsGroupId) {
				return $this->responseView(
					'PiratesNewsFeed_ViewPublic_Forum_PostNews',
					'PiratesNewsFeed_news_no_postergroup',
					array()
				);
			} else {
				return $this->responseView(
					'PiratesNewsFeed_ViewPublic_Forum_PostNews',
					'PiratesNewsFeed_news_error',
					array()
				);
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
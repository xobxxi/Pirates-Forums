<?php

class PiratesNewsFeed_ControllerPublic_Forum extends XFCP_PiratesNewsFeed_ControllerPublic_Forum
{
	/**
	 *
	 * Mark an specific news article as "not posted" TODO: check permissions, getSessionActivityDetailsForList
	 */

	/**
	 *
	 * Display news list
	 */
	public function actionDisplayNews()
	{
		$options = XenForo_Application::get('options');
		$forumId = $options->piratesNewsFeed_news_forum_id;
		
		$pirateNewsFeedModel = $this->_getPirateNewsFeedModel();

		$blogs = $pirateNewsFeedModel->registry();
		if(empty($blogs))
		{
			$blogs = $pirateNewsFeedModel->feed($forumId, $options->news_count);
		}

		$forumModel = $this->_getForumModel();
		$forum      = $forumModel->getForumById($forumId);

		$visitor = XenForo_Visitor::getInstance();
		
		$viewParams = array(
			'blog'          => $blogs,
			'canManageNews' => $visitor['is_admin'],
			'forum'         => $forum
		);

		return $this->responseView(
			'PiratesNewsFeed_ViewPublic_Forum_DisplayNews',
			'piratesNewsFeed_news',
			$viewParams
		);
	}

	/**
	 *
	 * Removes news feed from cache so it can be fetched/refreshed again
	 */
	public function actionRefreshNews()
	{		
		$this->_getPirateNewsFeedModel()->deleteRegistry();

		$options = XenForo_Application::get('options');
		
		$forum = $this->_getForumModel()->getForumById($options->piratesNewsFeed_news_forum_id);
		
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::RESOURCE_CREATED, //change response
			XenForo_Link::buildPublicLink('forums/display-news', $forum),
			new XenForo_Phrase(
				   'pirateProfile_the_pirate_has_been_saved_successfully' //change phrase
			)
		);
	}

	/**
	 *
	 * Post a news article to the news forum
	 */
	public function actionPostNews()
	{
		$options = XenForo_Application::get('options');
		$forum_id = $options->news_forum_id;
		$user_ids = explode(',', $options->news_users);
		$poster = $options->news_poster_options;
		$news_group_id = $options->news_group_id;

		$piratesNewsFeedModel = $this->_getPirateNewsFeedModel();
		$blogs = $piratesNewsFeedModel->registry(); // what if nothing is returned? is that possible? double check above code, which checks first for a registry

		$news_id = $this->_input->filterSingle('news_id', XenForo_Input::INT);
		// what if this isn't specified? we need to error out
		$news = $blogs[$news_id];
		// if this doesn't exist, error out

		$message = $piratesNewsFeedModel->fetch($news['url']);
		// so THIS is where fetch() is used..
		
		if (!preg_match("/\<div class\=\"news_body\"\>(.+)\t+\s+\<br\>\<br\>/sm", $message, $out))
		{
			preg_match("/\<div class\=\"news_body\"\>(.+)\n\s+\<div class\=\"next\-previous\"\>/sm", $message, $out);
		}

		if(empty($out))
		{
			return $this->responseError(new XenForo_Phrase('pirateNewsFeed_something_went_wrong_oh_no!')); // make phrase
		}
		
		$options    = array('stripLinkPathTraversal' => XenForo_Visitor::isBrowsingWith('firefox')); // why is this?
		$newMessage = trim(XenForo_Html_Renderer_BbCode::renderFromHtml(str_replace(array("\<br\>","<br />"), array("\n\n","\n\n"), $out[1]), $options));

		$user = $piratesNewsFeedModel->getNewsPoster(); // look into this

		PiratesForums_Helper_Thread::create($forum_id, $user, str_replace("\\'", "'", $news['title']).' '.$news['date'], $newMessage);

		$piratesNewsFeedModel->markPosted($news['stamp']);

		$piratesNewsFeedModel->injectCache($news_id, 'message', $newMessage); // what?

		$viewParams = array();
		return $this->responseView(
			'PiratesNewsFeed_ViewPublic_Forum_PostNews',
			'PiratesNewsFeed_news_posted',
			$viewParams
		);

	}
	
	function actionMarkNotPosted()
	{
		$newsId = $this->_input->filterSingle('news_id', XenForo_Input::INT);

		if(!$newsId)
		{
			return $this->responseError(new XenForo_Phrase('pirateNewsFeed_no_news_id_specified'));
		}
		
		$this->_getPirateNewsFeedModel()->markNotPosted($newsId);
		
		$options = XenForo_Application::get('options');
		
		$forum = $this->_getForumModel()->getForumById($options->piratesNewsFeed_news_forum_id);
		
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::RESOURCE_CREATED, // change response
			XenForo_Link::buildPublicLink('forums/display-news', $forum),
			new XenForo_Phrase(
				   'pirateProfile_the_pirate_has_been_saved_successfully' // change phrase
			)
		);
	}

	/**
	 *
	 * Marks an specific news article as "posted"
	 */
	function actionMarkPosted()
	{
		$newsId = $this->_input->filterSingle('news_id', XenForo_Input::INT);

		if(!$newsId)
		{
			return $this->responseError(new XenForo_Phrase('pirateNewsFeed_no_news_id_specified'));
		}
		
		$this->_getPirateNewsFeedModel()->markPosted($newsId);

		$options = XenForo_Application::get('options');
		
		$forum = $this->_getForumModel()->getForumById($options->piratesNewsFeed_news_forum_id);
		
		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::RESOURCE_CREATED, // change response 
			XenForo_Link::buildPublicLink('forums/display-news', $forum),
			new XenForo_Phrase(
				   'pirateProfile_the_pirate_has_been_saved_successfully' // change phrase
			)
		);
	}
	
	protected function _getPirateNewsFeedModel()
	{
		return $this->getModelFromCache('PiratesNewsFeed_Model_PiratesNewsFeed');
	}
	
	protected function _getForumModel()
	{
		return $this->getModelFromCache('XenForo_Model_Forum');
	}
}
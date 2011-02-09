<?php

class PiratesNewsFeed_ControllerPublic_Forum extends XFCP_PiratesNewsFeed_ControllerPublic_Forum
{
	/**
	 *
	 * Mark an specific news article as "not posted"
	 */
	function ActionMarkNotposted()
	{
		$news_id = $this->_input->filterSingle('news_id', XenForo_Input::INT);

		$model = $this->getModelFromCache('PiratesNewsFeed_Model_PiratesNewsFeed');

		if(!$news_id) {
			return $this->_genericError();
		}
		$model = $this->getModelFromCache('PiratesNewsFeed_Model_PiratesNewsFeed');
		$model->markNotPosted($news_id);

		return $this->_genericView();
	}

	/**
	 *
	 * Marks an specific news article as "posted"
	 */
	function ActionMarkPosted()
	{
		$news_id = $this->_input->filterSingle('news_id', XenForo_Input::INT);

		if(!$news_id) {
			return $this->_genericError();
		}
		$model = $this->getModelFromCache('PiratesNewsFeed_Model_PiratesNewsFeed');
		$model->markPosted($news_id);

		return $this->_genericView();
	}

	/**
	 *
	 * Generic message showing an action was done to avoid too much redundancy.
	 */
	function _genericView()
	{

		$viewParams = array();
		return $this->responseView(
			'PiratesNewsFeed_ViewPublic_Forum_Yo', // This is a fictional class, don't worry about why I guess lol
			'PiratesNewsFeed_news_success_generic',
			$viewParams
		);
	}

	/**
	 *
	 * Generic Error
	 */
	function _genericError()
	{
		$viewParams = array();
		return $this->responseView(
			'PiratesNewsFeed_ViewPublic_Forum_Yo', // This is a fictional class, don't worry about why I guess lol
			'PiratesNewsFeed_generic_error',
			$viewParams
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
		$forum_id = $options->news_forum_id;

		$model  = $this->getModelFromCache('PiratesNewsFeed_Model_PiratesNewsFeed');

		$blogs = $model->registry();
		if(!$blogs) {
			$blogs = $model->feed($forum_id, $itemsCount);
		}

		$model = XenForo_Model::create('XenForo_Model_Forum');
		$forum = $forum = $model->getForumById($forum_id);

		$viewParams = array(
			'blog' => $blogs,
			'canManageNews' => $visitor['is_admin'],
			'refreshLink' => XenForo_Link::buildPublicLink("forums/refreshNews",$forum)
		);

		return $this->responseView(
			'PiratesNewsFeed_ViewPublic_Forum_Yo', // This is a fictional class, don't worry about why I guess lol
			'PiratesNewsFeed_news_template',
			$viewParams
		);
	}

	/**
	 *
	 * Removes news feed from cache so it can be fetched/refreshed again
	 */
	function ActionRefreshnews()
	{
		$model  = $this->getModelFromCache('PiratesNewsFeed_Model_PiratesNewsFeed');
		$model->deleteRegistry('PiratesNewsFeedCache');

		$this->actionDisplayNews();

		$viewParams = array();
		return $this->_genericView();
	}

	/**
	 *
	 * Post a news article to the news forum
	 */
	public function ActionPostNews()
	{
		$options = XenForo_Application::get('options');
		$forum_id = $options->news_forum_id;
		$user_ids = explode(",",$options->news_users);
		$poster = $options->news_poster_options;
		$news_group_id = $options->news_group_id;

		$model  = $this->getModelFromCache('PiratesNewsFeed_Model_PiratesNewsFeed');
		$blogs = $model->registry();

		$news_id = $this->_input->filterSingle('news_id', XenForo_Input::INT);

		$news = $blogs[$news_id];


		$message = $model->fetch($news['url']);
		//pirates provides service notications in a slightly different format, so if check for news fails then will check with a second regular expression
		if(!preg_match("/\<div class\=\"news_body\"\>(.+)\t+\s+\<br\>\<br\>/sm",$message,$out)) {
			preg_match("/\<div class\=\"news_body\"\>(.+)\n\s+\<div class\=\"next\-previous\"\>/sm",$message,$out);
		}
		$viewParams = array();

		if(!$out) {
			//$this->error(new XenForo_Phrase('error_msg'), 'group_id');
			return $this->responseView(
				'PiratesNewsFeed_ViewPublic_Forum_Yo', // This is a fictional class, don't worry about why I guess lol
				'PiratesNewsFeed_news_error',
				$viewParams
			);
		}
		$options = array('stripLinkPathTraversal' => XenForo_Visitor::isBrowsingWith('firefox'));
		$new_message = trim(XenForo_Html_Renderer_BbCode::renderFromHtml(str_replace(array("\<br\>","<br />"),array("\n\n","\n\n"),$out[1]), $options));

		$user = $model->getNewsPoster();

		$model->mkThread($forum_id, $user,str_replace("\\'","'",$news['title']).' '.$news['date'],$new_message);

		$model->markPosted($news['stamp']);

		$model->injectCache($news_id,'message',$new_message);

		return $this->responseView(
			'PiratesNewsFeed_ViewPublic_Forum_Yo', // This is a fictional class, don't worry about why I guess lol
			'PiratesNewsFeed_news_posted',
			$viewParams
		);

	}



	/**
	 * this function is not used. Was added just for testing..
	 */
	public function ActionTestCron()
	{
		$model  = $this->getModelFromCache('PiratesNewsFeed_Model_PiratesNewsFeed');
		$blogs = $model->runCron();

		die("response..".print_r($blogs,1));

	}


}
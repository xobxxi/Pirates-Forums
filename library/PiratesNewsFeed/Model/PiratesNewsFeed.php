<?php

class PiratesNewsFeed_Model_PiratesNewsFeed  extends XenForo_Model {

	private static $blogs;
	public $form_id;
	public $itemsCount;

	const POSTER_RAMDOM = 1;
	const POSTER_POSTER_ID = 2;
	const POSTER_RAMDOM_AND_POSTER_ID = 3;
	const POSTER_CURRENT_POSTER = 4;
	const POSTER_DEFAULT =  4;


	/**
	 *
	 * Run cron jobs
	 *
	 */
	function runCron()
	{
		$xoptions = XenForo_Application::get('options');
		//things are off..
		if(!$xoptions->news_notification_forum && !$xoptions->news_auto_news) {
			return;
		}
		$itemsCount = $xoptions->news_count;
		$forum_id = $xoptions->news_forum_id;
		$user_ids = explode(",",$xoptions->news_users);
		//force to refresh the data.
		$model = $this->getModelFromCache('PiratesNewsFeed_Model_PiratesNewsFeed'); //new PiratesNewsFeed_Model_PiratesNewsFeed;

		$PiratesNewsFeedCache = $model->create('XenForo_Model_DataRegistry')->get('PiratesNewsFeedCache');

		$feed = $this->feed($forum_id,$itemsCount);
		if(!$feed) {
			return;
		}

		$latest = $feed[key($feed)];
		$user_model = $this->getModelFromCache('Xenforo_Model_User');

		$msg_options = array('stripLinkPathTraversal' => XenForo_Visitor::isBrowsingWith('firefox'));

		$record = $record = XenForo_Model::create('XenForo_Model_DataRegistry')->get('PiratesNewsFeedRecord');
		$reportNews = array();
		foreach($feed as $k => $v) {
			if($record && isset($record[$v['stamp']])) {
				continue;
			}

			$reportNews[$k] = $v['title'];
			if(!$xoptions->news_auto_news) {
				continue;
			}

			$user = $model->getNewsPoster();

			$message = self::fetch($v['url']);

			if(!preg_match("/\<div class\=\"news_body\"\>(.+)\t+\s+\<br\>\<br\>/sm",$message,$out)) {
				preg_match("/\<div class\=\"news_body\"\>(.+)\n\s+\<div class\=\"next\-previous\"\>/sm",$message,$out);
			}

			if(!$out) {
				continue;
			}

			$prepare_message = str_replace(
				array(
					"\<br\>",
					"<br />",
					"<br/>"),
				array(
					"\n\n",
					"\n\n",
					"\n\n"
				),$out[1]
			);

			$new_message = trim(XenForo_Html_Renderer_BbCode::renderFromHtml($prepare_message, $msg_options));

			self::mkThread($forum_id, $user,str_replace("\\'","'",$v['title']).' '.$v['date'],$new_message);

			$model->markPosted($v['stamp']);
		}

		if($xoptions->news_notification_forum && $reportNews) {
			$news_count = count($reportNews);
			if($news_count > 1) {
				$is = "are";
			} else {
				$is = "is";
			}

			$new_message = "
				There $is $news_count news that have not been posted on the site. Will you post 'em?.<br />
				If so be sure to claim this thread by posting/responding. Thank You.<br />
				<br /><br />
				<b>News</b>:\n<br />
			".implode("<br /><br />",$reportNews);

			$user = $model->getNewsPoster();
			$new_message = trim(XenForo_Html_Renderer_BbCode::renderFromHtml($new_message, $msg_options));

			$thread = self::mkThread($xoptions->news_notification_forum, $user,"News Waiting to be posted",str_replace("\\'","'",$new_message));

			if($xoptions->news_subscribe_posters) {
				$permission = $this->getModelFromCache('Xenforo_Model_UserGroup');
				$user_ids = $permission->getUserIdsInUserGroup($xoptions->news_group_id);

				$watch = $this->getModelFromCache('XenForo_Model_ThreadWatch');
				$notify_method = ($xoptions->news_notify_posters_email? 'watch_email':'watch_no_email');

				foreach ($user_ids as $k => $v) {
					$watch->setThreadWatchState($k, $thread['thread_id'], $notify_method);

					if($xoptions->news_alert) {
						$pv_user = $user_model->getUserById($k);
						XenForo_Model_Alert::alert(
							$k,
							$user['user_id'],
							$pv_user['username'],
							'post',
							$thread['thread_id'],
							'insert',
							array()
						);
					}

				}
			}
		}

		$model->deleteRegistry('PiratesNewsFeedCache');
		if(!$PiratesNewsFeedCache) {
			$feed['last_stamp'] = $latest['stamp'];
			$model->registry($feed);
		}
	}

		/**
	 *
	 * Determine what user will post the news article.
	 */
	function getNewsPoster()
	{
		$xoptions = XenForo_Application::get('options');
		$user_ids = explode(",",$xoptions->news_users);
		$poster = $xoptions->news_poster_options;
		$news_group_id = $xoptions->news_group_id;

		switch($poster) {
			default:

				$user  = XenForo_Visitor::getInstance();

			break;
			case self::POSTER_CURRENT_POSTER:

				$user  = XenForo_Visitor::getInstance();

				break;
			case self::POSTER_POSTER_ID:

				if(is_array($user_ids)) {
					$user_ids = array_flip($user_ids);
					$user_id = array_rand($user_ids, 1);
				} else {
					$user_id = $user_ids;
				}

				$user_model = $this->getModelFromCache('Xenforo_Model_User');
				$user = $user_model->getUserById($user_id);

				break;
			case self::POSTER_RAMDOM:

				if(!$news_group_id) {
					return $this->responseView(
						'PiratesNewsFeed_ViewPublic_Forum_Yo',
						'PiratesNewsFeed_news_error',
						$viewParams
					);
				}
				$permission = $this->getModelFromCache('Xenforo_Model_UserGroup');
				$user_ids = $permission->getUserIdsInUserGroup($news_group_id);

				$user_id = array_rand($user_ids, 1);

				$user_model = $this->getModelFromCache('Xenforo_Model_User');
				$user = $user_model->getUserById($user_id);


				break;
			case self::POSTER_RAMDOM_AND_POSTER_ID:

				if(is_array($user_ids)) {
					$field_user_ids = array_flip($user_ids);
				} else {
					//is an int
					if($user_ids) {
						$field_user_ids[$user_ids] = $user_ids;
					} else {
						$field_user_ids = array();
					}
				}

				$permission = $this->getModelFromCache('Xenforo_Model_UserGroup');
				$user_ids = $permission->getUserIdsInUserGroup($news_group_id);
				$user_ids += $field_user_ids;

				$user_id = array_rand($user_ids, 1);

				$user_model = $this->getModelFromCache('Xenforo_Model_User');
				$user = $user_model->getUserById($user_id);

				break;
		}

		return $user;
	}

	/**
	* get a News Page
	*
	* @param array $request
	* @param boolean $debug
	* @param boolean $clean_response
	* @return string
	*/
	public function fetch($url)
	{
		$link = curl_init();
		curl_setopt($link, CURLOPT_URL, $url);
		//curl_setopt($link, CURLOPT_POSTFIELDS, http_build_query($data));
		curl_setopt($link, CURLOPT_VERBOSE, 0);
		curl_setopt($link, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($link, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($link, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($link, CURLOPT_MAXREDIRS, 6);
		curl_setopt($link, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($link, CURLOPT_TIMEOUT, 15); // 60
		$results=curl_exec($link);

		curl_close($link);

		return $results;
	}

	function injectCache($id, $setting,$data)
	{
		$registry = $this->registry();
		$registry[$id][$setting] = $data;
		$this->registry($registry);
	}

	/**
	 * Start a new thread
	 * @param unknown_type $forumId
	 * @param unknown_type $user
	 * @param unknown_type $title
	 * @param unknown_type $message
	 */
	public static function mkThread($forumId, $user, $title, $message)
	{
		$writer = XenForo_DataWriter::create('XenForo_DataWriter_Discussion_Thread');
		$writer->set('user_id', $user['user_id']);
		$writer->set('username', $user['username']);
		$writer->set('title', $title);

		$postWriter = $writer->getFirstMessageDw();
		$postWriter->set('message', $message);

		$writer->set('node_id', $forumId);
		$writer->preSave();
		$writer->save();

		return $writer->getMergedData();
	}

	/**
	 *
	 * Mark article as posted.
	 * @param $new_id
	 */
	function markPosted($new_id)
	{
		$record = XenForo_Model::create('XenForo_Model_DataRegistry')->get('PiratesNewsFeedRecord');

		if(is_array($record) && isset($record[$new_id])) {
			return;
		}
		$visitor = XenForo_Visitor::getInstance();
		$record[$new_id]  = $visitor->username;

		$this->_getDataRegistryModel()->set('PiratesNewsFeedRecord', $record);

		//update Cache...
		$registry = $this->registry();
		$news = $registry[$new_id];
		if(isset($news['poster'])) {
			$news['poster'] = $visitor->username;
		}
		$news['posted'] = $visitor->username;
		$registry[$news['stamp']] = $news;
		$this->registry($registry);
	}

	/**
	 *
	 * Mark article as not posted.
	 * @param $new_id
	 */
	function markNotPosted($new_id)
	{
		$record = XenForo_Model::create('XenForo_Model_DataRegistry')->get('PiratesNewsFeedRecord');
		if(is_array($record) && isset($record[$new_id])) {

			unset($record[$new_id]);

			$this->_getDataRegistryModel()->set('PiratesNewsFeedRecord', $record);
		}

		//update Cache...
		//update Cache...
		$registry = $this->registry();
		$news = $registry[$new_id];
		$news['posted'] = false;
		$registry[$news['stamp']] = $news;
		$this->registry($registry);
	}


	function getPosted()
	{
		return XenForo_Model::create('XenForo_Model_DataRegistry')->get('PiratesNewsFeedRecord');
	}

	public function registry($cache = array())
	{
		if($cache===array()) {
			return XenForo_Model::create('XenForo_Model_DataRegistry')->get('PiratesNewsFeedCache');
		}
		$this->_getDataRegistryModel()->set('PiratesNewsFeedCache', $cache);
	}

	function  deleteRegistry()
	{
		$this->_getDataRegistryModel()->delete('PiratesNewsFeedCache');
	}

	/**
	 * Gets remove articles from piratesonline.com and saves them in cache.
	 *
	 * @param $forum_id
	 * @param $itemsCount
	 */
	function feed($forum_id, $itemsCount)
	{
		if(self::$blogs) {
			return self::$blogs;
		}
		$visitor = XenForo_Visitor::getInstance();

		$model = XenForo_Model::create('XenForo_Model_Forum');//new XenForo_Model_Forum;
		$forum = $forum = $model->getForumById($forum_id);

		$feed = "http://blog.piratesonline.go.com/blog/pirates/feed2/entries/atom?numEntries=$itemsCount";
		$data = simplexml_load_file($feed);

		$posted = $this->getPosted();

		foreach($data->entry as $k =>$v) {
			$attr = $v->link->attributes();
			$blog['title'] = str_replace("'","\'",(string)  $v->title);
			$blog['url'] = (string) $attr->href;
			$blog['summary'] = (string) $v->summary;
			$blog['published'] = (string) $v->published;
			$blog['updated'] = (string) $v->updated;
			$blog['stamp'] = strtotime((string) $v->published);
			$blog['date'] = date("M/d/Y", strtotime((string) $v->published));
			$blog['markPosted'] = XenForo_Link::buildPublicLink("forums/markPosted&news_id={$blog['stamp']}",$forum);
			$blog['markNotPosted'] = XenForo_Link::buildPublicLink("forums/markNotPosted&news_id={$blog['stamp']}",$forum);

//			$blog['postLink'] = XenForo_Link::buildPublicLink("forums/PostNews?url=".urlencode($blog['url'])."&title={$blog['title']}",$forum);

			$blog['postLink'] = XenForo_Link::buildPublicLink("forums/PostNews/&news_id={$blog['stamp']}",$forum);
			if(isset($posted[$blog['stamp']])) {
				//news already posted...
				$blog['posted']  = $posted[$blog['stamp']];
			} else {
				$blog['posted']  = false;
			}
			$blogs[$blog['stamp']] = $blog;
		}
		$blogs['last_stamp'] = key($blogs);

		$this->_getDataRegistryModel()->set('PiratesNewsFeedCache', $blogs);

		return self::$blogs = $blogs;
	}

	/**
	 * @return PiratesNewsFeed_Model_PiratesNewsFeed
	 */
	protected function _getUserModel()
	{
		return $this->getModelFromCache('PiratesNewsFeed_Model_PiratesNewsFeed');
	}
}
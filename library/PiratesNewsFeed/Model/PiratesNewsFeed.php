<?php

class PiratesNewsFeed_Model_PiratesNewsFeed  extends XenForo_Model {

	//this holds feeds()
	private static $blogs;

	/**
	 *
	 * These constants are used in function getPosterID();
	 *
	 * @var unknown_type
	 */
	const POSTER_RAMDOM = 1;
	const POSTER_POSTER_ID = 2;
	const POSTER_RAMDOM_AND_POSTER_ID = 3;
	const POSTER_CURRENT_POSTER = 4;
	const POSTER_DEFAULT =  4;

	/**
	 *
	 * Run cron jobs
	 *
	 * This is the only cron job in this addon, and what it does is - -
	 *
	 * Well, first I'll go about how the data is stored,
	 *
	 * There are in total 2 entries in the registry.
	 *
	 * The data uses the registry, in an entry call "PiratesNewsFeedCache"
	 *
	 * (it does not include the article itself,  only information about it)
	 *
	 * this entry has information about feeds that have been fetched in the past.
	 * when fetching the atom/rss  from pirates site,
	 * These are the items returned by the news feed ( and saved to the registry):
	 * href
	 * summary
	 * published
	 * updated
	 *
	 *
	 * href - is the url of article
	 *
	 * summary - a brief description of the article
	 *
	 * published - is the date and time an article was published
	 *
	 * update - if the article was updated, this is the date and time of that update
	 *
	 * ----------------------------
 	 *
	 *  "PiratesNewsFeedRecord"
	 *
	 * this entry has an array, with all the previously posted articles.
	 * this is only a stack of keys data time stamp of an specific
	 * article. So only the key is stored, and that is how we know if
	 * an specic article has been published or not, by saving the time stamp of the date.
	 * If it is not there, it means it hasn't been published on the site. If it is there it has.
	 *
	 * to keep track of which article is which, we convert the "published" date, into a time stamp,
	 * this provies a unique numeric value for each article that becomes the key of the article in the stack,
	 * and helps to know if an article has been already posted or not.
	 * then this becomes the key identifier of each individual article.
	 *
	 * -----------------------------
	 *
	 * $feed = $this->feed($forum_id,$itemsCount);
	 *
	 * this fetches all the articles from piratesonline.com
	 *
	 * $forum_id - this is only used  to generated links related links to mark posted, or redirect to that forum etc.
	 *
	 * $itemsCount - the number of news articles to fetch
	 *
	 * -----------------------------
	 *
	 * Two registry entries:
	 *
	 * - PiratesNewsFeedCache
	 * - PiratesNewsFeedRecord
	 *
	 */
	function runCron()
	{
		$xoptions = XenForo_Application::get('options');
		//if settings on the admin are turned off, or not set
		if(!$xoptions->news_notification_forum && !$xoptions->news_auto_news) {
			return;
		}

		$itemsCount = $xoptions->news_count;
		$forum_id = $xoptions->news_forum_id;
		$user_ids = explode(",",$xoptions->news_users);

		//get an instance of the Xenforo registry
		$registry = XenForo_Model::create('XenForo_Model_DataRegistry');

		//get articles latest feeds already in the system
		$cache = $registry->get('PiratesNewsFeedCache');

		//get the articles already posted
		$record = $registry->get('PiratesNewsFeedRecord');

		/**
		 *
		 * Get a stack of articles(fetch news feed) from piratesonline.com
		 * this function also formats the data, and makes it in a way where it
		 * can be used anywere in the addon, without furder modication of the stack.
		 *
		 * $forum_id - is used to get the forum properties, and generated links related to that forum.
		 * Then links can be used to post a news articles, or to mark as posted, or not posted, this record is kept in the registry.
		 *
		 * this function also, automarically updates the registry, with the latest information fetched.
		 *
		 * but not before, we have already a copy of this registry data in $cache, which helps us
		 * to compare if an individual article exists or not.
		 *
		 * @var $feed array
		 */
		$feed = $this->feed($forum_id,$itemsCount);
		if(!$feed) {
			//this means it failed to get/fetch any articles from piratesonline.com
			return;
		}

		//get the first key from the array, aka get the latest article
		$latest = $feed[key($feed)];

		/**
		 *
		 * $msg_options - this is used by function self::mkThread()  called in the loop below.
		 *
		 * It is defined up here for efficiency, instead of doing the same task over and over in the loop
		 *
		 * it converts html into bbcode that can be used in the articles
		 *
		 * @var unknown_type
		 */
		$msg_options = array('stripLinkPathTraversal' => XenForo_Visitor::isBrowsingWith('firefox'));

		//this gets the fresh cache from the registry ( after feed() method is called, this data gets refreshed )
		//$record = $registry->get('PiratesNewsFeedRecord');

		//holds the titles of articles that needs to be posted, in news posters notification.
		$reportNews = array();

		/**
		 * this can be used to make changes to  articles, of specific tags, or string, before is converted into bbcode.
		 * see reference below, after the preg_match statement
		 * *EXAMPLE*
		 * $search['search this'] = "replace with this";
		 */
		$search['<br>'] = "<br /><br />";
		$search['<br />'] = "<br /><br />";



		/**
		 * Then we loop through $feed (the fresh stack of articles fetched from piratesonline.com)
		 */
		foreach($feed as $k => $v) {

			//we check if the article already exist  if the key is in $cache then it exists.
			if($record && isset($record[$v['stamp']])) {
				//if isset/ or exists ,  then we skip this article, and move on to the next article
				continue;
			}

			//$reportNews - is stack used in self::mkTread()
			//this is for notification purposes, to notify news posters that there are new articles waiting to be posted
			$reportNews[$k] = $v['title'];

			if(!$xoptions->news_auto_news) {
				//this turns off the ability to post news articles automatically
				continue;
			}

			//fetch an article from piratesonline.com,  the newsfeed only provide information, not necesarily the article itself..
			//it does provide the url where the article is though, so we fetch the article.
			$message = self::fetch($v['url'],false);

			if(!$message) {
				//this means  we failed to fetch the article.
				continue;
			}

			//from the page that was fetched, we need to extract the article, and remove all the other html from the page.
			if(!preg_match("/\<div class\=\"news_body\"\>(.+)\t+\s+\<br\>\<br\>/sm",$message,$out)) {
				//if the above fails, we try to see if this is a "service" notification message
				//the "service" notification messages, have a slightly different format than a regular articles, so we adjust the regular expression to reflec that.
				preg_match("/\<div class\=\"news_body\"\>(.+)\n\s+\<div class\=\"next\-previous\"\>/sm",$message,$out);
			}

			if(!$out) {
				//this means the format how pirates show blogs could have changed etc..
				//in this case, the regular expressions above will need to be updated.
				continue;
			}


			/**
			* this can be used to make changes to  articles, of specific tags, or string, before is converted into bbcode.
			* Uncomment if it becomes necessary..
			*
			* this uses the $search  array defined above
			*
			* $out[1] = str_replace(array_keys($search),$search,$out[1]);
			*/

			$prepare_message = $out[1] ;

			/**
			 *
			 * Convert html into bbcode
			 */
			$new_message = trim(XenForo_Html_Renderer_BbCode::renderFromHtml($prepare_message, $msg_options));


			/**
			 *
			 * Based in the admin options, we get the id of user that the article will be posted under.
			 */
			$user = $this->getNewsPoster();

			//prepare the title
			$title = str_replace("\\'","'",$v['title']).' '.$v['date'];

			//create thread
			self::mkThread($forum_id, $user,$title,$new_message);

			//mark this article as posted, in the cache.
			$this->markPosted($v['stamp']);
		}

		//the curl connection is kept open to save resources, so after all the work is done, we close it.
		// this is what is used in self::fetch() function.
		if(self::$fetch_link) {
			curl_close(self::$fetch_link);
		}

		/**
		 * This posts on the news reporter forum (if any) announcing of new articles avilable to be posted.
		 */
		$this->_notifyParties($reportNews);

		if(!$cache) {
			//update the registry last article time stamp
			$feed['last_stamp'] = $latest['stamp'];
			$this->registry($feed);
		} else {
			//delete the whole cache.
			//forces it to refresh, when used again.
			$this->_getDataRegistryModel()->delete('PiratesNewsFeedCache');
		}
	}

	/**
	 *
	 * This function is exclusively called from cron.
	 *
	 * This function posts notifications under a determine "news reporter" forum.
	 * Starts new thread informating news reporters that there are new news pending to be posted
	 *
	 *
	 * @param unknown_type $reportNews
	 */
	function _notifyParties($reportNews)
	{
		$xoptions = XenForo_Application::get('options');
		if($xoptions->news_notification_forum && $reportNews) {
			return;
		}

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

		//who will post the message
		$user = $model->getNewsPoster();

		//turns the text into bbcode
		$new_message = trim(XenForo_Html_Renderer_BbCode::renderFromHtml($new_message, $msg_options));

		//create the thread
		$thread = self::mkThread($xoptions->news_notification_forum, $user,"News Waiting to be posted",str_replace("\\'","'",$new_message));

		if($xoptions->news_subscribe_posters) {

			//
			$permission = $this->getModelFromCache('Xenforo_Model_UserGroup');

			//get user_ids from "news reporter" group
			$user_ids = $permission->getUserIdsInUserGroup($xoptions->news_group_id);

			//this will get everyone in the group to an alert when the message is posted.
			$watch = $this->getModelFromCache('XenForo_Model_ThreadWatch');

			$notify_method = ($xoptions->news_notify_posters_email? 'watch_email':'watch_no_email');


			//get user model.
			$user_model = $this->getModelFromCache('Xenforo_Model_User');
			foreach ($user_ids as $k => $v) {
				//adds "watch" to users in group news reporter
				$watch->setThreadWatchState($k, $thread['thread_id'], $notify_method);


				if($xoptions->news_alert) {
					/**
					 * I am not really sure if the above action will trigger an alert.
					 *
					 * When I tested it, it did not trigger an alert, so the statement below was added to address that, which
					 * triggers an alert to the users in group "news reporter".
					 */

					//  this creates the news alert.
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

	/**
	 *
	 * Determine what user will post the news article.
	 *
	 * This function uses the admin options to determine what user will be posting an article.
	 *
	 * Who posts the article will depend on the admin options, it can be set from one specific user to , a group of users
	 * or to a group of users plus other specific users.
	 *
	 */
	function getNewsPoster()
	{
		$xoptions = XenForo_Application::get('options');
		$user_ids = explode(",",$xoptions->news_users);
		$poster = $xoptions->news_poster_options;
		$news_group_id = $xoptions->news_group_id;

		/**
		 * This swtich uses admin option to determine what user will post the news article.
		 */
		switch($poster) {
			default:
			case self::POSTER_DEFAULT:
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
					return false;
				}
				$permission = $this->getModelFromCache('Xenforo_Model_UserGroup');

				//get the user_ids from group
				$user_ids = $permission->getUserIdsInUserGroup($news_group_id);

				//pick a random id from the group
				$user_id = array_rand($user_ids, 1);


				$user_model = $this->getModelFromCache('Xenforo_Model_User');

				//get user from picked id
				$user = $user_model->getUserById($user_id);


				break;
			case self::POSTER_RAMDOM_AND_POSTER_ID:

				/**
				 * this is basically the same as the above option, but it also adds spefici ids specified
				 * in the "poster id" field in the admin. So it will add any specified id(s) to the pool of ids.
				 * before a random one is picked once all together.
				 *
				 */
				if(is_array($user_ids)) {
					//more than one id specific in "poster id"
					$field_user_ids = array_flip($user_ids);
				} else {
					//is an int
					if($user_ids) {
						//just one id specified
						$field_user_ids[$user_ids] = $user_ids;
					} else {
						// field was empty.
						$field_user_ids = array();
					}
				}

				$permission = $this->getModelFromCache('Xenforo_Model_UserGroup');
				//gets the ids from group
				$user_ids = $permission->getUserIdsInUserGroup($news_group_id);

				//merges the ids from poster and group
				$user_ids += $field_user_ids;

				//picks a random id from the pool of ids
				$user_id = array_rand($user_ids, 1);

				//get user
				$user_model = $this->getModelFromCache('Xenforo_Model_User');
				$user = $user_model->getUserById($user_id);

				break;
		}

		return $user;
	}

	private static $fetch_link;
	/**
	* get a News Page
	*
	* @param array $request
	* @param boolean $debug
	* @param boolean $clean_response
	* @return string
	*/
	public function fetch($url,$close_link = true)
	{
		self::$fetch_link = curl_init();
		curl_setopt(self::$fetch_link, CURLOPT_URL, $url);
		//curl_setopt($link, CURLOPT_POSTFIELDS, http_build_query($data));
		curl_setopt(self::$fetch_link, CURLOPT_VERBOSE, 0);
		curl_setopt(self::$fetch_link, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt(self::$fetch_link, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt(self::$fetch_link, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt(self::$fetch_link, CURLOPT_MAXREDIRS, 6);
		curl_setopt(self::$fetch_link, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt(self::$fetch_link, CURLOPT_TIMEOUT, 15); // 60
		$results=curl_exec(self::$fetch_link);

		if($close_link) {
			curl_close(self::$fetch_link);
		}
		return $results;
	}

	/**
	 * This basically would add specific setting to an article already saved in cached.
	 *
	 * This is not used.  But it is a good reference here.
	 *
	 * can safely be removed.
	 *
	 * @param $id
	 * @param $setting
	 * @param $data
	 */
	function injectCache($id, $setting,$data)
	{
		$registry = $this->_modelRegistry();
		$registry[$id][$setting] = $data;
		$this->_modelRegistry($registry);
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
		//get the record of posted articles.
		$record = XenForo_Model::create('XenForo_Model_DataRegistry')->get('PiratesNewsFeedRecord');

		//is already posted
		if(is_array($record) && isset($record[$new_id])) {
			//this means the article is already marked as posted, so no need to go any further.
			return;
		}
		$visitor = XenForo_Visitor::getInstance();
		//just for reference, post who posted the article.

		//adds the new id to the key stack. and in addition adds the username as value.
		$record[$new_id]  = $visitor->username;

		//saves the record.
		$this->_getDataRegistryModel()->set('PiratesNewsFeedRecord', $record);

		//here in indicates the the article was posted  outside of the "newsfeed" system,
		//so admins can mark it as posted, and take credit for posting it
		//since possible the person who posted it  is not in the news reporter group or doesn't know how to use it.
		//update who posted it.
		$registry = $this->_modelRegistry();
		$news = $registry[$new_id];
		if(isset($news['poster'])) {
			$news['poster'] = $visitor->username;
		}
		$news['posted'] = $visitor->username;
		$registry[$news['stamp']] = $news;
		$this->_modelRegistry($registry);
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
			//updates the record
			$this->_getDataRegistryModel()->set('PiratesNewsFeedRecord', $record);
		}

		//removes any reference of who posted the article, since it is marked as not posted..
		$registry = $this->_modelRegistry();
		$news = $registry[$new_id];
		$news['posted'] = false;
		$registry[$news['stamp']] = $news;
		$this->_modelRegistry($registry);
	}



	/**
	 *
	 * Gets the PiratesNewsFeed Cached data
	 * @param unknown_type $cache
	 */
	public function _modelRegistry($cache = array())
	{
		if($cache===array()) {
			return XenForo_Model::create('XenForo_Model_DataRegistry')->get('PiratesNewsFeedCache');
		}
		$this->_getDataRegistryModel()->set('PiratesNewsFeedCache', $cache);
	}

	/**
	 *
	 * Delete cache
	 */
	function  deleteRegistry()
	{
		$this->_getDataRegistryModel()->delete('PiratesNewsFeedCache');
	}


	/**
	 *
	 * Get news records of posted articles
	 * @return array - return an array with key identifier of each article posted (time stamp)
	 */
	function _getPosted()
	{
		return XenForo_Model::create('XenForo_Model_DataRegistry')->get('PiratesNewsFeedRecord');
	}

	/**
	 * Gets news articles from piratesonline.com and saves them in cache.
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
		//pharse the feeds from piratesonline.com
		$data = simplexml_load_file($feed);

		//get already posted articles
		$posted = $this->_getPosted();

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

			$blog['postLink'] = XenForo_Link::buildPublicLink("forums/PostNews/&news_id={$blog['stamp']}",$forum);
			/**
			 * checks if the article is already posted
			 */
			if(isset($posted[$blog['stamp']])) {
				//news already posted...
				$blog['posted']  = $posted[$blog['stamp']];
			} else {
				$blog['posted']  = false;
			}

			//stacks the article into the blogs stack
			$blogs[$blog['stamp']] = $blog;
		}
		//gets the last time stamp and save it it for reference.
		$blogs['last_stamp'] = key($blogs);

		$this->_getDataRegistryModel()->set('PiratesNewsFeedCache', $blogs);

		return self::$blogs = $blogs;
	}
}
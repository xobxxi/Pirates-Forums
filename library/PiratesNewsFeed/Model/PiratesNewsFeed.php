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
	 * Determine what user will post the news article.
	 */
	function getNewsPoster()
	{
		$options = XenForo_Application::get('options');
		$user_ids = explode(",",$options->news_users);
		$poster = $options->news_poster_options;
		$news_group_id = $options->news_group_id;

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
						'PiratesNewsFeed_ViewPublic_Forum_Yo', // This is a fictional class, don't worry about why I guess lol
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
	 *
	 * Run Cron jobs.. still need more work probably for 1.2.
	 *
	 */
	function runCron()
	{
		$options = XenForo_Application::get('options');
		$itemsCount = $options->news_count;
		$forum_id = $options->news_forum_id;
		$user_ids = explode(",",$options->news_users);
		//force to refresh the data.
		$model = $this->getModelFromCache('PiratesNewsFeed_Model_PiratesNewsFeed'); //new PiratesNewsFeed_Model_PiratesNewsFeed;

		$PiratesNewsFeedCache = $model->create('XenForo_Model_DataRegistry')->get('PiratesNewsFeedCache');

		$feed = $this->feed($forum_id,$itemsCount);
		if(!$feed) {
			return;
		}

		$latest = $feed[1];

		if($PiratesNewsFeedCache['last_stamp'] && $latest && $latest['stamp'] > $PiratesNewsFeedCache['last_stamp']) {

			$options = array('stripLinkPathTraversal' => XenForo_Visitor::isBrowsingWith('firefox'));

			$user_model = $this->getModelFromCache('Xenforo_Model_User');
			foreach($feed as $k => $v) {

				if($PiratesNewsFeedCache['last_stamp'] > $v['stamp']) {
					 break;
				}
				$new_message = trim(XenForo_Html_Renderer_BbCode::renderFromHtml(str_replace("\<br\>","\n\n",$v['message']), $options));

				$user = $model->getNewsPoster();

				self::mkThread($forum_id, $user,$v['title'].' '.$v['date'],$new_message);

			}
		}

		$model->deleteRegistry('PiratesNewsFeedCache');
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
<?php
/**
 * This is a  tiny third party ajax framework.
 */

//This is a CJAX Controller.
class controller_alert extends CJAX {

	CONST TIMER_ENGINE_PHP = 0;
	CONST TIME_ENGINE_JAVASCRIPT = 1;

	private $data = array();

	protected static $fxStarted;

	function initFX()
	{
		if(self::$fxStarted) {
			return;
		}
		//Initializing XenForo
		$startTime = microtime(true);
		$xenforoRoot = str_replace("\\","/", (dirname(dirname(dirname(dirname(__FILE__))))));
		$roorDir = $xenforoRoot;

		// Setup XenForo's autoloader
		require_once($xenforoRoot . '/library/XenForo/Autoloader.php');
		XenForo_Autoloader::getInstance()->setupAutoloader($xenforoRoot . '/library');

		XenForo_Application::initialize($xenforoRoot . '/library', $roorDir);
		XenForo_Application::set('page_start_time', $startTime);

		// Not required if you are not using any of the preloaded data
		//$dependencies = new XenForo_Dependencies_Public();
		//$dependencies->preLoadData();

		//Start Session
		XenForo_Session::startPublicSession();
		self::$fxStarted = true;

		//return $dependencies;
	}

	/**
	 *
	 * This is ran the first time.
	 *
	 * @param $seconds
	 * @param $unread_convo
	 */
	function dispatcher($options = array())
	{
		if(!(int) $options['time']) {
			return $this->warning("Invalid Time Property");
		}

		$this->initFX();

		$visitor = XenForo_Visitor::getInstance()->toArray();
		if($visitor['user_id']) {

			$ajax = CJAX::getInstance();

			switch($options['engine']) {
				case self::TIMER_ENGINE_PHP:

					//$ajax->wait($options['time'],true);
					sleep($options['time']);

					break;
				case self::TIME_ENGINE_JAVASCRIPT:

					$ajax->wait($options['time']);
					break;
			}
			$use_options = array('unread_msgs','engine','time','sound');
			//make sure we get what we expect or use set it as null to avoid errors.
			foreach($use_options as $v) {
				if(!isset($options[$v])) {
					$options[$v] = null;
				}
			}
			$this->data['a[unread_msgs]'] = $visitor['conversations_unread'];
			$this->data['a[engine]']  = $options['engine'];
			$this->data['a[time]'] = $options['time'];
			$this->data['a[sound]'] = $options['sound'];


			$ajax->text = false;
			$ajax->loading = true; //dont't change this one. Is special variable ajusted to this addon.
			$ajax->post = $this->data;
			$ajax->call("library/AutoUpdater/ajax.php?controller=alert&function=looper");
		}
	}

	/**
	 * Looper
	 *
	 * this function is ran every interval to check for new items.
	 *
	 * @param $seconds
	 * @param $unread_convo
	 */
	function looper($options = array(),$count = 0)
	{
		if(!isset($options['time']) || !(int) $options['time']) {
			return $this->warning("[AutoUpdater] Invalid Time Property");
		}
		$this->initFX();

		$visitor = XenForo_Visitor::getInstance()->toArray();

		if(!$visitor['user_id']) {
			//should never get here but if it ever does. Die in firebug console.
			exit("Exiting AutoUpdater... You must be logged in to use make use of it.");
		}

		$ajax = CJAX::getInstance();

		$this->convoUpdate( $visitor , $options);
		$this->alertsUpdate( $visitor , $options);

		switch($options['engine']) {
			case self::TIMER_ENGINE_PHP:

				sleep($options['time']);
				break;
			case self::TIME_ENGINE_JAVASCRIPT:

				$ajax->wait($options['time']);
				break;
		}
		$this->data['a[unread_msgs]'] = $visitor['conversations_unread'];//after timer
		$this->data['a[engine]']  = $options['engine'];
		$this->data['a[time]'] = $options['time'];
		$this->data['a[sound]'] = $options['sound'];
		$this->data['b'] = $count+1;
		//$ajax->text = "Updating";
		$ajax->text = false; //this prevents the "loading..." message to appear on the screen. It can be changed.
		$ajax->loading = true; //don't change this.
		$ajax->post = $this->data;
		$ajax->call("library/AutoUpdater/ajax.php?controller=alert&function=looper");
	}

	/**
	 *
	 * Update alert popup gadget
	 * @param $visitor
	 * @param $options
	 * @param $data
	 */
	function alertsUpdate($visitor,$options = array(),$data = array())
	{
		if($visitor['alerts_unread']) {

			if(!isset($options['alerts_unread']) || $visitor['alerts_unread'] > $options['alerts_unread']) {

				$ajax = CJAX::getInstance();

				//This makes it so that the next 2 commands are excempt from waiting and are executed rapidly.
				$ajax->setflag('no_wait',2);

				$sound = null;
				if(isset($options['sound']) && $options['sound']) {
					$sound = $this->sound();
				}

				//Updates the red bubble with the new alerts count
				$ajax->update("AlertsMenu_Counter", $visitor['alerts_unread'] ."<span class=\"arrow\">$sound</span>");

				//Displays the red arrow bubble.
				$ajax->style("AlertsMenu_Counter",array('display'=>'block'));
			}

		}
		$this->data['a[alerts_unread]'] = $visitor['alerts_unread'];


	}

	/**
	 *
	 * Update conversation gadget
	 *
	 * @param $visitor
	 * @param $options
	 * @param $data
	 */
	function convoUpdate($visitor = array(),$options = array(), $data = array())
	{
		if($visitor['conversations_unread']) {

			//only update if there are new messages
			if(!isset($options['unread_msgs']) || $visitor['conversations_unread'] > $options['unread_msgs']) {

				$ajax = CJAX::getInstance();

				//This makes it so that the next 2 commands are excempt from waiting and are executed rapidly.
				$ajax->setflag('no_wait',2);

				$sound = null;
				if(isset($options['sound']) && $options['sound']) {
					$sound = $this->sound();
				}
				//Updates the red bubble with the new messages count
				$ajax->update("ConversationsMenu_Counter", $visitor['conversations_unread'] ."<span class=\"arrow\">$sound</span>");

				//Displays the red arrow bubble.
				$ajax->style("ConversationsMenu_Counter",array('display'=>'block'));

				//update conversation menu to reflect the new messages yet todo.
				//$this->updateConversationsMenu();

				//if(isset($options['sound']) && $options['sound']) {

				//}
			}
			$this->data['a[unread_msgs]'] = $visitor['conversations_unread'];
		}

	}


	function sound()
	{
		$sound = "
			<object width='0' height='0'>
			 <param name='bgcolor' value='transparent'>
			<param name='movie' value='./Library/AutoUpdater/sound.swf'>
			<embed src='./Library/AutoUpdater/sound.swf' width='0' height='0'>
			</embed>
			</object>
			";

		return $sound;

	}
	/**
	 * TODO
	 *
	 * This is an attent to get a the menu popup template to update it accordingly if there are new messages..
	 * or find a way to reset it.
	 *
	 * If you know a Xenforo way, you can execute javascript with eval:
	 *
	 * $ajax = CJAX::getInstance();
	 * $ajax->eval();
	 */
	function updateConversationMenu()
	{

	}
}
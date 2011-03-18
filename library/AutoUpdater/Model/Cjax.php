<?php

class AutoUpdater_Model_Cjax {


	function fireCall()
	{
		$options = XenForo_Application::get('options');

		$visitor = XenForo_Visitor::getInstance();

		if($options->AutoUpdaterOnOff && $visitor->user_id) {
			require_once dirname(__file__)."/../ajax.php";

			if(!$seconds = $options->AutoUpdaterInterval) {
				//default if not set
				$seconds = 30;
			}

			$ajax = CJAX::getInstance();

			//To see stuff on firebug you can enable this..
			//$ajax->debug = true;

			$data['a[sound]']  = $options->AutoUpdaterSound;

			$data['a[engine]']  = $options->AutoUpdaterTimeEngine;
			$data['a[time]'] = $seconds;
			$data['a[unread_msgs]'] = $visitor['conversations_unread'];
			$ajax->post = $data;
			$ajax->text = 'Loading AutoUpdater..';
			$ajax->call("library/AutoUpdater/ajax.php?controller=alert&function=dispatcher");
		}
	}

}
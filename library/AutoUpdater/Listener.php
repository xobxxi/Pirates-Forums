<?php

class AutoUpdater_Listener
{
	public static function loadClassController($class, &$extend)
	{
		$options = XenForo_Application::get('options');

		if($options->AutoUpdaterOnOff) {
			switch($class) {
				case 'XenForo_ControllerPublic_Forum':
					if(!$options->AutoUpdaterThreads)  {
						return;
					}
					$extend[] = 'AutoUpdater_ControllerPublic_Forum';
					break;
				case 'XenForo_ControllerPublic_Index':
					if(!$options->AutoUpdaterIndex)  {
						return;
					}

					$extend[] = 'AutoUpdater_ControllerPublic_Index';
					break;
				case 'XenForo_ControllerPublic_FindNew':

					//$extend[] = 'AutoUpdater_ControllerPublic_FindNew';
					break;

			}
		}
	}

	public static  function load_cjax($name , $contents ,array $params,XenForo_Template_Abstract $template)
	{
		if($name=='page_container_head') {
		    if (!XenForo_Visitor::getInstance()->hasPermission('general', 'AutoUpdater')) {
				return $contents;
			}
			$options = XenForo_Application::get('options');

			if(!$options->AutoUpdaterOnOff) {
				return;
			}


			require_once dirname(__file__)."/ajax.php";

			$ajax = CJAX::getInstance();

			$options = XenForo_Application::get('options');

			$visitor = XenForo_Visitor::getInstance();

			if($visitor->user_id) {
				return $contents .= "\t".$ajax->init();
			}
		}

	}
}
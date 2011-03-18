<?php

class AutoUpdater_ControllerPublic_FindNew extends XFCP_AutoUpdater_ControllerPublic_FindNew {


	function actionThreads()
	{

		$response =  parent::actionThreads();


		return $response;
		//die("<pre>".print_r($response,1));
	}


}
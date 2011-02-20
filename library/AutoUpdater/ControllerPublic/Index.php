<?php

class AutoUpdater_ControllerPublic_Index extends XFCP_AutoUpdater_ControllerPublic_Index {


	function actionIndex()
	{
		$model = $this->getModelFromCache("AutoUpdater_Model_Cjax");

		$model->fireCall();

		return parent::actionIndex();
	}
}
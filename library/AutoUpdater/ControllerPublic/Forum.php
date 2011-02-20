<?php

class AutoUpdater_ControllerPublic_Forum extends XFCP_AutoUpdater_ControllerPublic_Forum {

	public function actionIndex()
	{
		$model = $this->getModelFromCache("AutoUpdater_Model_Cjax");

		$model->fireCall();

		return parent::actionIndex();
	}

}
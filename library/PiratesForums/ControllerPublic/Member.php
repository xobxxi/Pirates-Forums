<?php

class PiratesForums_ControllerPublic_Member extends XFCP_PiratesForums_ControllerPublic_Member
{
	public function actionNew()
	{
		$latestUsers = $this->_getUserModel()->getLatestUsers(
			array('is_banned' => 0), array('limit' => 20)
		);
		
		$viewParams = array(
			'users' => $latestUsers
		);
		
		return $this->responseView(
			'PiratesForums_ViewPublic_Member_New',
			'piratesForums_members_new',
			$viewParams
		);
	}
}
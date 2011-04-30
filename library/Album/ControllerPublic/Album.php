<?php

class Album_ControllerPublic_Album extends XenForo_ControllerPublic_Abstract
{
	public function actionIndex()
	{
		$userId = $this->_input->filterSingle('id', XenForo_Input::UINT);
		
		if (empty($userId))
		{
			$userId = XenForo_Visitor::getUserId();
		}
		
		$user = $this->_getUserModel()->getUserById($userId);
		
		if (empty($user))
		{
			throw $this->responseException($this->responseError(
				new XenForo_Phrase('requested_member_not_found'), 404)
			);
		}
		
		$this->canonicalizeRequestUrl(
			XenForo_Link::buildPublicLink('albums', $user)
		);
		
		$viewParams = array(
			'user' => $user
		);
		
		return $this->responseView(
			'Album_ViewPublic_Album_Index',
			'album_user',
			$viewParams
		);
	}
	
	public static function getSessionActivityDetailsForList(array $activities)
	{
		return new XenForo_Phrase('album_viewing_albums');
	}
	
	protected function _getUserModel()
	{
		return $this->getModelFromCache('XenForo_Model_User');
	}
}
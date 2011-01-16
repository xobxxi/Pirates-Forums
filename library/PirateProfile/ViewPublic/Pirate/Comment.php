<?php

class PirateProfile_ViewPublic_Pirate_Comment extends XenForo_ViewPublic_Base
{
	public function renderJson()
	{
		return array(
			'comment' => $this->createTemplateObject('pirateProfile_pirate_comment', $this->_params)
		);
	}
}
<?php

class Album_ViewPublic_Album_ManagePhoto extends XenForo_ViewPublic_Base
{
	public function renderJson()
	{
		return array(
			'photoDescription' => $this->createTemplateObject('album_photo_description', $this->_params)
		);
	}
}
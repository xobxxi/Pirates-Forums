<?php

class Album_ViewPublic_Album_SetCover extends XenForo_ViewPublic_Base
{
	public function renderJson()
	{
		return array(
			'alertMessage'     => new XenForo_Phrase('album_the_cover_photo_has_been_changed'),
			'photoSetCover' => $this->createTemplateObject('album_photo_set_cover', $this->_params)
		);
	}
}
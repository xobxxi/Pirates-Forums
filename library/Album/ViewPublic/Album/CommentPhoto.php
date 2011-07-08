<?php

class Album_ViewPublic_Album_CommentPhoto extends XenForo_ViewPublic_Base
{
	public function renderJson()
	{
		return array(
			'comment' => $this->createTemplateObject('album_photo_comment', $this->_params)
		);
	}
}
<?php

class Album_ViewPublic_Album_CommentsPhoto extends XenForo_ViewPublic_Base
{
	public function renderJson()
	{
		$comments = array();

		if ($this->_params['photo']['first_comment_date'] < $this->_params['firstCommentShown']['comment_date'])
		{
			$comments[] = $this->createTemplateObject(
				'album_photo_comments_before', $this->_params
			);
		}

		foreach ($this->_params['comments'] AS $comment)
		{
			$comments[] = $this->createTemplateObject(
				'album_photo_comment', array('comment' => $comment) + $this->_params
			);
		}

		return array(
			'comments' => $comments
		);
	}
}
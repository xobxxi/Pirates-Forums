<?php

class Album_ViewPublic_Album_CommentLikeConfirmedPhoto extends XenForo_ViewPublic_Base
{
	public function renderJson()
	{
		$photo   = $this->_params['photo'];
		$comment = $this->_params['comment'];

		if (!empty($comment['likeUsers']))
		{
			$params = array(
				'message'  => $comment,
				'likesUrl' => XenForo_Link::buildPublicLink(
					'albums/comment-likes-photo',
					$photo,
					array('comment' => $comment['album_photo_comment_id'])
				)
			);

			$output = $this->_renderer->getDefaultOutputArray(get_class($this), $params, 'likes_summary');
		}
		else
		{
			$output = array('templateHtml' => '', 'js' => '', 'css' => '');
		}

		$output['term'] = ($this->_params['liked'] ? new XenForo_Phrase('unlike') : new XenForo_Phrase('like'));

		return XenForo_ViewRenderer_Json::jsonEncodeForOutput($output);
	}
}
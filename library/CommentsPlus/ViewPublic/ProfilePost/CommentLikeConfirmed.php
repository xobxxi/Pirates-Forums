<?php

class CommentsPlus_ViewPublic_ProfilePost_CommentLikeConfirmed extends XenForo_ViewPublic_Base
{
	public function renderJson()
	{
		$profilePost = $this->_params['profilePost'];
		$comment = $this->_params['comment'];

		if (!empty($comment['likeUsers']))
		{
			$params = array(
				'message'  => $comment,
				'likesUrl' => XenForo_Link::buildPublicLink('profile-post/comment-likes', $profilePost)
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
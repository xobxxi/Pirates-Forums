<?php

class PirateProfile_ViewPublic_Pirate_CommentLikeConfirmed extends XenForo_ViewPublic_Base
{
	public function renderJson()
	{
		$pirate  = $this->_params['pirate'];
		$comment = $this->_params['comment'];

		if (!empty($comment['likeUsers']))
		{
			$params = array(
				'message'  => $comment,
				'likesUrl' => XenForo_Link::buildPublicLink(
					'pirates/comment-likes',
					$pirate,
					array('comment' => $comment['pirate_comment_id'])
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
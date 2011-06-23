<?php

class Album_ViewPublic_Album_LikeConfirmedPhoto extends XenForo_ViewPublic_Base
{
	public function renderJson()
	{
		$photo = $this->_params['photo'];

		if (!empty($photo['likeUsers']))
		{
			$params = array(
				'message'  => $photo,
				'likesUrl' => XenForo_Link::buildPublicLink('album/likes-photo', $photo)
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
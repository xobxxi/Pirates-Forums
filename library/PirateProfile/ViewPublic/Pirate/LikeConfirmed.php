<?php

class PirateProfile_ViewPublic_Pirate_LikeConfirmed extends XenForo_ViewPublic_Base
{
	public function renderJson()
	{
		$pirate = $this->_params['pirate'];

		if (!empty($pirate['likeUsers']))
		{
			$params = array(
				'message'  => $pirate,
				'likesUrl' => XenForo_Link::buildPublicLink('pirate/likes', $pirate)
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
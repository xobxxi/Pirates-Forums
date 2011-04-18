<?php

class CommentsPlus_DataWriter_ProfilePostComment extends XFCP_CommentsPlus_DataWriter_ProfilePostComment
{		
	protected function _getFields()
	{
		$fields = parent::_getFields();
		
		$fields['xf_profile_post_comment'] += array(
			'likes' => array(
				'type' => self::TYPE_UINT_FORCED,
				'default' => 0
			),
			'like_users' => array(
				'type' => self::TYPE_SERIALIZED,
				'default' => 'a:0:{}'
			),
		);
		
		return $fields;
	}
	
	protected function _postDelete()
	{
		parent::_postDelete();
		
		if ($likes = $this->get('likes'))
		{
			$this->getModelFromCache('XenForo_Model_Like')->deleteContentLikes(
				'profile_post_comment', $this->get('profile_post_comment_id')
			);
			
			if ($userId = $this->get('user_id'))
			{
				$this->_db->query('
					UPDATE xf_user
					SET like_count = IF(like_count > ?, like_count - ?, 0)
					WHERE user_id = ?
				', array($likes, $likes, $userId));
			}
		}
	}
}
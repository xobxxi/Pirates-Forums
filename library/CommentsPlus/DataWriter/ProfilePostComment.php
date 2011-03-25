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
}
<?php

class CommentsPlus_Model_ProfilePost extends XFCP_CommentsPlus_Model_ProfilePost
{
	public function canEditProfilePostComment(array $comment, array $profilePost, array $user, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if (!$viewingUser['user_id'])
		{
			return false;
		}

		if (XenForo_Permission::hasPermission($viewingUser['permissions'], 'profilePost', 'editAny'))
		{
			return true;
		}
		
		if ($viewingUser['user_id'] == $comment['user_id'])
		{
			return XenForo_Permission::hasPermission($viewingUser['permissions'], 'profilePost', 'editOwn');
		}
		else
		{
			return false;
		}
	}
	
	public function canLikeProfilePostComment(array $comment, array $user, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if (!$viewingUser['user_id'])
		{
			return false;
		}

		if ($comment['user_id'] == $viewingUser['user_id'])
		{
			$errorPhraseKey = 'liking_own_content_cheating';
			return false;
		}

		return XenForo_Permission::hasPermission($viewingUser['permissions'], 'profilePost', 'like');
	}
	
	public function prepareProfilePostCommentFetchOptions(array $fetchOptions)
	{
		$fetch = parent::prepareProfilePostCommentFetchOptions($fetchOptions);
		$selectFields = $fetch['selectFields'];
		$joinTables   = $fetch['joinTables'];
		
		$db = $this->_getDb();
		
		if (isset($fetchOptions['likeUserId']))
		{
			if (empty($fetchOptions['likeUserId']))
			{
				$selectFields .= ',
					0 AS like_date';
			}
			else
			{
				$selectFields .= ',
					liked_content.like_date';
				$joinTables .= '
					LEFT JOIN xf_liked_content AS liked_content
						ON (liked_content.content_type = \'profile_post_comment\'
							AND liked_content.content_id = profile_post_comment.profile_post_comment_id
							AND liked_content.like_user_id = ' .$db->quote($fetchOptions['likeUserId']) . ')';
			}
		}
		
		return array(
			'selectFields' => $selectFields,
			'joinTables'   => $joinTables
		);
	}
	
	public function prepareProfilePostComment(array $comment, array $profilePost, array $user, array $viewingUser = null)
	{
		$comment = parent::prepareProfilePostComment($comment, $profilePost, $user, $viewingUser);
		
		$comment['canEdit']    = $this->canEditProfilePostComment($comment, $profilePost, $user, $null, $viewingUser);
		$comment['likeUsers']  = unserialize($comment['like_users']);
		
		return $comment;
	}
}
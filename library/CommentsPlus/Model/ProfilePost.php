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
	
	public function prepareProfilePostComment(array $comment, array $profilePost, array $user, array $viewingUser = null)
	{
		$comment = parent::prepareProfilePostComment($comment, $profilePost, $user, $viewingUser);
		
		$comment['canEdit'] = $this->canEditProfilePostComment($comment, $profilePost, $user, $null, $viewingUser);
		
		return $comment;
	}
}
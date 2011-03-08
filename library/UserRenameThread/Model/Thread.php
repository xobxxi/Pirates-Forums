<?php

class UserRenameThread_Model_Thread extends XFCP_UserRenameThread_Model_Thread
{
	public function canEditThread(array $thread, array $forum, &$errorPhraseKey = '', array $nodePermissions = null, array $viewingUser = null)
	{
		$canEditThread = parent::canEditThread($thread, $forum, $errorPhraseKey, $nodePermissions, $viewingUser);
		
		if (!$canEditThread)
		{
			$this->standardizeViewingUserReferenceForNode($thread['node_id'], $viewingUser, $nodePermissions);
			
			if ($viewingUser['user_id'] == $thread['user_id'] && XenForo_Permission::hasContentPermission($nodePermissions, 'editOwnPost'))
			{
				$editLimit = XenForo_Permission::hasContentPermission($nodePermissions, 'editOwnPostTimeLimit');
				
				if ($editLimit != -1 && $thread['post_date'] < XenForo_Application::$time - 60 * $editLimit)
				{
					$errorPhraseKey = array('message_edit_time_limit_expired', 'minutes' => $editLimit);
					return false;
				}

				if (empty($forum['allow_posting']))
				{
					$errorPhraseKey = 'you_may_not_perform_this_action_because_forum_does_not_allow_posting';
					return false;
				}

				return true;
			}
			
			return false;
		}
		
		return true;
	}
}
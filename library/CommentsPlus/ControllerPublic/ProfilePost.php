<?php

class CommentsPlus_ControllerPublic_ProfilePost extends XFCP_CommentsPlus_ControllerPublic_ProfilePost
{
	public function actionCommentEdit()
	{
		$commentId = $this->_input->filterSingle('comment', XenForo_Input::UINT);
		list($comment, $profilePost, $user) = $this->getHelper('UserProfile')->assertProfilePostCommentValidAndViewable($commentId);
		
		$profilePostId = $this->_input->filterSingle('profile_post_id', XenForo_Input::UINT);
		if ($profilePostId != $comment['profile_post_id'])
		{
			return $this->responseNoPermission();
		}
		
		if (!$this->_getProfilePostModel()->canEditProfilePostComment($comment, $profilePost, $user, $errorPhraseKey))
		{
			throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
		}
		
		if ($this->_request->isPost())
		{
			$inputMessage = $this->_input->filterSingle('message', XenForo_Input::STRING);
			
			$dw = XenForo_DataWriter::create('XenForo_DataWriter_ProfilePostComment');
			$dw->setExistingData($commentId);
			$dw->set('message', $inputMessage);
			$dw->save();
			
			return $this->getProfilePostSpecificRedirect($profilePost, $user);
		}
		else
		{
			$viewParams = array(
				'comment'     => $comment,
				'profilePost' => $profilePost,
				'user'        => $user
			);

			return $this->responseView(
				'CommentsPlus_ViewPublic_ProfilePost_CommentEdit',
				'commentsPlus_profile_post_comment_edit',
				$viewParams
			);
		}
	}
}
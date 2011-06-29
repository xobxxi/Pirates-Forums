<?php

class CommentsPlus_ControllerPublic_ProfilePost extends XFCP_CommentsPlus_ControllerPublic_ProfilePost
{
	public function actionComments()
	{
		$response = parent::actionComments();
		if (isset($response->params['comments']))
		{
			$profilePost = $response->params['profilePost'];
			$profilePostId = $profilePost['profile_post_id'];
			
			$user = $response->params['user'];
			
			$beforeDate = $this->_input->filterSingle('before', XenForo_Input::UINT);
			
			$profilePostModel = $this->_getProfilePostModel();

			$comments = $profilePostModel->getProfilePostCommentsByProfilePost($profilePostId, $beforeDate, array(
				'join' => XenForo_Model_ProfilePost::FETCH_COMMENT_USER,
				'limit' => 50,
				'likeUserId' => XenForo_Visitor::getUserId()
			));
			
			foreach ($comments AS &$comment)
			{
				$comment = $profilePostModel->prepareProfilePostComment($comment, $profilePost, $user);
			}
			
			$response->params['comments'] = $comments;
		}
		
		return $response;
	}
	
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
	
	public function actionCommentLike()
	{
		$commentId = $this->_input->filterSingle('comment', XenForo_Input::UINT);
		
		list($comment, $profilePost, $user) = $this->getHelper('UserProfile')->assertProfilePostCommentValidAndViewable($commentId);
		
		$profilePostId = $this->_input->filterSingle('profile_post_id', XenForo_Input::UINT);
		if ($profilePostId != $comment['profile_post_id'])
		{
			return $this->responseNoPermission();
		}
		
		if (!$this->_getProfilePostModel()->canLikeProfilePostComment($comment, $user, $errorPhraseKey))
		{
			throw $this->getErrorOrNoPermissionResponseException($errorPhraseKey);
		}
		
		$likeModel = $this->getModelFromCache('XenForo_Model_Like');

		$existingLike = $likeModel->getContentLikeByLikeUser(
			'profile_post_comment', $commentId, XenForo_Visitor::getUserId()
		);
		
		if ($this->_request->isPost())
		{
			if ($existingLike)
			{
				$latestUsers = $likeModel->unlikeContent($existingLike);
			}
			else
			{
				$latestUsers = $likeModel->likeContent(
					'profile_post_comment', $commentId, $comment['user_id']
				);
			}
			
			$liked = ($existingLike ? false : true);

			if ($this->_noRedirect() && $latestUsers !== false)
			{
				$comment['likeUsers'] = $latestUsers;
				$comment['likes'] += ($liked ? 1 : -1);
				$comment['like_date'] = ($liked ? XenForo_Application::$time : 0);

				$viewParams = array(
					'comment' => $comment,
					'profilePost' => $profilePost,
					'user'   => $user,
					'liked'  => $liked,
				);

				return $this->responseView(
					'CommentsPlus_ViewPublic_ProfilePost_CommentLikeConfirmed',
					'',
					$viewParams
				);
			}
			else
			{
				return $this->responseRedirect(
						XenForo_ControllerResponse_Redirect::SUCCESS,
						XenForo_Link::buildPublicLink('profile-posts', $profilePost)
				);
			}
		}
		else
		{
			$viewParams = array(
				'comment'     => $comment,
				'profilePost' => $profilePost,
				'user'        => $user,
				'like'        => $existingLike
			);

			return $this->responseView(
				'CommentsPlus_ViewPublic_ProfilePost_LikeComment',
				'commentsPlus_comment_like',
				$viewParams
			);
		}
	}
	
	public function actionCommentLikes()
	{
		$commentId = $this->_input->filterSingle('comment', XenForo_Input::UINT);
		
		list($comment, $profilePost, $user) = $this->getHelper('UserProfile')->assertProfilePostCommentValidAndViewable($commentId);
		
		$likes = $this->getModelFromCache('XenForo_Model_Like')
		              ->getContentLikes('profile_post_comment', $commentId);
		if (!$likes)
		{
			return $this->responseError(
				new XenForo_Phrase('commentsPlus_no_one_has_liked_this_comment_yet')
			);
		}

		$viewParams = array(
			'comment' => $comment,
			'user'    => $user,
			'likes'   => $likes	
		);
		
		return $this->responseView(
			'CommentsPlus_ViewPublic_ProfilePost_CommentLikes',
			'commentsPlus_comment_likes',
			$viewParams
		);
	}
}
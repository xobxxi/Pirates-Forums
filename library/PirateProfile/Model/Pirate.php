<?php

class PirateProfile_Model_Pirate extends XenForo_Model
{
	const FETCH_PIRATE_USER  = 0x01;
	const FETCH_COMMENT_USER = 0x01;
	
	public function preparePirateFetchOptions(array $fetchOptions)
	{
		$selectFields = '';
		$joinTables = '';
		
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
						ON (liked_content.content_type = \'pirate\'
							AND liked_content.content_id = pirate.pirate_id
							AND liked_content.like_user_id = ' .$db->quote($fetchOptions['likeUserId']) . ')';
			}
		}
		
		if (!empty($fetchOptions['join']))
		{
			if ($fetchOptions['join'] & self::FETCH_PIRATE_USER)
			{
				$selectFields .= ',
					user.*,
					IF(user.username IS NULL, pirate.user_id, user.user_id) AS user_id';
				$joinTables .= '
					LEFT JOIN xf_user AS user ON
						(user.user_id = pirate.user_id)';
			}
		}

		return array(
			'selectFields' => $selectFields,
			'joinTables'   => $joinTables
		);
	}
	
	public function getAllPirates($limit, $page)
	{
		$start = ($limit * ($page - 1));
		// TODO: use built in limit options
		
		return $this->_getDb()->fetchAll("
			SELECT pirate_id, user_id, name
			FROM pirate
			ORDER BY name ASC
			LIMIT {$start}, {$limit}
		");
	}
	
	public function getUserPirates($user_id)
	{
		return $this->_getDb()->fetchAll('
			SELECT pirate_id, user_id, name
			FROM pirate
			WHERE user_id = ?
		', $user_id);
	}

	public function getPirateById($id, array $fetchOptions = array())
	{
		$sqlClauses = $this->preparePirateFetchOptions($fetchOptions);
		
		$pirate = $this->_getDb()->fetchRow('
			SELECT *
			' . $sqlClauses['selectFields'] . '
			FROM pirate
			' . $sqlClauses['joinTables'] . '
			WHERE pirate_id = ?
		', $id);

		if (!isset($pirate)) return false;

		$pirate = preg_replace("/^0$/is", null, $pirate);

		return $pirate;
	}
	
	public function getPiratesByIds($ids, array $fetchOptions = array())
	{
		$sqlClauses = $this->preparePirateFetchOptions($fetchOptions);
		
		return $this->fetchAllKeyed('
			SELECT *
			' . $sqlClauses['selectFields'] . '
			FROM pirate
			' . $sqlClauses['joinTables'] . '
			WHERE pirate.pirate_id IN (' . $this->_getDb()->quote($ids) . ')
		', 'pirate_id');
		
	}

	public function getPicturesById($id)
	{
		$attachmentModel = $this->_getAttachmentModel();
		$attachments = $attachmentModel->getAttachmentsByContentId('pirate', $id);

		if (empty($attachments)) return false;

		foreach ($attachments as $attachment)
		{
			$return[] = $attachmentModel->prepareAttachment($attachment);
		}

		return $return;
	}

	public function getAttachmentParams(array $contentData)
	{
		if ($this->canUploadAndManageAttachment())
		{
			return array(
				'hash' => md5(uniqid('', true)),
				'content_type' => 'pirate',
				'content_data' => $contentData
			);
		}
		else
		{
			return false;
		}
	}

	public function canUploadAndManageAttachment()
	{
		$perms = $this->getPermissions();
		if (!$perms['canAttach']) return false;
		
		return true;
	}
	
	public function preparePirateCommentFetchOptions(array $fetchOptions)
	{
		$selectFields = '';
		$joinTables = '';

		if (!empty($fetchOptions['join']))
		{
			if ($fetchOptions['join'] & self::FETCH_COMMENT_USER)
			{
				$selectFields .= ',
					user.*,
					IF(user.username IS NULL, pirate_comment.username, user.username) AS username';
				$joinTables .= '
					LEFT JOIN xf_user AS user ON
						(user.user_id = pirate_comment.user_id)';
			}
		}

		return array(
			'selectFields' => $selectFields,
			'joinTables'   => $joinTables
		);
	}
	
	public function getPirateCommentById($pirateCommentId, $fetchOptions = array())
	{
		$sqlClauses = $this->preparePirateCommentFetchOptions($fetchOptions);
		
		return $this->_getDb()->fetchRow('
			SELECT pirate_comment.*
			' . $sqlClauses['selectFields'] . '
			FROM pirate_comment
			' . $sqlClauses['joinTables'] . '
			WHERE pirate_comment.pirate_comment_id = ?
		', $pirateCommentId);
	}
	
	public function getPirateCommentsByIds(array $ids, $fetchOptions = array())
	{
		if (!$ids)
		{
			return array();
		}

		$sqlClauses = $this->preparePirateCommentFetchOptions($fetchOptions);

		return $this->fetchAllKeyed('
			SELECT pirate_comment.*
			' . $sqlClauses['selectFields'] . '
			FROM pirate_comment
			' . $sqlClauses['joinTables'] . '
			WHERE pirate_comment.pirate_comment_id IN (' . $this->_getDb()->quote($ids) . ')
		', 'pirate_comment_id');
	}
	
	public function getPirateCommentUserIds($pirateId)
	{
		return $this->_getDb()->fetchCol('
			SELECT DISTINCT user_id
			FROM pirate_comment
			WHERE pirate_id = ?
		', $pirateId);
	}
	
	public function getPirateCommentsByPirate($pirateId, $beforeDate = 0, array $fetchOptions = array())
	{
		$sqlClauses = $this->preparePirateCommentFetchOptions($fetchOptions);
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

		if ($beforeDate)
		{
			$beforeCondition = 'AND pirate_comment.comment_date < ' . $this->_getDb()->quote($beforeDate);
		}
		else
		{
			$beforeCondition = '';
		}

		$results = $this->fetchAllKeyed($this->limitQueryResults(
			'
				SELECT pirate_comment.*
				' . $sqlClauses['selectFields'] . '
				FROM pirate_comment
					' . $sqlClauses['joinTables'] . '
				WHERE pirate_comment.pirate_id = ?
					' . $beforeCondition . '
				ORDER BY pirate_comment.comment_date DESC
			', $limitOptions['limit'], $limitOptions['offset']
		), 'pirate_comment_id', $pirateId);

		return array_reverse($results, true);
	}
	
	public function addPirateCommentsToPirate(array $pirate, array $fetchOptions = array())
	{
		if ($pirate['latest_comment_ids'])
		{
			foreach (explode(',', $pirate['latest_comment_ids']) AS $commentId)
			{
				$commentIdMap[intval($commentId)] = $pirate['pirate_id'];
			}
			
			$pirate['comments'] = array();
		}

		if (isset($commentIdMap))
		{
			$comments = $this->getPirateCommentsByIds(array_keys($commentIdMap), $fetchOptions);
			foreach ($commentIdMap AS $commentId => $profilePostId)
			{
				if (isset($comments[$commentId]))
				{
					if (!isset($pirate['first_shown_comment_date']))
					{
						$pirate['first_shown_comment_date'] = $comments[$commentId]['comment_date'];
					}
					$pirate['comments'][$commentId] = $comments[$commentId];
				}
			}
		}

		return $pirate;
	}
	
	public function preparePirateComment(array $comment, array $profilePost, array $user, array $viewingUser = null)
	{
		$comment['canDelete'] = $this->canDeletePirateComment($comment, $profilePost, $user, $null, $viewingUser);
		
		return $comment;
	}
	
	public function canDeletePirateComment(array $comment, array $pirate, array $user, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if (!$viewingUser['user_id'])
		{
			return false;
		}
		
		$perms = $this->getPermissions($viewingUser);
		if ($perms['canManage'])
		{
			return true;
		}

		if ($viewingUser['user_id'] == $comment['user_id'])
		{
			return true;
		}
		else if ($viewingUser['user_id'] == $pirate['user_id'])
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	public function getPermissions(array $viewingUser = null)
	{
			$this->standardizeViewingUserReference($viewingUser);
			
			$permissions = $viewingUser['permissions'];
			
			$perms = array(
				'canView'   => $this->_hasPermission($permissions, 'pirateProfile', 'canView'),
				'canAdd'    => $this->_hasPermission($permissions, 'pirateProfile', 'canAdd'),
				'canAttach' => $this->_hasPermission($permissions, 'pirateProfile', 'canAttach'),
				'canEdit'   => $this->_hasPermission($permissions, 'pirateProfile', 'canEdit'),
				'canDelete' => $this->_hasPermission($permissions, 'pirateProfile', 'canDelete'),
				'canManage' => $this->_hasPermission($permissions, 'pirateProfile', 'canManage')
			);

			return $perms;
	}
	
	public function canLikePirate(array $pirate, array $user, &$errorPhraseKey = '', array $viewingUser = null)
	{
		$this->standardizeViewingUserReference($viewingUser);

		if (!$viewingUser['user_id'])
		{
			return false;
		}

		if ($pirate['user_id'] == $viewingUser['user_id'])
		{
			$errorPhraseKey = 'liking_own_content_cheating';
			return false;
		}
		
		$perms = $this->getPermissions($viewingUser);
		if (!$perms['canView']) return false;

		return true;
	}
	
	protected function _hasPermission($permissions, $group, $permission)
	{
		return XenForo_Permission::hasPermission($permissions, $group, $permission);
	}
	
	protected function _getAttachmentModel()
	{
		return $this->getModelFromCache('XenForo_Model_Attachment');
	}
}
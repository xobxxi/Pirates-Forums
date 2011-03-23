<?php

class PirateProfile_Model_Pirate extends XenForo_Model
{
	const FETCH_PIRATE_USER  = 0x01;
	const FETCH_COMMENT_USER = 0x01;
	
	public function preparePirateConditions(array $conditions, array &$fetchOptions)
	{
		$db = $this->_getDb();
		$sqlConditions = array();

		if (!empty($conditions['name']))
		{
			if (is_array($conditions['name']))
			{
				$sqlConditions[] = 'pirate.name LIKE ' . XenForo_Db::quoteLike($conditions['name'][0], $conditions['name'][1], $db);
			}
			else
			{
				$sqlConditions[] = 'user.username LIKE ' . XenForo_Db::quoteLike($conditions['name'], 'lr', $db);
			}
		}
		
		return $this->getConditionsForClause($sqlConditions);
	}
	
	public function preparePirateOrderOptions(array &$fetchOptions, $defaultOrderSql = '')
	{
		$choices = array(
			'id'                => 'pirate.pirate_id',
			'user_id'           => 'pirate.user_id',
			'name'              => 'pirate.name',
			'modified_date'     => 'pirate.modified_date',
			'level'             => 'pirate.level',
			'guild'             => 'pirate.guild',
			'last_comment_date' => 'pirate.last_comment_date',
			'likes'             => 'pirate.likes'
		);
		
		$orderSql = null;

		if (!empty($fetchOptions['order']) && isset($choices[$fetchOptions['order']]))
		{
			$orderSql = $choices[$fetchOptions['order']];

			if (!empty($fetchOptions['direction']))
			{
				$orderSql .= (strtolower($fetchOptions['direction']) == 'desc' ? ' DESC' : ' ASC');
			}
			
			$orderSql .= ',' . $choices['name'] . ' ASC';
		}

		if (!$orderSql)
		{
			$orderSql = $defaultOrderSql;
		}
		return ($orderSql ? 'ORDER BY ' . $orderSql : '');
	}
	
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
			'joinTables'   => $joinTables,
		);
	}
	
	public function countPirates(array $conditions)
	{
		$fetchOptions = array();
		$whereClause = $this->preparePirateConditions($conditions, $fetchOptions);

		$joinOptions = $this->preparePirateFetchOptions($fetchOptions);

		return $this->_getDb()->fetchOne('
			SELECT COUNT(*)
			FROM pirate
			' . $joinOptions['joinTables'] . '
			WHERE ' . $whereClause
		);
	}
	
	public function getPirates(array $conditions, array $fetchOptions = array())
	{
		$whereClause  = $this->preparePirateConditions($conditions, $fetchOptions);
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);
		$orderClause  = $this->preparePirateOrderOptions($fetchOptions, 'pirate.name');
		
		return $this->fetchAllKeyed($this->limitQueryResults('
			SELECT pirate_id, user_id, name, modified_date, likes, level, guild, last_comment_date, latest_comment_ids
			FROM pirate
			WHERE ' . $whereClause . '
			' . $orderClause . '
			', $limitOptions['limit'], $limitOptions['offset']
		), 'pirate_id');
	}
	
	public function getLatestPirates(array $criteria, array $fetchOptions = array())
	{
		$fetchOptions['order'] = 'modified_date';
		$fetchOptions['direction'] = 'desc';

		return $this->getPirates($criteria, $fetchOptions);
	}
	
	public function getNewestPirates(array $criteria, array $fetchOptions = array())
	{
		$fetchOptions['order'] = 'id';
		$fetchOptions['direction'] = 'desc';
		
		return $this->getPirates($criteria, $fetchOptions);
	}
	
	public function getUserPirates($user_id, $fetchOptions = array())
	{
		$sqlClauses = $this->preparePirateFetchOptions($fetchOptions);
		
		return $this->_getDb()->fetchAll('
			SELECT pirate_id, user_id, name
			' . $sqlClauses['selectFields'] . '
			FROM pirate
			' . $sqlClauses['joinTables'] . '
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
	
	public function getPirateByName($name, array $fetchOptions = array())
	{
		$sqlClauses = $this->preparePirateFetchOptions($fetchOptions);
		
		return $this->_getDb()->fetchRow('
			SELECT *
			' . $sqlClauses['selectFields'] . '
			FROM pirate
			' . $sqlClauses['joinTables'] . '
			WHERE pirate.name LIKE ?
			ORDER BY pirate.name ASC
		', $name);
	}
	
	public function getPiratesByName($name, array $fetchOptions = array())
	{
		$sqlClauses = $this->preparePirateFetchOptions($fetchOptions);
		
		return $this->fetchAllKeyed('
			SELECT *
			' . $sqlClauses['selectFields'] . '
			FROM pirate
			' . $sqlClauses['joinTables'] . '
			WHERE pirate.name LIKE ?
		', 'pirate_id', $name);
	}
	
	public function preparePirate($pirate)
	{
		$options = XenForo_Application::get('options');
		
		$pirate += array(
			'likeUsers'  => unserialize($pirate['like_users']),
			'skills_set' => true,
			'max'	     => array(
				'weapon' => $options->pirateProfile_maxLevelWeapon,
				'skill'	 => $options->pirateProfile_maxLevelSkill
			),
			'weapons'    => array(),
			'skills'     => array(),
			'rank'       => array()
		);

		foreach ($pirate as $name => $level)
		{
			switch ($name)
			{
				case 'cannon':
				case 'sailing':
				case 'sword':
				case 'shooting':
				case 'doll':
				case 'dagger':
				case 'grenade':
				case 'staff':
					$phrase = new XenForo_Phrase('pirateProfile_pirate_' . $name);
					$phrase = $phrase->__toString();
					$pirate['weapons'][$name] = array(
						'name'	=> $phrase,
						'level' => $level
					);
					unset($pirate[$name]);
				break;
				case 'potions':
				case 'fishing':
					$phrase = new XenForo_Phrase('pirateProfile_pirate_' . $name);
					$phrase = $phrase->__toString();
					$pirate['skills'][$name] = array(
						'name'	=> $phrase,
						'level' => $level
					);
					unset($pirate[$name]);
				break;
			}
		}

		$skillsSet = false;
		foreach ($pirate['weapons'] as $weapon)
		{
			if (!empty($weapon['level'])) $skillsSet = true;
		}
		foreach ($pirate['skills'] as $skill)
		{
			if (!empty($skill['level'])) $skillsSet = true;
		}

		if (!$skillsSet)
		{
			$pirate['skills_set'] = false;
		}
		
		$pirate['ranks'] = array();
		
		if (!empty($pirate['infamy_privateering']))
		{
			$privateering = new XenForo_Phrase(
				'pirateProfile_pirate_rank_privateering_' . $pirate['infamy_privateering']
			);
			
			$pirate['ranks']['privateering'] = array(
				'title' => $pirate['infamy_privateering'],
				'name'  => $privateering
			);
		}
		
		if (!empty($pirate['infamy_pvp']))
		{
			$pvp = new XenForo_Phrase(
				'pirateProfile_pirate_rank_pvp_' . $pirate['infamy_pvp']
			);
			
			$pirate['ranks']['pvp'] = array(
				'title' => $pirate['infamy_pvp'],
				'name'  => $pvp
			);
		}
		
		unset($pirate['infamy_privateering'], $pirate['infamy_pvp']);
		
		$pictures          = $this->getPicturesById($pirate['pirate_id']);
		$pirate['picture'] = $this->_preparePicture($pictures[0], $pirate['make_fit']);

		return $pirate;
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
	
	protected function _preparePicture($picture, $fit = false)
	{
		if (empty($picture)) return false;

		$width  = 250;
		$height = 280;
		
		if ($fit)
		{
			$picture['width']  = $width;
			$picture['height'] = $height;
			
			return $picture;
		}

		switch ($picture['width'] >= $picture['height'])
		{
			default:
			case true:
				$ratio = ($picture['height'] / $height);
				$picture['width']  = intval(round($picture['width'] / $ratio));
				$picture['height'] = $height;
				$picture['margin'] = intval(round(-(($picture['width'] - $width) / 2)));
			break;
			case false:
				$ratio = ($picture['width'] /  $width);
				$picture['width']  = $width;
				$picture['height'] = intval(round($picture['height'] / $ratio));
			break;
		}

		return $picture;
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
			foreach ($commentIdMap AS $commentId => $pirateId)
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
	
	public function preparePirateComment(array $comment, array $pirate, array $user, array $viewingUser = null)
	{
		$comment['canEdit']   = $this->canEditPirateComment($comment, $pirate, $user, $viewingUser);
		$comment['canDelete'] = $this->canDeletePirateComment($comment, $pirate, $user, $null, $viewingUser);
		
		return $comment;
	}
	
	public function canEditPirateComment(array $comment, array $pirate, array $user, &$errorPhraseKey = '', array $viewingUser = null)
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
		else
		{
			return false;
		}
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
	
	public static function getRanks()
	{
		$ranks = array(
			'privateering' => array(
				'', 'mariner', 'lieutenant', 'commander', 'captain', 'commodore', 'vice_admiral', 'admiral'
			),
			'pvp' => array(
				'', 'rookie', 'brawler', 'duelist', 'buccaneer', 'swashbuckler', 'war_dog', 'war_master'
			)
		);
		
		foreach ($ranks as $type => $children)
		{
			foreach ($children as $key => $rank)
			{
				if (!empty($rank))
				{
					$name = new XenForo_Phrase('pirateProfile_pirate_rank_' . $type . '_' . $rank);
					$ranks[$type][$rank] = $name->__toString();
					unset($ranks[$type][$key]);
				}
			}
		}
		
		return $ranks;
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
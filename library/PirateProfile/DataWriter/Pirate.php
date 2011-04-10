<?php

class PirateProfile_DataWriter_Pirate extends XenForo_DataWriter
{
	protected $_username;
	
	protected function _getFields()
	{
		$options = XenForo_Application::get('options');
		
		$maxLevels = array(
			'notoriety' => $options->pirateProfile_maxLevelNotoriety,
			'weapon'    => $options->pirateProfile_maxLevelWeapon,
			'skill'     => $options->pirateProfile_maxLevelSkill
		);
		
		$weapons = PirateProfile_Model_Pirate::getWeapons(true, true);
		$skills  = PirateProfile_Model_Pirate::getSkills(true, true);
		$ranks   = PirateProfile_Model_Pirate::getRanks(true, true);
		
		$fields = array(
			'pirate' => array(
				'pirate_id' => array(
					'type'			=> self::TYPE_UINT,
					'autoIncrement' => true
				),
				'user_id' => array(
					'type'	   => self::TYPE_UINT,
					'required' => true
				),
				'modified_date' => array(
					'type'	   => self::TYPE_UINT,
					'required' => true,
					'default'  => XenForo_Application::$time
				),
				'name' => array(
					'type'			=> self::TYPE_STRING,
					'maxLength'		=> 32,
					'required'		=> true,
					'requiredError' => 'pirateProfile_please_enter_pirate_name'
				),
				'level' => array(
					'type' => self::TYPE_UINT,
					'min'  => 1,
					'max'  => $maxLevels['notoriety']
				),
				'guild' =>	array(
					'type'		=> self::TYPE_STRING,
					'maxLength' => 32
				),
				'likes' => array(
					'type' => self::TYPE_UINT_FORCED,
					'default' => 0
				),
				'like_users' => array(
					'type' => self::TYPE_SERIALIZED,
					'default' => 'a:0:{}'
				),
				'extra' => array(
					'type'		=> self::TYPE_STRING,
					'maxLength' => 32
				),
				'make_fit' => array(
					'type'     => self::TYPE_UINT,
					'required' => true,
					'default'  => 0
				),
				'comment_count' => array(
					'type'    => self::TYPE_UINT_FORCED,
					'default' => 0
				),
				'first_comment_date' => array(
					'type'    => self::TYPE_UINT,
					'default' => 0
				),
				'last_comment_date' => array(
				'type'    => self::TYPE_UINT,
				'default' => 0
				),
				'latest_comment_ids' => array(
					'type'      => self::TYPE_BINARY,
					'default'   => '',
					'maxLength' => 100
				)
			)
		);
		
		foreach ($weapons as $weapon)
		{
			$fields['pirate'][$weapon] = array(
				'type'    => self::TYPE_STRING,
				'max'     => $maxLevels['weapon'],
				'default' => 0
			);
		}
		
		foreach ($skills as $skill)
		{
			$fields['pirate'][$skill] = array(
				'type'    => self::TYPE_UINT,
				'max'     => $maxLevels['skill'],
				'default' => 0
			);
		}
		
		foreach (array_keys($ranks) as $type)
		{
			$fields['pirate'][$type] = array(
				'type'          => self::TYPE_STRING,
				'allowedValues' => array_keys($ranks[$type])
			);
		}
		
		return $fields;
	}

	protected function _getExistingData($data)
	{
		if (!$pirate_id = $this->_getExistingPrimaryKey($data))
		{
			return false;
		}

		if (!$pirate = $this->_getPirateModel()->getPirateById($pirate_id))
		{
			return false;
		}

		return $this->getTablesDataFromArray($pirate);
	}

	protected function _getUpdateCondition($tableName)
	{
		return 'pirate_id = ' . $this->_db->quote($this->getExisting('pirate_id'));
	}

	public function setPirate(array $input)
	{	
		$weapons = PirateProfile_Model_Pirate::getWeapons(false, true);
		
		foreach ($input as $name => $level)
		{
			if (!empty($level) && isset($weapons[$name]['level']))
			{
				if ($input['level'] < $weapons[$name]['level'])
				{
					return false;
				}
			}
		}

		$this->bulkSet($input);
		$this->set('modified_date', XenForo_Application::$time);

		return true;
	}

	protected final function _postSave()
	{
		$pictureHash = $this->getExtraData('attachment_hash');

		if ($pictureHash)
		{
			$this->_associatePictures($pictureHash);
		}
		
		$this->_publishToNewsFeed();
		
		return true;
	}
	
	protected function _postDelete()
	{
		$pirateId = $this->get('pirate_id');
		
		$this->getModelFromCache('XenForo_Model_Alert')->deleteAlerts('pirate', $pirateId);
		
		if ($likes = $this->get('likes'))
		{
			$this->getModelFromCache('XenForo_Model_Like')->deleteContentLikes(
				'pirate', $pirateId
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
		
		if ($this->get('comment_count'))
		{
			$this->_db->delete('pirate_comment', 'pirate_id = ' . $this->_db->quote($pirateId));
		}
		
		$this->getModelFromCache('XenForo_Model_NewsFeed')->delete(
			'pirate',
			$pirateId
		);
		
		if ($this->get('attach_count'))
		{
			$this->getModelFromCache('XenForo_Model_Attachment')->deleteAttachmentsFromContentIds(
				'pirate',
				array($pirateId)
			);
		}
	}

	protected function _associatePictures($attachmentHash)
	{
		$rows = $this->_db->update('xf_attachment', array(
			'content_type' => 'pirate',
			'content_id' => $this->get('pirate_id'),
			'temp_hash' => '',
			'unassociated' => 0
		),  'temp_hash = ' . $this->_db->quote($attachmentHash));
		
		if ($rows && $this->isUpdate())
		{
			$id = $this->get('user_id');
			
			$this->_getNewsFeedModel()->publish(
				$id,
				$this->_getUsername($id),
				'pirate',
				$this->get('pirate_id'),
				'picture',
				array('hash' => $attachmentHash)
			);
		}
		
		return true;
	}
	
	protected function _publishToNewsFeed()
	{
		$changes = array();
		
		if ($this->isInsert())
		{
			$changes[] = array(
				'action' => 'add',
				'data'   => array()
			);
		}
		
		if ($this->isUpdate())
		{	
			if ($this->isChanged('name'))
			{
				$changes[] = array(
					'action' => 'name',
					'data'   => array('old' => $this->getExisting('name'), 'new' => $this->get('name'))
				);
			}
		
			if ($this->isChanged('guild'))
			{
				$guild = $this->get('guild');
				if (!empty($guild))
				{
					$changes[] = array(
						'action' => 'guild',
						'data'   => array('old' => $this->getExisting('guild'), 'new' => $guild)
					);
				}
			}
			
			$skills = $this->_getChangedSkills();
			if (!empty($skills))
			{
				$changes[] = array(
					'action' => 'level',
					'data'   => $skills
				);
			}
			
			$ranks = $this->_getChangedRanks();
			if (!empty($ranks))
			{
				foreach ($ranks as $rank)
				{	
					$changes[] = array(
						'action' => 'rank',
						'data'   => $rank 
					);
				}
			}
			
			if ($this->isChanged('extra'))
			{
				$changes[] = array(
					'action' => 'extra',
					'data'   => array('extra' => $this->get('extra'))
				);
			}
		}
		
		foreach ($changes as $change)
		{
			$id = $this->get('user_id');
			
			$this->_getNewsFeedModel()->publish(
				$id,
				$this->_getUsername($id),
				'pirate',
				$this->get('pirate_id'),
				$change['action'],
				$change['data']
			);
		}
		
		return true;
	}
	
	protected function _getChangedSkills()
	{
		$skills = array('level');
		
		$skills = array_merge(
			$skills,
			PirateProfile_Model_Pirate::getWeapons(true, true), 
			PirateProfile_Model_Pirate::getSkills(true, true)
		);
		
		$changed = array();
		foreach ($skills as $skill)
		{
			$existing = $this->getExisting($skill);
			$updated  = $this->get($skill);
			
			if (!empty($current))
			{
				if ($this->isChanged($skill) && ($updated > $existing))
				{
					$changed[$skill] = $this->get($skill);
				}
			}
		}
		
		return $changed;
	}
	
	protected function _getChangedRanks()
	{
		$ranks = PirateProfile_Model_Pirate::getRanks(true, true);
		
		$types = array_keys($ranks);
		$changed = array();
		foreach ($types as $type)
		{
			$new = $this->get($type);
			if (!empty($new) && $this->isChanged($type))
			{
				$changed[$type] = array(
					'type' => $type,
					'old'  => $this->getExisting($type),
					'new'  => $new
				);
			}
		}
		
		return $changed;
	}
	
	protected function _setUsername($id)
	{
		$user = $this->_getUserModel()->getUserById($id);
		$this->_username[$id] = $user['username'];
		
		return $this->_username[$id];
	}
	
	protected function _getUsername($id)
	{
		if (!$username = $this->_username[$id])
		{
			$username = $this->_setUsername($id);
		}
		
		return $username;
	}

	public function insertNewComment($commentId, $commentDate)
	{
		$this->set('comment_count', $this->get('comment_count') + 1);
		if (!$this->get('first_comment_date') || $commentDate < $this->get('first_comment_date'))
		{
			$this->set('first_comment_date', $commentDate);
		}
		$this->set('last_comment_date', max($this->get('last_comment_date'), $commentDate));

		$latest = $this->get('latest_comment_ids');
		$ids = ($latest ? explode(',', $latest) : array());
		$ids[] = $commentId;

		if (count($ids) > 3)
		{
			$ids = array_slice($ids, -3);
		}

		$this->set('latest_comment_ids', implode(',', $ids));
	}
	
	public function rebuildPirateCommentCounters()
	{
		$db = $this->_db;
		$pirateId = $this->get('pirate_id');

		$counts = $db->fetchRow('
			SELECT COUNT(*) AS comment_count,
				MIN(comment_date) AS first_comment_date,
				MAX(comment_date) AS last_comment_date
			FROM pirate_comment
			WHERE pirate_id = ?
		', $pirateId);

		if ($counts['comment_count'])
		{
			$ids = $db->fetchCol($db->limit(
				'
					SELECT pirate_comment_id
					FROM pirate_comment
					WHERE pirate_id = ?
					ORDER BY comment_date DESC
				', 3
			), $pirateId);
			$ids = array_reverse($ids);
		}
		else
		{
			$ids = array();
		}

		$this->bulkSet($counts);
		$this->set('latest_comment_ids', implode(',', $ids));
	}
	
	protected function _getPirateModel()
	{
		return $this->getModelFromCache('PirateProfile_Model_Pirate');
	}
	
	protected function _getUserModel()
	{
		return $this->getModelFromCache('XenForo_Model_User');
	}
	
	protected function _getNewsFeedModel()
	{
		return $this->getModelFromCache('XenForo_Model_NewsFeed');
	}
}
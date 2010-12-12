<?php

class PirateProfile_DataWriter_Pirate extends XenForo_DataWriter
{
	protected $_username;
	
	protected function _getFields()
	{
		$options = XenForo_Application::get('options');

		$maxLevelNotoriety = $options->pirateProfile_maxLevelNotoriety;
		$maxLevelWeapon	   = $options->pirateProfile_maxLevelWeapon;
		$maxLevelSkill	   = $options->pirateProfile_maxLevelSkill;

		return array(
			'pirates' => array(
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
					'max'  => $maxLevelNotoriety
				),
				'guild' =>	array(
					'type'		=> self::TYPE_STRING,
					'maxLength' => 32
				),
				'sailing' => array(
					'type' => self::TYPE_UINT,
					'max'  => $maxLevelWeapon
				),
				'cannon' => array(
					'type' => self::TYPE_UINT,
					'max'  => $maxLevelWeapon
				),
				'sword' => array(
					'type' => self::TYPE_UINT,
					'max'  => $maxLevelWeapon
				),
				'shooting' => array(
					'type' => self::TYPE_UINT,
					'max'  => $maxLevelWeapon
				),
				'doll' => array(
					'type' => self::TYPE_UINT,
					'max'  => $maxLevelWeapon
				),
				'dagger' => array(
					'type' => self::TYPE_UINT,
					'max'  => $maxLevelWeapon
				),
				'grenade' => array(
					'type' => self::TYPE_UINT,
					'max'  => $maxLevelWeapon
				),
				'staff' => array(
					'type' => self::TYPE_UINT,
					'max'  => $maxLevelWeapon
				),
				'potions' => array(
					'type' => self::TYPE_UINT,
					'max'  => $maxLevelSkill
				),
				'fishing' => array(
					'type' => self::TYPE_UINT,
					'max'  => $maxLevelSkill
				),
				'extra' => array(
					'type'		=> self::TYPE_STRING,
					'maxLength' => 32
				),
				'make_fit' => array(
					'type'     => self::TYPE_UINT,
					'required' => true,
					'default'  => 0
				)
			)
		);
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

		$returnData = $this->getTablesDataFromArray($pirate);
		return $returnData;
	}

	protected function _getUpdateCondition($tableName)
	{
		return 'pirate_id = ' . $this->_db->quote($this->getExisting('pirate_id'));
	}

	public function setPirate(array $input)
	{
		foreach ($input as $name => $level)
		{
			if (!empty($level))
			{
				switch ($name)
				{
					case 'doll':
						$required = 5;
						break;
					case 'dagger':
						$required = 12;
						break;
					case 'grenade':
						$required = 20;
						break;
					case 'staff':
						$required = 30;
					break;
				}
			}
		}

		if (!empty($required)) {
			if ($input['level'] < $required)
				return false;
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
				$changes[] = array(
					'action' => 'guild',
					'data'   => array('old' => $this->getExisting('guild'), 'new' => $this->get('guild'))
				);
			}
			
			$skills = $this->_getChangedSkills();
			
			if (!empty($skills))
			{
				$changes[] = array(
					'action' => 'level',
					'data'   => $skills
				);
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
		$skills = array(
			'level',
			'cannon',
			'sailing',
			'sword',
			'shooting',
			'doll',
			'dagger',
			'grenade',
			'staff',
			'potions',
			'fishing'
		);
		
		$changed = array();
		foreach ($skills as $skill)
		{
			if ($this->isChanged($skill) && ($this->get($skill) > $this->getExisting($skill)))
			{
				$changed[$skill] = $this->get($skill);
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
	
	protected function _getUserModel()
	{
		return $this->getModelFromCache('XenForo_Model_User');
	}
	
	protected function _getNewsFeedModel()
	{
		return $this->getModelFromCache('XenForo_Model_NewsFeed');
	}

	protected function _getPirateModel()
	{
		return $this->getModelFromCache('PirateProfile_Model_Pirate');
	}
}
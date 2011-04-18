<?php

class PirateProfile_DataWriter_PirateComment extends XenForo_DataWriter
{
	const DATA_PIRATE_USER = 'pirateUser';
	const DATA_PIRATE = 'pirate';

	protected $_existingDataErrorPhrase = 'pirateProfile_requested_pirate_not_found';

	protected function _getFields()
	{
		return array(
			'pirate_comment' => array(
				'pirate_comment_id'   => array('type' => self::TYPE_UINT,   'autoIncrement' => true),
				'pirate_id'           => array('type' => self::TYPE_UINT,   'required' => true),
				'user_id'                => array('type' => self::TYPE_UINT,   'required' => true),
				'username'               => array('type' => self::TYPE_STRING, 'required' => true, 'maxLength' => 50,
						'requiredError' => 'please_enter_valid_name'
				),
				'comment_date'           => array('type' => self::TYPE_UINT,   'required' => true, 'default' => XenForo_Application::$time),
				'message'                => array('type' => self::TYPE_STRING, 'required' => true,
						'requiredError' => 'please_enter_valid_message'
				),
				'likes' => array(
					'type' => self::TYPE_UINT_FORCED,
					'default' => 0
				),
				'like_users' => array(
					'type' => self::TYPE_SERIALIZED,
					'default' => 'a:0:{}'
				)
			)
		);
	}

	protected function _getExistingData($data)
	{
		if (!$id = $this->_getExistingPrimaryKey($data))
		{
			return false;
		}

		return array('pirate_comment' => $this->_getPirateModel()->getPirateCommentById($id));
	}

	protected function _getUpdateCondition($tableName)
	{
		return 'pirate_comment_id = ' . $this->_db->quote($this->getExisting('pirate_comment_id'));
	}

	protected function _preSave()
	{
		if ($this->isChanged('message'))
		{
			$maxLength = 420;
			if (utf8_strlen($this->get('message')) > $maxLength)
			{
				$this->error(new XenForo_Phrase('please_enter_message_with_no_more_than_x_characters', array('count' => $maxLength)), 'message');
			}
		}
	}

	protected function _postSave()
	{
		 $pirateId = $this->get('pirate_id');

		if ($this->isInsert())
		{
			$dw = XenForo_DataWriter::create('PirateProfile_DataWriter_Pirate');
			$dw->setExistingData($pirateId);
			$dw->insertNewComment($this->get('pirate_comment_id'), $this->get('comment_date'));
			$dw->save();

			$pirateUser = $this->getExtraData(self::DATA_PIRATE_USER);
			if ($pirateUser && $pirateUser['user_id'] != $this->get('user_id'))
			{
				if (XenForo_Model_Alert::userReceivesAlert($pirateUser, 'pirate', 'comment_your_pirate'))
				{
					XenForo_Model_Alert::alert(
						$pirateUser['user_id'],
						$this->get('user_id'),
						$this->get('username'),
						'pirate',
						$pirateId,
						'comment_your_pirate'
					);
				}
			}

			$pirate = $this->getExtraData(self::DATA_PIRATE);

			$otherCommenterIds = $this->_getPirateModel()->getPirateCommentUserIds($pirateId);

			$otherCommenters = $this->_getUserModel()->getUsersByIds($otherCommenterIds, array(
				'join' => XenForo_Model_User::FETCH_USER_OPTION
			));

			$pirateUserId = empty($pirateUser) ? 0 : $pirateUser['user_id'];

			foreach ($otherCommenters AS $otherCommenter)
			{
				switch ($otherCommenter['user_id'])
				{
					case $pirateUserId:
					case $this->get('user_id'):
					case 0:
						break;

					default:
						if (XenForo_Model_Alert::userReceivesAlert($otherCommenter, 'pirate', 'comment_other_commenter'))
						{
							XenForo_Model_Alert::alert(
								$otherCommenter['user_id'],
								$this->get('user_id'),
								$this->get('username'),
								'pirate',
								$pirateId,
								'comment_other_commenter'
							);
						}
						break;
				}
			}
		}
	}

	protected function _postDelete()
	{
		$dw = XenForo_DataWriter::create('PirateProfile_DataWriter_Pirate');
		$dw->setExistingData($this->get('pirate_id'));
		$dw->rebuildPirateCommentCounters();
		$dw->save();
		
		if ($likes = $this->get('likes'))
		{
			$this->getModelFromCache('XenForo_Model_Like')->deleteContentLikes(
				'pirate_comment', $this->get('pirate_comment_id')
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
	}

	protected function _getPirateModel()
	{
		return $this->getModelFromCache('PirateProfile_Model_Pirate');
	}
	
	protected function _getUserModel()
	{
		return $this->getModelFromCache('XenForo_Model_User');
	}
}
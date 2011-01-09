<?php

class PirateProfile_Model_Pirate extends XenForo_Model
{
	public function getAllPirates($limit, $page)
	{
		$start = ($limit * ($page - 1));
		
		$pirates = $this->_getDb()->fetchAll("
			SELECT pirate_id, user_id, name
			FROM pirates
			ORDER BY name ASC
			LIMIT {$start}, {$limit}
		");
		
		if (empty($pirates)) return false;
		
		return $pirates;
	}
	
	public function getUserPirates($user_id)
	{
		$pirates = $this->_getDb()->fetchAll('
			SELECT pirate_id, user_id, name
			FROM pirates
			WHERE user_id = ?
		', $user_id);

		if (!isset($pirates)) return false;

		return $pirates;
	}

	public function getPirateById($id, array $fetchOptions = array())
	{
		$sqlClauses = $this->preparePirateFetchOptions($fetchOptions);
		
		$pirate = $this->_getDb()->fetchRow('
			SELECT *
					' . $sqlClauses['selectFields'] . '
			FROM pirates AS pirate
				' . $sqlClauses['joinTables'] . '
			WHERE pirate_id = ?
		', $id);

		if (!isset($pirate)) return false;

		$pirate = preg_replace("/^0$/is", null, $pirate);

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
		if (!$perms['attach']) return false;
		
		return true;
	}

	protected function _getAttachmentModel()
	{
		return $this->getModelFromCache('XenForo_Model_Attachment');
	}
	
	public function getPermissions(array $viewingUser = null)
	{
			$this->standardizeViewingUserReference($viewingUser);
			
			$permissions = $viewingUser['permissions'];
			
			$perms = array(
				'view'   => $this->_hasPermission($permissions, 'pirateProfile', 'canView'),
				'add'    => $this->_hasPermission($permissions, 'pirateProfile', 'canAdd'),
				'attach' => $this->_hasPermission($permissions, 'pirateProfile', 'canAttach'),
				'edit'   => $this->_hasPermission($permissions, 'pirateProfile', 'canEdit'),
				'delete' => $this->_hasPermission($permissions, 'pirateProfile', 'canDelete'),
				'manage' => $this->_hasPermission($permissions, 'pirateProfile', 'canManage')
			);

			return $perms;
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

		return array(
			'selectFields' => $selectFields,
			'joinTables'   => $joinTables
		);
	}
	
	protected function _hasPermission($permissions, $group, $permission)
	{
		return XenForo_Permission::hasPermission($permissions, $group, $permission);
	}
}
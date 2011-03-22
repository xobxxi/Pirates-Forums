<?php

class PirateProfile_NewsFeedHandler_Pirate extends XenForo_NewsFeedHandler_Abstract
{
	
	public function getContentByIds(array $contentIds, $model, array $viewingUser)
	{
		
		$pirates = array();
		
		$permissions = $model->getModelFromCache('PirateProfile_Model_Pirate')
		                     ->getPermissions($viewingUser);
		if (!$permissions['canView']) return $pirates;
		
		return $model->getModelFromCache('PirateProfile_Model_Pirate')->getPiratesByIds($contentIds);
	}
	
	protected function _prepareName(array $item)
	{
		$item['name'] = unserialize($item['extra_data']);
		unset($item['extra_data']);
		
		return $item;
	}
	
	protected function _prepareGuild(array $item)
	{
		$item['guild'] = unserialize($item['extra_data']);
		unset($item['extra_data']);
		
		return $item;
	}
	
	protected function _prepareExtra(array $item)
	{
		$data = unserialize($item['extra_data']);
		unset($item['extra_data']);
		
		$item['extra'] = $data['extra'];
		
		return $item;
	}
	
	protected function _prepareLevel(array $item)
	{	
		$skills = unserialize($item['extra_data']);

		foreach ($skills as $skill => $level)
		{
			if ($skill == 'level') $skill = 'notoriety';
			
			$skill = new XenForo_Phrase('pirateProfile_pirate_' . $skill);
			$item['skills'][$skill->__toString()] = $level;
		}

		unset($item['extra_data']);
		
		$keys = array_keys($item['skills']);
		$last = count($keys) - 1;
		$item['last'] = $keys[$last];
		
		if (count($item['skills']) === 1)
		{
			$item['none'] = true;
		}
		
		return $item;
	}
	
	protected function _prepareInfamy(array $item)
	{
		$item['extra_data'] = unserialize($item['extra_data']);
		
		$item['type'] = $item['extra_data']['type'];
		unset($item['extra_data']['type']);
		
		$item['rank'] = $item['extra_data'];
		
		unset($item['extra_data']);
		
		foreach ($item['rank'] as $key => $rank)
		{
			if (!empty($rank))
			{
				$name = new XenForo_Phrase(
					'pirateProfile_pirate_rank_' . $item['type'] . '_' . $rank
				);
			
				$item['rank'][$key] = $name->__toString();
			}
		}
		
		$type = new XenForo_Phrase('pirateProfile_pirate_' . $item['type']);
		$item['type'] = $type->__toString();
		
		return $item;
	}
	
	protected function _prepareLike(array $item)
	{
		$item['owner'] =
			XenForo_Model::create('XenForo_Model_User')
				->getUserById($item['content']['user_id']);
		
		return $item;
	}
}
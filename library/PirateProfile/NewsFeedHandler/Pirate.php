<?php

class PirateProfile_NewsFeedHandler_Pirate extends XenForo_NewsFeedHandler_Abstract
{
	
	public function getContentByIds(array $contentIds, $model, array $viewingUser)
	{
		
		$pirates = array();
		foreach ($contentIds as $contentId)
		{
			if (!isset($pirates[$contentId]))
			{
				$pirates[$contentId] = $model->getModelFromCache('PirateProfile_Model_Pirate')
				                             ->getPirateById($contentId);
			}
		}

		return $pirates;
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
}
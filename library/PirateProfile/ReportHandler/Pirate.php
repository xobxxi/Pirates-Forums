<?php

class PirateProfile_ReportHandler_Pirate extends XenForo_ReportHandler_Abstract
{

	public function getReportDetailsFromContent(array $content)
	{
		$pirateModel = XenForo_Model::create('PirateProfile_Model_Pirate');

		$pirate = $pirateModel->getPirateById($content['pirate_id']);
		if (!$pirate)
		{
			return array(false, false, false);
		}
		
		if (!empty($pirate['name']))
		{
			$pirate['name']	 = XenForo_Helper_String::censorString($pirate['name']);
		}

		if (!empty($pirate['guild']))
		{
			$pirate['guild'] = XenForo_Helper_String::censorString($pirate['guild']);
		}

		if (!empty($pirate['extra']))
		{
			$pirate['extra'] = XenForo_Helper_String::censorString($pirate['extra']);
		}
		
		$pirate = $pirateModel->preparePirate($pirate);

		return array(
			$content['pirate_id'],
			$content['user_id'],
			array(
				'pirate' => $pirate
			)
		);
	}

	public function getVisibleReportsForUser(array $reports, array $viewingUser)
	{
		$pirateModel = XenForo_Model::create('PirateProfile_Model_Pirate');
		
		$permissions = $pirateModel->getPermissions($viewingUser);
		if ($permissions['manage'])
		{
			return $reports;
		}
		
		return array();
	}

	public function getContentTitle(array $report, array $contentInfo)
	{
		return new XenForo_Phrase('pirateProfile_pirate_x', array('pirate' => $contentInfo['pirate']['name']));
	}

	public function getContentLink(array $report, array $contentInfo)
	{
		return XenForo_Link::buildPublicLink('pirates/card', $contentInfo['pirate']);
	}

	public function viewCallback(XenForo_View $view, array &$report, array &$contentInfo)
	{
		return $view->createTemplateObject('pirateProfile_pirate_card', array(
			'report' => $report,
			'pirate' => $contentInfo['pirate']
		));
	}
}
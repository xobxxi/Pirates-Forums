<?php
/**
 * Performs the installation actions for the add-on.
 *
 * Install methods are designated in the format _installVersionX, where X is the
 * version ID of which that install code applies.
 *
 * @package ReportConversations
 */

class ReportConversations_Install
{
	/**
	 * Instance manager.
	 *
	 * @var ReportConversations_Install
	 */
	private static $_instance;

	/**
	 * Database object
	 *
	 * @var Zend_Db_Adapter_Abstract
	 */
	protected $_db;

	/**
	 * Gets the installer instance.
	 *
	 * @return ReportConversations_Install
	 */
	public static final function getInstance()
	{
		if (!self::$_instance)
		{
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Helper method to get the database object.
	 *
	 * @return Zend_Db_Adapter_Abstract
	 */
	protected function _getDb()
	{
		if ($this->_db === null)
		{
			$this->_db = XenForo_Application::get('db');
		}

		return $this->_db;
	}

	/**
	 * Begins the installation process and picks the proper install routine.
	 *
	 * See see XenForo_Model_Addon::installAddOnXml() for more details about
	 * the arguments passed to this method.
	 *
	 * @param array Information about the existing version (if upgrading)
	 * @param array Information about the current version being installed
	 *
	 * @return void
	 */
	public static function install($existingAddOn, $addOnData)
	{
		// the version IDs from which we should start/end the install process
		$startVersionId = 1;
		$endVersionId = $addOnData['version_id'];

		if ($existingAddOn)
		{
			// we are upgrading, run every install method since last upgrade
			$startVersionId = $existingAddOn['version_id'] + 1;
		}

		// create our install object
		$install = self::getInstance();

		for ($i = $startVersionId; $i <= $endVersionId; ++$i)
		{
			$method = '_installVersion' . $i;
			if (method_exists($install, $method) === false)
			{
				continue;
			}

			$install->$method();
		}
	}

	/**
	 * Install routine for version ID 1 (first version!).
	 *
	 * @return void
	 */
	protected function _installVersion1()
	{
		$db = $this->_getDb();

		$queries = ReportConversations_Install_Data_MySql::getQueries(1);
		foreach ($queries AS $query)
		{
			$db->query($query);
		}

		// rebuild caches
		XenForo_Model::create('XenForo_Model_ContentType')->rebuildContentTypeCache();
	}
}

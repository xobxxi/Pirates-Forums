<?php
/**
 * MySQL Schema for Report Conversations installation.
 *
 * @package ReportConversations
 */

class ReportConversations_Install_Data_MySql
{
	/**
	 * Fetches the appropriate queries. This method can take a variable number
	 * of arguments, which will be passed on to the specific method.
	 *
	 * @param integer Version ID of queries to fetch
	 *
	 * @return array List of queries to run
	 * @return array Empty array if method does not exist
	 */
	public static function getQueries($version)
	{
		$method = '_getQueriesVersion' . (int)$version;
		if (method_exists(__CLASS__, $method) === false)
		{
			return array();
		}

		$args = func_get_args();
		$args = array_slice($args, 1);

		if (!is_array($args))
		{
			$args = array();
		}

		return call_user_func_array(array(__CLASS__, $method), $args);
	}

	/**
	 * Schema definitions for version 1.
	 *
	 * @return array List of queries to run
	 */
	protected static function _getQueriesVersion1()
	{
		$queries = array();

$queries[] = "
	INSERT INTO xf_content_type
		(content_type, addon_id, fields)
	VALUES
		('conversation_message', 'ReportConversations', '')
";

$queries[] = "
	INSERT INTO xf_content_type_field
		(content_type, field_name, field_value)
	VALUES
		('conversation_message', 'report_handler_class', 'ReportConversations_ReportHandler_ConversationMessage')
";

		return $queries;
	}
}

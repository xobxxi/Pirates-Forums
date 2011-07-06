<?php

class PiratesForums_Helper_Thread
{
	public static function create($forumId, $user, $title, $message)
	{
		$writer = XenForo_DataWriter::create('XenForo_DataWriter_Discussion_Thread');
		$writer->set('user_id', $user['user_id']);
		$writer->set('username', $user['username']);
		$writer->set('title', $title);
		
		$postWriter = $writer->getFirstMessageDw();
		$postWriter->set('message', $message);
		
		$writer->set('node_id', $forumId);
		$writer->preSave();
		$writer->save();
		
		return $writer->getMergedData();
	}
}
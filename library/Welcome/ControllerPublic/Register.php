<?php

class Welcome_ControllerPublic_Register extends XFCP_Welcome_ControllerPublic_Register
{
	public function actionRegister()
	{
		$response = parent::actionRegister();

		if (isset($response->params['user']['username']))
		{
			$this->_fireConversation($response->params['user']['username']);
		}

		return $response;
	}

	protected function _fireConversation($recipient)
	{
		$options = XenForo_Application::get('options');

		$title   = $options->welcomeUsers_title;
		$senders = $options->welcomeUsers_senders;

		$senders = explode(',', $options->welcomeUsers_senders);
		/*$messages   = explode(',', $options->welcomeUsers_messages);*/

		$davy      =
				"Ahoy,

				Welcome to the forums, we are glad you could join us. We are a friendly community of pirates of all guilds and backgrounds.

				Before we get going, it's important you go check your email so you may verify your account and begin posting.

				Once you've done that, we invite you to introduce yourself here: http://piratesforums.com/forums/new-members.2/

				We are committed to providing an enjoyable experience without bias for all members of the Pirates gaming community alike.

				You may upload a picture to identify yourself or your pirate here: http://piratesforums.com/account/avatar
				and change your personal and account details here: http://piratesforums.com/account/personal-details";

		$star =
                "To help us identify you better in-game, please add your pirates to your profile here: http://piratesforums.com/pirates
                
				Everyone has a story to tell and we look forward to hearing yours!

				If you want to tell us a bit about your life as a pirate and experiences in the game, please read this: http://piratesforums.com/threads/key-points-to-talk-about-and-share.8/

				We look forward to reading your posts!
				Just reply if you have any questions, comments, or problems.

				Thank you!";

	    $messages = array($davy, $star);

		$conversation['recipients'] = array_merge($senders, array($recipient));


		$ids = array();
		$i = 0;
		foreach ($messages as $message)
		{
			if ($i == 0)
			{
				$inviteUser = $this->_getUserByName($senders[$i]);
				$inviteUser['permissions']['conversation']['start'] = true;
				$inviteUser['permissions']['conversation']['maxRecipients'] = count($senders) + 1;

				$conversation['title']               = $title;
				$conversation['open_invite']         = 0;
				$conversation['conversation_locked'] = 0;

				$conversation['from']['name']        = $inviteUser['username'];
				$conversation['from']['id']          = $inviteUser['user_id'];
				$conversation['message']             = $message;

				$ids[] = $inviteUser['user_id'];

				$conversationData = $this->_startConversation($conversation, $inviteUser);
				$conversation['conversation_id'] = $conversationData['conversation_id'];
			}
			else
			{
				if (!$senders[$i])
				{
					$senders[$i] = end($senders);
				}

				$replyUser = $this->_getUserByName($senders[$i]);

				$conversation['from']['name'] = $replyUser['username'];
				$conversation['from']['id']   = $replyUser['user_id'];
				$conversation['message_date'] = $conversationData['last_message_date'] + $i;
				$conversation['message']      = $message;

				$ids[] = $replyUser['user_id'];

				$this->_replyConversation($conversation, $replyUser);
			}

			$i++;
		}

		$this->_hideConversationFromUsers($conversation['conversation_id'], $ids);
	}

	protected function _startConversation($conversation, $inviteUser)
	{
		$conversationDw = XenForo_DataWriter::create('XenForo_DataWriter_ConversationMaster');
		$conversationDw->setExtraData(XenForo_DataWriter_ConversationMaster::DATA_ACTION_USER, $inviteUser);
		$conversationDw->set('user_id', $conversation['from']['id']);
		$conversationDw->set('username', $conversation['from']['name']);
		$conversationDw->set('title', $conversation['title']);
		$conversationDw->set('open_invite', $conversation['open_invite']);
		$conversationDw->set('conversation_open', $conversation['conversation_locked'] ? 0 : 1);
		$conversationDw->addRecipientUserNames($conversation['recipients']);

		$messageDw = $conversationDw->getFirstMessageDw();
		$conversation['message'] = XenForo_Helper_String::autoLinkBbCode($conversation['message']);
		$messageDw->set('message', $conversation['message']);

		$conversationDw->save();
		$conversationData = $conversationDw->getMergedData();

		return $conversationData;
	}

	protected function _replyConversation($conversation, $replyUser)
	{
		$conversation['message'] = XenForo_Helper_String::autoLinkBbCode($conversation['message']);

		$messageDw = XenForo_DataWriter::create('XenForo_DataWriter_ConversationMessage');
		$messageDw->setExtraData(XenForo_DataWriter_ConversationMessage::DATA_MESSAGE_SENDER, $replyUser);
		$messageDw->set('conversation_id', $conversation['conversation_id']);
		$messageDw->set('user_id', $conversation['from']['id']);
		$messageDw->set('username', $conversation['from']['name']);
		$messageDw->set('message_date', $conversation['message_date']);
		$messageDw->set('message', $conversation['message']);
		$messageDw->save();

		return true;
	}

	protected function _getUserByName($name, $returnId = false)
	{
		$user  = $this->_getUserModel()->getUserByName($name);

		if (!$returnId)
		{
			return $user;
		}

		return $this->_getUserModel()->getUserIdFromUser($user);
	}

	protected function _hideConversationFromUsers($id, array $users)
	{
		foreach ($users as $uid)
		{
			$this->_getConversationModel()->deleteConversationForUser(
				$id, $uid, 'delete'
			);
	    }

		return true;
	}

	protected function _getUserModel()
	{
		return $this->getModelFromCache('XenForo_Model_User');
	}

	protected function _getConversationModel()
	{
		return $this->getModelFromCache('XenForo_Model_Conversation');
	}
}
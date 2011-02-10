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
		$options    = XenForo_Application::get('options');
		//clean spaces
		$clean_senders = preg_replace("/\s+(.+)\s?$/s","$1",$options->welcomeUsers_senders);
		$senders    = explode(',', $clean_senders);
		$recipients = array($recipient);
		$title      = $options->welcomeUsers_title;
		/*$messages   = explode(',', $options->welcomeUsers_messages);*/

		$start_message = <<<CONVERSATION_MASTER
		Ahoy,

		Welcome to the forums, we are glad you could join us. We are a friendly community of pirates of all guilds and backgrounds.

		Before we get going, it's important you go check your email so you may verify your account and begin posting.

		Once you've done that, we invite you to introduce yourself here: http://piratesforums.com/forums/new-members.2/

		We are committed to providing an enjoyable experience without bias for all members of the Pirates gaming community alike.

		You may upload a picture to identify yourself or your pirate here: http://piratesforums.com/account/avatar
		and change your personal and account details here: http://piratesforums.com/account/personal-details

		To help us identify you better in-game, please add your pirates to your profile here: http://piratesforums.com/pirates
CONVERSATION_MASTER;

		$messages[] =
				"Everyone has a story to tell and we look forward to hearing yours!

				If you want to tell us a bit about your life as a pirate and experiences in the game, please read this: http://piratesforums.com/threads/key-points-to-talk-about-and-share.8/

				Our site is run entirely by volunteers, so we appreciate all the help we can get. If you think you'd like to give us a hand, see what positions are available here: http://piratesforums.com/threads/new-positions-open-as-of-1-20-2011.1185/

				We look forward to reading your posts!
				Just reply if you have any questions, comments, or problems.

				Thank you!";

		//If you add more users you change these messages:
		//the order is the same as they are listed.

		//change this if you plan to add more users!!..
		$messages[] = "Some random message";

		//change this if you plan to add more users!!..
		$messages[] = "yo-ho-ho!";


		$messages[] = "yo-ho-ho! Mate";


		$convo = array();

		$convo['recipients'] = array_merge(array_merge(array($options->welcome_convo_starter),$senders), $recipients);

		$inviteUser = $this->_getUserByName($options->welcome_convo_starter);
		$inviteUser['permissions']['conversation']['start'] = true;
		$inviteUser['permissions']['conversation']['maxRecipients'] = count(array_keys($convo['recipients'])) + 1;

		$convo['title']               = $title;
		$convo['username']       	  = $options->welcome_convo_starter;
		$convo['user_id'] 			  = $inviteUser['user_id'];
		$convo['message']             = $start_message;

		$conversationData = $this->_startConversation($convo, $inviteUser);
		$convo['conversation_id'] = $conversationData['conversation_id'];

		$new_message= array();

		do {
			$new_message[trim(current($senders))] = current($messages);
		} while(next($messages) && next($senders));


		$user_model = $this->getModelFromCache('Xenforo_Model_User');

		$master_user = $user_model->getUserByName($options->welcome_convo_starter);

		$add_ids_to_convo[] = $master_user['user_id'];
		$i=0;
		foreach ($new_message as $sender_name => $message) {
				$i++;
				$sender = $user_model->getUserByName($sender_name);

				$convo['username'] = $sender_name;
				$convo['user_id']   = $sender['user_id'];
				$convo['message_date'] = $conversationData['last_message_date']+$i;
				$convo['message']      = $message;

				$add_ids_to_convo[] = $sender['user_id'];
				$this->_replyConversation($convo);
		}
		$this->_hideConversationFromUsers($convo['conversation_id'], $add_ids_to_convo);

		return true;
	}


	protected function _startConversation($conversation, $inviteUser)
	{
		$conversationDw = XenForo_DataWriter::create('XenForo_DataWriter_ConversationMaster');
		$conversationDw->setExtraData('inviteUser', $inviteUser);
		$conversationDw->set('user_id', $conversation['user_id']);
		$conversationDw->set('username', 	$conversation['username']);
		$conversationDw->set('title', $conversation['title']);
		$conversationDw->set('open_invite', false);
		$conversationDw->set('conversation_open', true);
		$conversationDw->addRecipientUserNames($conversation['recipients']);

		$messageDw = $conversationDw->getFirstMessageDw();
		$conversation['message'] = XenForo_Helper_String::autoLinkBbCode($conversation['message']);
		$messageDw->set('message', $conversation['message']);

		$conversationDw->save();
		$conversationData = $conversationDw->getMergedData();

		return $conversationData;
	}

	protected function _replyConversation($conversation)
	{
		$conversation['message'] = XenForo_Helper_String::autoLinkBbCode($conversation['message']);

		$messageDw = XenForo_DataWriter::create('XenForo_DataWriter_ConversationMessage');
		$messageDw->set('conversation_id', $conversation['conversation_id']);
		$messageDw->set('user_id', $conversation['user_id']);
		$messageDw->set('username', $conversation['username']);
		$messageDw->set('message_date', $conversation['message_date']);
		$messageDw->set('message', $conversation['message']);
		$messageDw->save();

		return true;
	}

	protected function _getUserByName($name, $returnId = false)
	{
		$user  = $this->_getUserModel()->getUserByName($name);

		if (!$returnId) return $user;
		return $this->_getUserModel()->getUserIdFromUser($user);
	}

	protected function _hideConversationFromUsers($id, array $users)
	{
		foreach ($users as $uid) {
			$this->_getConversationModel()->deleteConversationForUser(
				$id, $uid, 'delete'
			);
	    }

		return true;
	}

	/**
	 * @return XenForo_Model_User
	 */
	protected function _getUserModel()
	{
		return $this->getModelFromCache('XenForo_Model_User');
	}

	/**
	 * @return XenForo_Model_Conversation
	 */
	protected function _getConversationModel()
	{
		return $this->getModelFromCache('XenForo_Model_Conversation');
	}
}
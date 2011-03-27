Conversation Attachments
========================

Installation
------------

1. Upload this folder to the /library directory of your XenForo installation.

2. Go to your Admin CP > Install Add-on and under 'Install from file on server' enter:
'library/ConversationAttachments/addon-conversationAttachments.xml'.

3. Apply the template edits that follow.

4. Set permissions for 'View attachments' and 'Add attachments' for groups under 'Personal Conversation Permissions'.

### Template Edits

#### In conversation_add: 

_Find_:

	<input type="submit" value="{xen:phrase start_conversation}" accesskey="s" class="button primary" />

_Add **below**_:

	<xen:include template="attachment_upload_button" />

_Find_:

	<input type="hidden" name="_xfToken" value="{$visitor.csrf_token_page}" />

_Add **above**_:

	<xen:if is="{$attachmentParams}">
	 		<dl class="ctrlUnit AttachedFilesUnit">
				<dt><label for="ctrl_uploader">{xen:phrase attached_files}:</label></dt>
				<dd><xen:include template="attachment_editor" /></dd>
			</dl>
	</xen:if>

#### In conversation_reply:

_Find_:

	<input type="submit" value="{xen:phrase reply_to_conversation}" accesskey="s" class="button primary" />

_Add **below**_:

	<xen:include template="attachment_upload_button" />

*Find*:

	<input type="hidden" name="_xfToken" value="{$visitor.csrf_token_page}" />

_Add **above**_:

	<xen:if is="{$attachmentParams}">
	 		<dl class="ctrlUnit AttachedFilesUnit">
				<dt><label for="ctrl_uploader">{xen:phrase attached_files}:</label></dt>
				<dd><xen:include template="attachment_editor" /></dd>
			</dl>
	</xen:if>

#### In conversation\_message\_edit:

_Find_:

	<input type="submit" value="{xen:phrase save_changes}" accesskey="s" class="button primary" />

_Add **below**_:

	<xen:include template="attachment_upload_button" />

_Find_:

	<input type="hidden" name="_xfToken" value="{$visitor.csrf_token_page}" />

_Add **above**_:

	<xen:if is="{$attachmentParams}">
			<dl class="ctrlUnit AttachedFilesUnit">
				<dt><label for="ctrl_uploader">{xen:phrase attached_files}:</label></dt>
				<dd><xen:include template="attachment_editor"><xen:map from="$conversationMessage.attachments" to="$attachments" /></xen:include></dd>
			</dl>
	</xen:if>

#### In conversation_message:

_Add at top_:

	<xen:require css="conversationAttachments_conversation_message.css" />

_Find_:

	<xen:set var="$messageId">message-{$message.message_id}</xen:set>

_Add **after**_:

	<xen:set var="$messageContentAfterTemplate"><xen:if is="{$message.attachments}"><xen:include template="attached_files"><xen:map from="$message" to="$post" /></xen:include></xen:if></xen:set>


Uninstallation
--------------

_Please note all attachments will be unassociated when uninstalling, and will subsequently be deleted._

1. Revert all your template edits

2. Go to your ACP > Manage Add-ons > Conversation Attachments > Controls > Uninstall

3. Once the process is completed, remove the ConversationAttachments folder from the /library directory of your XenForo installation.
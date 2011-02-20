<xen:edithint template="navigation.css" />

<ul class="visitorTabs">

	<!-- account -->
	<li class="navTab account Popup PopupControl PopupClosed {xen:if $tabs.account.selected, 'selected'}">

		<a href="{xen:link account}" class="navLink accountPopup" rel="Menu"><strong>{$visitor.username}</strong></a>

		<div class="Menu JsOnly" id="AccountMenu">
			<div class="primaryContent menuHeader">
				<xen:avatar user="$visitor" size="m" class="NoOverlay plainImage" title="{xen:phrase view_your_profile}" />

				<h3><a href="{xen:link members, $visitor}" class="concealed" title="{xen:phrase view_your_profile}">{$visitor.username}</a></h3>

				<xen:if hascontent="true"><div class="muted"><xen:contentcheck>{xen:helper usertitle, $visitor}</xen:contentcheck></div></xen:if>

				<ul class="links">
					<li class="fl"><a href="{xen:link members, $visitor}">{xen:phrase your_profile_page}</a></li>
					<!--<li class="fr"><a href="{xen:link logout, '', '_xfToken={$visitor.csrf_token_page}'}" class="LogOut">{xen:phrase log_out}</a></li>-->
				</ul>
			</div>
			<div class="menuColumns secondaryContent">
				<ul class="col1 blockLinksList">
					<li><a href="{xen:link account/personal-details}">{xen:phrase personal_details}</a></li>
					<li><a href="{xen:link account/contact-details}">{xen:phrase contact_details}</a></li>
					<li><a href="{xen:link account/preferences}">{xen:phrase preferences}</a></li>
					<li><a href="{xen:link account/alert-preferences}">{xen:phrase alert_preferences}</a></li>
					<li><a href="{xen:link account/privacy}">{xen:phrase privacy}</a></li>
					<xen:if is="{$canEditSignature}"><li><a href="{xen:link account/signature}">{xen:phrase signature}</a></li></xen:if>
					<xen:if is="{$canUploadAvatar}"><li><a href="{xen:link account/avatar}" class="OverlayTrigger" data-cacheOverlay="true">{xen:phrase avatar}</a></li></xen:if>
				</ul>
				<ul class="col2 blockLinksList">
					<li><a href="{xen:link account/news-feed}">{xen:phrase your_news_feed}</a></li>
					<li><a href="{xen:link account/alerts}">{xen:phrase your_alerts}</a></li>
					<li><a href="{xen:link watched/threads}">{xen:phrase watched_threads}</a></li>
					<li><a href="{xen:link account/likes}">{xen:phrase likes_received}</a></li>
					<li><a href="{xen:link search/member, '', 'user_id={$visitor.user_id}'}">{xen:phrase your_content}</a></li>
					<li><a href="{xen:link account/following}">{xen:phrase people_you_follow}</a></li>
					<xen:if is="{$xenCache.userUpgradeCount}"><li><a href="{xen:link account/upgrades}">{xen:phrase account_upgrades}</a></li></xen:if>
					<xen:if is="{$xenOptions.facebookAppId}"><li><a href="{xen:link account/facebook}">{xen:phrase facebook_integration}</a></li></xen:if>
				</ul>
			</div>
			<div class="menuColumns secondaryContent">
				<ul class="col1 blockLinksList">
					<li>
						<form action="{xen:link account/toggle-visibility}" method="post" class="AutoValidator visibilityForm">
							<label><input type="checkbox" name="visible" value="1" class="SubmitOnChange" {xen:checked $visitor.visible} />
								{xen:phrase show_online_status}</label>
							<input type="hidden" name="_xfToken" value="{$visitor.csrf_token_page}" />
						</form>
					</li>
				</ul>
				<ul class="col2 blockLinksList">
					<li><a href="{xen:link logout, '', '_xfToken={$visitor.csrf_token_page}'}" class="LogOut">{xen:phrase log_out}</a></li>
				</ul>
			</div>
			<xen:if is="{$canUpdateStatus}">
				<form action="{xen:link members/post, $visitor}" method="post" class="sectionFooter statusPoster AutoValidator" data-optInOut="OptIn">
					<textarea name="message" class="textCtrl StatusEditor Elastic" placeholder="{xen:phrase update_your_status}..." rows="1" cols="40" style="height:14px" data-statusEditorCounter="#visMenuSEdCount"></textarea>
					<div class="submitUnit">
						<span id="visMenuSEdCount" title="{xen:phrase characters_remaining}"></span>
						<input type="submit" class="button primary MenuCloser" value="{xen:phrase post}" accesskey="s" />
						<input type="hidden" name="_xfToken" value="{$visitor.csrf_token_page}" />
						<input type="hidden" name="return" value="1" />
					</div>
				</form>
			</xen:if>
		</div>
	</li>

	<xen:if is="{$tabs.account.selected}">
	<li class="navTab selected PopupClosed">
		<div class="tabLinks">
			<div class="primaryContent menuHeader">
				<h3>{$tabs.account.title}</h3>
				<div class="muted">{xen:phrase quick_links}</div>
			</div>
			<ul class="secondaryContent blockLinksList">
				<li><a href="{xen:link account/personal-details}">{xen:phrase personal_details}</a></li>
				<li><a href="{xen:link conversations}">{xen:phrase conversations}</a></li>
				<li><a href="{xen:link account/news-feed}">{xen:phrase your_news_feed}</a></li>
				<li><a href="{xen:link account/likes}">{xen:phrase likes_received}</a></li>
				<!--<li><a href="{xen:link 'watched/threads'}">{xen:phrase watched_threads}</a></li>-->
			</ul>
		</div>
	</li>
	</xen:if>

	<!-- conversations popup -->
	<li class="navTab inbox Popup PopupControl PopupClosed">

		<a href="{xen:link conversations}" rel="Menu" class="navLink NoPopupGadget">{xen:phrase inbox}
			<xen:if is="{$visitor.conversations_unread}">
				<strong class="itemCount" id="ConversationsMenu_Counter">{xen:number $visitor.conversations_unread}
					<span class="arrow"></span>
				</strong>
			<xen:else />
				<strong class="itemCount" id="ConversationsMenu_Counter" style="display:none">
				</strong>
			</xen:if>
		</a>

		<div class="Menu JsOnly navPopup" id="ConversationsMenu"
			data-contentSrc="{xen:link 'conversations/popup'}"
			data-contentDest="#ConversationsMenu .listPlaceholder">

			<div class="menuHeader primaryContent">
				<h3>
					<span class="Progress InProgress"></span>
					<a href="{xen:link conversations}" class="concealed">{xen:phrase conversations}</a>
				</h3>
			</div>

			<div class="listPlaceholder"></div>

			<div class="sectionFooter">
				<a href="{xen:link conversations/add}" class="floatLink">{xen:phrase start_new_conversation}</a>
				<a href="{xen:link conversations}">{xen:phrase show_all}...</a>
			</div>
		</div>
	</li>

	<!-- alerts popup -->
	<li class="navTab alerts Popup PopupControl PopupClosed">

		<a href="{xen:link account/alerts}" rel="Menu" class="navLink NoPopupGadget">{xen:phrase alerts}
			<xen:if is="{$visitor.alerts_unread}">
				<strong class="itemCount" id="AlertsMenu_Counter">{xen:number $visitor.alerts_unread}
					<span class="arrow"></span>
				</strong>
			<xen:else />
				<strong class="itemCount" id="AlertsMenu_Counter" style="display:none">
				</strong>
			</xen:if>
		</a>

		<div class="Menu JsOnly navPopup" id="AlertsMenu"
			data-contentSrc="{xen:link 'account/alerts-popup'}"
			data-contentDest="#AlertsMenu .listPlaceholder"
			data-removeCounter="#AlertsMenu_Counter">

			<div class="menuHeader primaryContent">
				<h3>
					<span class="Progress InProgress"></span>
					<a href="{xen:link account/alerts}" class="concealed">{xen:phrase alerts}</a>
				</h3>
			</div>

			<div class="listPlaceholder"></div>

			<div class="sectionFooter">
				<a href="{xen:link account/alert-preferences}" class="floatLink">{xen:phrase alert_preferences}</a>
				<a href="{xen:link account/alerts}">{xen:phrase show_all}...</a>
			</div>
		</div>
	</li>

	<li class="navTab PopupClosed"><a href="{xen:link logout, '', '_xfToken={$visitor.csrf_token_page}'}" class="LogOut navLink visitorTabItem">{xen:phrase log_out}</a></li>
</ul>
/** @param {jQuery} $ jQuery Object */
!function($, window, document, _undefined)
{
	XenForo.AlbumPhotoLoader = function(href) { this.__construct(href); };
	XenForo.AlbumPhotoLoader.prototype =
	{
		__construct: function(href)
		{	
			XenForo.ajax(
				href,
				{},
				function (ajaxData, textStatus)
				{
					if (ajaxData.templateHtml)
					{
						new XenForo.ExtLoader(ajaxData, function()
						{
							if ($(ajaxData.templateHtml).hasClass('albumPhoto'))
							{
								$(ajaxData.templateHtml).xfInsert('replaceAll', '.albumPhoto', 'xfShow', 0, function()
								{
									this.success = true;
								});
							}
							else
							{
								this.success = false;
							}
						})
					}
				}
			);
		},
		
		checkStatus: function()
		{
			return this.success;
		}
	};
	
	XenForo.AlbumNavigation = function($link)
	{
		$link.click(function(e) {
			e.preventDefault();

			new XenForo.AlbumPhotoLoader($link.attr('href'));
		});
	}

	XenForo.AlbumPhotoDescriptionEditor = function($element) { this.__construct($element); };
	XenForo.AlbumPhotoDescriptionEditor.prototype =
	{
		__construct: function($button)
		{
			this.$descriptionContainer = $('.photoDescriptionContainer');
			this.$description		   = $('.photoDescription');
			this.$descriptionForm	   = $('.photoDescriptionForm');

			if ($button.attr('href'))
			{
				this.submitUrl = $button.attr('href');
			}
			else
			{
				this.submitUrl = this.$descriptionForm.attr('action');
			}

			$button.click($.context(this, 'click'));

			this.$descriptionForm.find('input:submit, button').click($.context(this, 'submit'));
		},

		click: function(e)
		{
			e.preventDefault();

			this.$description.xfHide(0);
			this.$descriptionForm.xfShow(0);
			this.$descriptionForm.find('input[name="description"]').focus();
		},

		submit: function(e)
		{
			e.preventDefault();

			var $form = this.$descriptionForm;
			if ($form.length)
			{
				if (!$form.data('MultiSubmitDisable'))
				{
					XenForo.MultiSubmitFix($form);
				}
				$form.data('MultiSubmitDisable')();
			}

			XenForo.ajax(
				this.submitUrl,
				{ description: $form.find('input[name="description"]').val() },
				$.context(this, 'submitSuccess')
			);
		},

		submitSuccess: function(ajaxData)
		{
			var $form = this.$descriptionForm;
			if ($form.data('MultiSubmitEnable'))
			{
				$form.data('MultiSubmitEnable')();
			}

			if (XenForo.hasResponseError(ajaxData))
			{
				return false;
			}

			if (ajaxData.photoDescription)
			{
				$(ajaxData.photoDescription).xfInsert('replaceAll', this.$descriptionContainer, 'xfShow', 0);
			}
		}
	};
	
	XenForo.AlbumDescriptionCounter = function($input) { this.__construct($input); };
	XenForo.AlbumDescriptionCounter.prototype =
	{
		__construct: function($input)
		{
			this.$input = $input
				.keyup($.context(this, 'update'));

			this.$counter = $('<span />').insertAfter(this.$input);

			this.$counter
				.addClass('extraCounter')
				.text('0');

			this.charLimit = 250; // max characters
			this.charCount = 0; // number of chars currently in use

			this.update();
		},

		update: function(e)
		{
			var extraText = this.$input.val();

			if (this.$input.attr('placeholder') && this.$input.attr('placeholder') == extraText)
			{
				this.setCounterValue(this.charLimit, extraText.length);
			}
			else
			{
				this.setCounterValue(this.charLimit - extraText.length, extraText.length);
			}
		},

		setCounterValue: function(remaining, length)
		{
			if (remaining < 0)
			{
				this.$counter.addClass('error');
				this.$counter.removeClass('warning');
			}
			else if (remaining <= this.charLimit - 150)
			{
				this.$counter.removeClass('error');
				this.$counter.addClass('warning');
			}
			else
			{
				this.$counter.removeClass('error');
				this.$counter.removeClass('warning');
			}

			this.$counter.text(remaining);
			this.charCount = length || this.$input.val().length;
		}
	};
	
	XenForo.AlbumSetCover = function($form) { this.__construct($form) };
	XenForo.AlbumSetCover.prototype =
	{
		__construct: function($form)
		{
			this.$form = $form;
			this.formAction = $form.attr('action');
			this.$checkbox = $form.children().children('input:checkbox');
			
			this.$checkbox.click($.context(this, 'submit'));
		},
		
		submit: function(e)
		{
			e.preventDefault();
			
			XenForo.ajax(this.formAction,
				{ _xfConfirm: this.$checkbox.val() },
				$.context(this, 'submitSuccess')
			);
		},
		
		submitSuccess: function(ajaxData)
		{
			if (XenForo.hasResponseError(ajaxData))
			{
				return false;
			}

			if (ajaxData.photoSetCover)
			{
				$(ajaxData.photoSetCover).xfInsert('replaceAll', this.$form, 'xfShow', 0);
			}
			
			XenForo.alert(ajaxData.alertMessage, 'info', 1500);
		}
	};


	// *********************************************************************
	XenForo.register('a.AlbumNavigation', 'XenForo.AlbumNavigation');
	XenForo.register('.AlbumPhotoDescriptionEditor', 'XenForo.AlbumPhotoDescriptionEditor');
	XenForo.register('.AlbumDescriptionCounter', 'XenForo.AlbumDescriptionCounter');
	XenForo.register('.AlbumSetCover', 'XenForo.AlbumSetCover');

}
(jQuery, this, document);
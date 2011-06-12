/** @param {jQuery} $ jQuery Object */
!function($, window, document, _undefined)
{
	XenForo.AlbumPhotoLoader = function($link)
	{
		$link.click(function(e) {
			e.preventDefault();

			XenForo.ajax(
				$link.attr('href'),
				{},
				function (ajaxData, textStatus)
				{
					if (ajaxData.templateHtml)
					{
						new XenForo.ExtLoader(ajaxData, function()
						{
							$(ajaxData.templateHtml).xfInsert('replaceAll', '.albumPhoto', 'xfShow', 0);
						})
					}
				}
			);
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


	// *********************************************************************
	XenForo.register('a.AlbumPhotoLoader', 'XenForo.AlbumPhotoLoader');
	XenForo.register('.AlbumPhotoDescriptionEditor', 'XenForo.AlbumPhotoDescriptionEditor');

}
(jQuery, this, document);
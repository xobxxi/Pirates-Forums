/** @param {jQuery} $ jQuery Object */
!function($, window, document, _undefined)
{
	XenForo.ResetButton = function($input)
	{
		if ($input.parent().parent().parent().parent().hasClass('xenOverlay'))
		{
			$input.show();
		}
	}
	
	XenForo.ExtraCounter = function($input) { this.__construct($input); };
	XenForo.ExtraCounter.prototype =
	{
		__construct: function($input)
		{
			this.$input = $input
				.keyup($.context(this, 'update'));

			this.$counter = $('<span />').insertAfter(this.$input);

			this.$counter
				.addClass('extraCounter')
				.text('0');

			this.charLimit = 32; // max characters
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
			else if (remaining <= this.charLimit - 22)
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
		},
	};
	
	// *********************************************************************

	XenForo.register('.ResetButton', 'XenForo.ResetButton');
	XenForo.register('.ExtraCounter', 'XenForo.ExtraCounter');
	
}
(jQuery, this, document);
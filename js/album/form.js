/** @param {jQuery} $ jQuery Object */
!function($, window, document, _undefined)
{	
	XenForo.ResetButton = function($input)
	{			
		if ($input.parent().parent().parent().parent().hasClass('xenOverlay'))
		{
			$input.css('display', 'inline !important');
		}
	}
	
	XenForo.register('.ResetButton', 'XenForo.ResetButton');
	
}
(jQuery, this, document);
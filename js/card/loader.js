/** @param {jQuery} $ jQuery Object */
!function($, window, document, _undefined)
{
	XenForo.PirateFormOverlay = function ($trigger)
	{		
			$trigger.click(function(e) {
				if (!parseInt(XenForo._enableOverlays))
				{
					window.location = XenForo.canonicalizeUrl($trigger.attr('href'));
					return false;
				}
				if (e.ctrlKey || e.shiftKey || e.altKey)
				{
					return true;
				}

				if (e.which > 1)
				{
					// right or middle click, don't open
					return true;
				}

				e.preventDefault();
				
				this.overlayLoader = new XenForo.OverlayLoader($trigger, false, false);
				
				this.overlayLoader.load(function()
				{
					function check()
					{
						var overlay        = $('.xenOverlay:last'),
						    offsetForm     = parseInt(overlay.css('top')),
					     	heightForm     = overlay.outerHeight(),
						    heightRequired = (offsetForm + heightForm);

						var heightWindow = $(window).height();

						if (heightRequired > heightWindow)
						{
							window.location = XenForo.canonicalizeUrl($trigger.attr('href'));
						}
					}
					
					setTimeout(check, 0);
				});
			});
			
	}
	
	XenForo.CardLoader = function($link)
	{
		$link.click(function(e) {
			e.preventDefault();
			
			$('#pirateContainer').xfSlideUp(500, function()
			{
				XenForo.ajax(
					$link.attr('href'),
					{},
					function (ajaxData, textStatus)
					{
						if (ajaxData.templateHtml)
						{
							new XenForo.ExtLoader(ajaxData, function()
							{
								$(ajaxData.templateHtml).xfInsert('replaceAll', '#pirateContainer', 'xfHide', 0, function()
								{
									new XenForo.setupSkills();
									$('#pirateContainer').xfSlideDown(500);
								});
							})
						}
					}
				);
			});
		});
	}
	
	
	// *********************************************************************

	XenForo.register('a.PirateFormTrigger', 'XenForo.PirateFormOverlay');
	XenForo.register('a.CardLoader', 'XenForo.CardLoader');

}
(jQuery, this, document);
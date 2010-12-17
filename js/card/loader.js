/** @param {jQuery} $ jQuery Object */
!function($, window, document, _undefined)
{
	
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
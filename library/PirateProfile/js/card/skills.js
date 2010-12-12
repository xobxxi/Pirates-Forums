/** @param {jQuery} $ jQuery Object */
!function($, window, document, _undefined)
{
	XenForo.setupSkills = function() {
		$('.pirateCard .pirateProfile div.pirateAvatar')
			.css('position', 'relative')
			.css('z-index', '2');
		$('.pirateCard div.pirateSkills').css('display', 'none');
		$('.pirateCard div.pirateHandle').hover(
			function() { $(this).css('background-image', 'url(\'../styles/pirates/card/handle/handle_over.gif\')')},
		    function() { $(this).css('background-image', 'url(\'../styles/pirates/card/handle/handle.gif\')')});
	
		$('.pirateCard .pirateSkills li').each(function() {
			$(this).css('background-color', 'inherit').css('color', 'rgb(202,202,202)').css('margin-top', '3px').css('margin-bottom', '0');
		
			$(this).append('<div class="pirateProgressBar"><p></p></div>');
			var progress = ($(this).data('level')/$(this).data('max'))*100;
			$(this).children().children().filter('p').width(progress + '%');
		});
	
		$('.pirateCard .pirateSkills li div.pirateProgressBar')
		    .css('border-width', '0px 6px')
			.css('border-image', 'url(../styles/pirates/card/borders/skills-border.gif) 0 6 stretch')
			.css('-moz-border-image', 'url(../styles/pirates/card/borders/skills-border.gif) 0 6 stretch')
			.css('-webkit-border-image', 'url(../styles/pirates/card/borders/skills-border.gif) 0 6 stretch');
	
		function showHandle() {
			$('.pirateCard div.pirateHandle:hidden').show("slide", { direction: "left" });
		}
	
		$('.pirateCard div.pirateHandle').click(function() {
			$(this).css('display', 'none');
			$(this).parent().children().filter('.pirateCard div.pirateSkills').toggle("slide", { direction: "left" });
			setTimeout(showHandle, 500);
		});
	}
	
	$(document).ready(function() {
		new XenForo.setupSkills();
	});
}
(jQuery, this, document);
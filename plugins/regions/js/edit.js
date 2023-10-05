$(function () {
	var html = $('html');

	var setSaveButtonState = function(state) {
		var states = {
			yellow: 'yellow',
			green: 'green'
		};
		var $save_button = $('.submit-box__button');

		if (!states.hasOwnProperty(state) || !$save_button.length)
		{
			return;
		}


		Object.keys(states).forEach(function(state) {
			$save_button.removeClass(states[state]);
		});

		$save_button.addClass(states[state]);
	};

	html.on('ajax.success', '.city__form', function (e, html_response) {
		$('.city-page__header').replaceWith($('.city-page__header', html_response));
		var pages = $('.city__pages', html_response);
		$('.city__pages').replaceWith(pages);
		BsUI.init(pages);
		StorefrontSettings.refreshFavicon();

		setSaveButtonState('green');
	});

	$('.city__form').find('input, textarea, select').on('change', function() {
		setSaveButtonState('yellow');
	});
});
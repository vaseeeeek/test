$(function () {
	var html = $('html');

	html.on('ajax.success', '.city__form', function () {
		window.location = '?plugin=regions';
		StorefrontSettings.refreshFavicon();
	});
});
$(function () {
	var html = $('html');

	html.on('click', '.variable__name', function () {
		if (window.getSelection) {
			window.getSelection().selectAllChildren(this);
		} else { // старый IE
			var range = document.selection.createRange();
			range.moveToElementText(this);
			range.select();
		}

		return false;
	});
});
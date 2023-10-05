var punyDomainsArray = $.parseJSON(punyDomains);

$(document).ajaxComplete(function(event, xhr, settings) {
	if ((settings.url.indexOf("?module=design&action=theme") != -1))
	{
		$("#wa-theme-list li[data-routing]").each(function (index, el) {
			var name_tmp = $(this)
				.find('span.url')
				.data('url')
				.split("/");
			var name = name_tmp[2];

			if (punyDomainsArray[name] === undefined)
			{
				return;
			}

			var title = punyDomainsArray[name]['title'];

			if (title)
			{
				$(this).find('span.url').text(title);
			}
		});

	}
	else if ((settings.url.indexOf("?module=pages&_") != -1))
	{
		$(".sidebar .block-pages").each(function (index, el) {
			var name = $(this).children('ul').data('domain');

			if (punyDomainsArray[name] === undefined)
			{
				return;
			}

			var title = punyDomainsArray[name]['title'];

			if (title)
			{
				$(this)
					.find('h4.heading')
					.html("<span class='count'><span style='display: none;'></span><i class='icon16 add wa-page-add no-parent'></i></span><i class='icon16 darr'></i>" + title);
			}
		});

	}
});

$(document).ready(function() {
	if ($(".s-storefronts-filter").length)
	{
		$(".s-storefronts-filter li").each(function (index, el) {
			if ($(this).data('storefront') != 'NULL')
			{
				var name = $(this).find('a').text();

				if (punyDomainsArray[name] === undefined)
				{
					return;
				}

				var title = punyDomainsArray[name]['title'];

				if (title)
				{
					$(this).find('a').text(title);
				}
			}
		});
	}

	if ($("#s-category-list").length)
	{
		$("#s-category-list li[data-type=category] span.hint.routes").each(function (index, el) {
			var name = $(this).text().split(" ");
			var title = "";

			$.each(name, function (i, val) {
				if (val && punyDomainsArray[val] !== undefined)
				{
					title += " " + punyDomainsArray[val]['title'];
				}
			});

			if (title)
			{
				$(this).text(title);
			}
		});
	}

	if ((location.href.indexOf("shop/?action=storefronts#/design/") != -1))
	{
		setTimeout(function () {
			$("#wa-theme-list li[data-routing]").each(function (index, el) {
				var name_tmp = $(this).find('span.url').data('url').split("/");
				var name = name_tmp[2];

				if (punyDomainsArray[name] === undefined)
				{
					return;
				}

				var title = punyDomainsArray[name]['title'];

				if (title)
				{
					$(this).find('span.url').text(title);
				}
			});
		}, 1500);
	}
});

jQuery(function ($) {
	if (typeof shopRegions != 'object')
	{
		shopRegions = {};
	}

	shopRegions.region_image_index = 1;
	shopRegions.selected_region_code = $('.js-shop-regions-window__region_region_wrap.selected_region').data('region_code');

	shopRegions.$search_result_block = $('.js-shop-region-window_search .js-search_result');

	var $hide_window_listener = null;

	shopRegions.showWindow = function()
	{
		var html = $('html');

		var width_before = html.outerWidth();
		html.addClass('shop-regions-status_window-show');
		var width_after = html.outerWidth();
		if (width_after - width_before > 0)
		{
			html.css('padding-right', width_after - width_before);
		}


		$('.shop-regions-window').addClass('shop-regions-window_show');

		if ($('.shop-regions-window__regions_all:visible').length)
		{
			html
				.addClass('shop-regions-status_window-regions-all-show');
		}

		$('.js-shop-regions-window__wrapper').on('click', '.shop-regions__trigger-switch-city', function (e) {
			e.stopPropagation();

			var $this = $(this);
			var city_id = $this.data('id');
			shopRegions.confirmCity(city_id);
			shopRegions.switchCity(city_id);
		});

		$('.js-shop-regions-window__wrapper').on('click', function(e) {
			e.stopPropagation();
		});

		$('.js-shop-regions-window').on('click', function() {
			shopRegions.hideWindow();
		});

		if (shopRegions.regions_sidebar_enable)
		{
			var $regions_sidebar = $('.js-shop-region-window_regions_sidebar');
			var $selected_region = $regions_sidebar.find('.selected_region');
			$regions_sidebar.scrollTop($selected_region.innerHeight() * $selected_region.index())
		}

		var code = $('.js-shop-regions-window__region_region').first().data('region_code');
		if (shopRegions.selected_region_code === undefined && code !== undefined)
		{
			shopRegions.selectRegion(code);
		}

		$hide_window_listener = $(document).on('keyup', function(e) {
			if (e.keyCode === 27)
			{
				shopRegions.hideWindow();
			}
		});
	};

	shopRegions.hideWindow = function()
	{
		$('html')
			.removeClass('shop-regions-status_window-show')
			.css('padding-right', 0);
		$('.shop-regions-window').removeClass('shop-regions-window_show');


		$('.js-shop-regions-window__wrapper').off('click');
		$('.js-shop-regions-window').off('click');
		if ($hide_window_listener)
		{
			$hide_window_listener.off('keyup');
			$hide_window_listener = null;
		}
	};

	shopRegions.showIpAnalyzer = function()
	{
		$('html').addClass('shop-regions-status_ip-analyzer-show');
		$('.shop-regions-ip-analyzer').removeClass('shop-regions-ip-analyzer_hide');
	};

	shopRegions.hideIpAnalyzer = function()
	{
		$('html').removeClass('shop-regions-status_ip-analyzer-show');
		$('.shop-regions-ip-analyzer').addClass('shop-regions-ip-analyzer_hide');
	};

	shopRegions.showAllRegions = function()
	{
		$('.shop-regions-window').addClass('shop-regions-window_show-all-regions');
	};

	shopRegions.hideAllRegions = function()
	{
		$('.shop-regions-window').removeClass('shop-regions-window_show-all-regions');
	};

	shopRegions.confirmCity = function (city_id) {
		$.cookie('shop_regions_confirm', city_id === undefined ? 0 : city_id , { path : '/', expires: 200 });
	};

	shopRegions.switchCity = function (city_id) {
		if (city_id == this.current_region_id)
		{
			this.hideIpAnalyzer();
			this.hideAllRegions();
			this.hideWindow();
			return;
		}

		$.ajax({
			url: shopRegions.request_redirect_url,
			type: 'POST',
			dataType: 'json',
			cache: false,
			data: {
				url: location.pathname,
				city_id: city_id
			},
			success: function(data) {
				var redirect_url = data.data.redirect_url;
				var restore_user_environment_url = data.data.restore_user_environment_url;

				var on_success = function() {
					if (redirect_url !== undefined && redirect_url.length)
					{
						window.location = redirect_url;
					}
				};

				var key = $.cookie('shop_regions_env_key');
				shopRegions._triggerRestoreUserEnvironmentOnNewRegion(city_id, key, restore_user_environment_url, on_success);
			},
			complete: function() {
				shopRegions._toggleLoading(false);
			}
		});

		this._toggleLoading(true);
	};

	shopRegions.selectRegion = function(region_code) {
		shopRegions.selected_region_code = region_code;

		$('.js-shop-regions-window-search__input').val('');
		shopRegions.search('');

		var region_code_selector = '[data-region_code="' + region_code + '"]';

		$('.js-shop-regions-window__region_region_wrap')
			.removeClass('selected_region')
			.find('.js-shop-regions-window__region_region' + region_code_selector)
			.closest('.js-shop-regions-window__region_region_wrap')
			.addClass('selected_region');

		$('.js-shop-regions-window__region_group')
			.addClass('hidden')
			.filter(region_code_selector)
			.removeClass('hidden');

		$('.js-popular_for_region')
			.removeClass('visible')
			.filter(region_code_selector)
			.addClass('visible');
	};

	shopRegions.search = function(search) {
		$('.shop-regions-window').toggleClass('searching', !!search);

		shopRegions.$search_result_block.html('');

		$('.js-shop-region-window_search .js-region_header').removeClass('visible');

		var is_no_found = true;

		if (search)
		{
			if (shopRegions.regions_sidebar_enable)
			{
				var regions = [];
				var cities_by_region = [];

				$('.js-shop-region-window_search').first()
					.find('.js-shop-regions-window__region_group.js-search_source')
					.each(function (group_index, group) {
						var $group = $(group);

						regions[group_index] = $group.find('.js-region_header').clone();

						var cities = [];
						$group.find('.shop-regions-window__region').each(function (i, item) {
							var $item = $(item);
							var is_shown = $item.find('.shop-regions__trigger-switch-city').text().trim().toLowerCase().indexOf(search.toLowerCase()) > -1;
							if (is_shown)
							{
								var $city = $item.clone();
								$city.find('.js-letter').remove();
								cities.push($city);
								is_no_found = false;
							}

							cities_by_region[group_index] = cities;
						});
					});

				shopRegions._generateCitiesByRegionSearchList(regions, cities_by_region);
			}
			else
			{
				var cities = [];

				$('.js-shop-region-window_search').first()
					.find('.js-search_source .shop-regions__trigger-switch-city')
					.each(function (i, item) {
						var $item = $(item);
						var is_shown = $item.text().trim().toLowerCase().indexOf(search.toLowerCase()) > -1;
						if (is_shown)
						{
							var $div = $item.parent();

							cities.push({
								id: $item.data('id'),
								name: $item.text().trim(),
								div_class: $div.prop('class'),
								region_code: $div.data('region_code')
							});

							is_no_found = false;
						}
					});

				shopRegions._generateCitiesSearchList(cities);
			}

			$('.js-shop-region-window_search .js-no_found_message').toggle(is_no_found);
		}

		shopRegions._toggleSearchMode(!!search);
	};

	shopRegions._generateCitiesByRegionSearchList = function(regions, cities_by_region) {
		regions.forEach(function(group, group_index) {
			if (cities_by_region[group_index] !== undefined && cities_by_region[group_index].length > 0)
			{
				shopRegions.$search_result_block.append(group);

				cities_by_region[group_index].forEach(function(city) {
					shopRegions.$search_result_block.append(city);
				});
			}
		});
	};

	shopRegions._generateCitiesSearchList = function(cities) {
		if (cities.length === 0)
		{
			return;
		}

		var cities_in_column = cities.length < shopRegions.number_of_columns
			? 1
			: parseInt(cities.length / shopRegions.number_of_columns);

		var extra_cities = cities.length < shopRegions.number_of_columns
			? 0
			: cities.length % shopRegions.number_of_columns;

		var last_first_letter = '';
		var city_index = 0;
		for (var column_i = 0; column_i < shopRegions.number_of_columns; column_i++)
		{
			var $column = $('.js-shop-regions-window__regions-column').first().clone();
			$column.html('');
			for (var i = 0; i < cities_in_column; i++)
			{
				var city = cities[city_index++];
				if (city === undefined)
				{
					continue;
				}

				var first_letter = city.name.charAt(0);

				if (shopRegions.enable_group_by_letter && last_first_letter !== first_letter)
				{
					$column.append('<div class="shop-regions-window__regions-letter">' + first_letter + '</div>');
					last_first_letter = first_letter;
				}
				var link = '<a class="shop-regions__link shop-regions-window__link shop-regions__trigger-switch-city visible" data-id="' + city.id + '">' + city.name + '</a>';
				$column.append('<div class="' + city.div_class + '" data-region_code="' + city.region_code + '"' + '>' + link + '</div>');
			}

			if (extra_cities)
			{
				city = cities[city_index++];
				if (city !== undefined)
				{
					first_letter = city.name.charAt(0);

					if (shopRegions.enable_group_by_letter && last_first_letter !== first_letter)
					{
						$column.append('<div class="shop-regions-window__regions-letter">' + first_letter + '</div>');
						last_first_letter = first_letter;
					}
					link = '<a class="shop-regions__link shop-regions-window__link shop-regions__trigger-switch-city visible" data-id="' + city.id + '">' + city.name + '</a>';
					$column.append('<div class="' + city.div_class + '" data-region_code="' + city.region_code + '"' + '>' + link + '</div>');
				}

				extra_cities--;
			}

			shopRegions.$search_result_block.append($column);
		}


	};

	shopRegions._toggleSearchMode = function(toggle) {
		if (!toggle)
		{
			$('.js-shop-region-window_search .js-shop-regions-window__region_group[data-region_code="' + shopRegions.selected_region_code + '"]').removeClass('hidden');
			$('.js-shop-region-window_search .js-search_source .shop-regions-window__region').addClass('visible');

			$('.js-shop-region-window_search .js-no_found_message').hide();
		}

		$('.js-shop-region-window_search .js-shop-regions-window__regions-letter').toggle(!toggle);
		$('.js-shop-region-window_search .js-regions_wrapper').toggle(!toggle);
	};

	shopRegions._triggerRestoreUserEnvironmentOnNewRegion = function(city_id, environment_key, restore_user_environment_url, on_success) {
		var img = new Image(1, 1);

		img.src = restore_user_environment_url.replace(/http(s)?:/, '')
			+ '?key=' + environment_key
			+ '&city_id=' + city_id
			+ '&_=' + (new Date).getTime();

		img.addEventListener('error', on_success);
	};

	shopRegions._toggleLoading = function(toggle) {
		//todo _toggleLoading animation
	};

	shopRegions._loadPopupContent = function() {
		$.ajax({
			url: shopRegions.load_popup_content_url,
			type: 'GET',
			success: function(response) {
				var $popup_wrapper = $('.js-shop-regions__window');
				if ($popup_wrapper.length === 0) {
					$popup_wrapper = $('<div/>');
					$popup_wrapper.addClass('shop-regions__window js-shop-regions__window');

					$('body').append($popup_wrapper);
				}

				$popup_wrapper.html(response);

				$('.js-shop-regions__button').removeClass('shop-regions__button_hidden');

				var $variants_block = $('.js-shop-regions__ip-analyzer-variants');
				if ($variants_block.length > 0) {
					// $('body').append($variants_block);
					$variants_block.removeClass('shop-regions__ip-analyzer-variants_hidden');
				}

				shopRegions._initPopup();
			}
		});
	};

	shopRegions._initPopup = function() {
		shopRegions.selected_region_code = $('.js-shop-regions-window__region_region_wrap.selected_region').data('region_code');

		shopRegions.$search_result_block = $('.js-shop-region-window_search .js-search_result');

		$('.shop-regions__trigger-hide-window').on('click', function () {
			shopRegions.hideWindow();
		});

		$('.shop-regions__trigger-confirm').on('click', function () {
			shopRegions.confirm();
		});

		$('.shop-regions-window__trigger-show-all-regions').on('click', function () {
			shopRegions.showAllRegions();
		});

		$('.shop-regions-window__trigger-hide-all-regions').on('click', function () {
			shopRegions.hideAllRegions();
		});

		$('.shop-regions-ip-analyzer__trigger-go-to-my-city').on('click', function () {
			window.location = $(this).data('href');
			shopRegions.hideIpAnalyzer();
		});

		$('.shop-regions-ip-analyzer__trigger-select-city').on('click', function () {
			shopRegions.hideIpAnalyzer();
			shopRegions.showWindow();
		});

		$('.shop-regions-ip-analyzer__button-close').on('click', function() {
			shopRegions.confirmCity();
			shopRegions.hideIpAnalyzer();
		});

		$('.js-shop-regions-window__region_region').on('click', function() {
			shopRegions.selectRegion($(this).data('region_code'));
		});

		$('.js-shop-regions-window-search__input').on('keyup', function () {
			shopRegions.search($(this).val().trim());
		});

		$('.js-clear_search').on('click', function() {
			$('.js-shop-regions-window-search__input').val('');
			shopRegions.search('');
		});

		$(document).on('click', '.shop-regions-ip-analyzer__trigger-switch-city', function (e) {
			e.stopPropagation();

			var $this = $(this);
			var city_id = $this.data('id');
			shopRegions.confirmCity(city_id);
			shopRegions.switchCity(city_id);
		});


		if ($('.shop-regions-ip-analyzer:visible').length)
		{
			$('html').addClass('shop-regions-status_ip-analyzer-show');
		}

		if ($('.shop-regions-window:visible').length)
		{
			var width_before = $('html').outerWidth();
			$('html').addClass('shop-regions-status_window-show');
			var width_after = $('html').outerWidth();

			if (width_after - width_before > 0)
			{
				$('html').css('padding-right', width_after - width_before);
			}
		}
	};


	$('.shop-regions__trigger-show-window').on('click', function () {
		shopRegions.showWindow();
		shopRegions.hideIpAnalyzer();
	});


	shopRegions._loadPopupContent();
});

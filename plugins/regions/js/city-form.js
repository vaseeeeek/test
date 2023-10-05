var StorefrontSettings = {
	refreshFavicon: function ()
	{
		var storefront = $('.city__storefront-select .select-box__input').val();
		var favicon = $('.city-storefront__favicon');
		favicon.removeAttr('src');
		var touch_icon = $('.city-storefront__touch-icon');
		touch_icon.removeAttr('src');

		if (storefront.length > 0)
		{
			var tmp = storefront.split('/');
			var domain = tmp.shift();

			setTimeout(function () {
				var img_favicon = new Image();
				img_favicon.onload = function () {
					favicon.attr('src', img_favicon.src);
				};
				img_favicon.src = '//'+domain+'/favicon.ico';

				var img_touch_icon = new Image();
				img_touch_icon.onload = function () {
					touch_icon.attr('src', img_touch_icon.src);
				};
				img_touch_icon.src = '//'+domain+'/apple-touch-icon.png';
			}, 1000);
		}
	},

	showLinkedFields: function ()
	{
		var field_group = $('.city__storefront-field-group');
		field_group.removeClass('city__storefront-field-group_unlinked');
		BsUI.refreshWysiwyg(field_group);
	},

	hideLinkedFields: function ()
	{
		$('.city__storefront-field-group').addClass('city__storefront-field-group_unlinked');
	},

	setSettingsLink: function (domain, route_id)
	{
		$('.city__storefront-link').attr('href', '?action=storefronts#/design/theme=null&domain='+domain+'&route='+route_id+'&action=settings')
	},

	setRobotsTxt: function (robots_txt)
	{
		var $target = $('.city-storefront__robots-txt');

		$target.val(robots_txt);
	},

	setHead: function (head)
	{
		var $target = $('.city-storefront__head');

		$target.val(head);
		$target.trigger('refresh');
		$target.trigger('change');
	},

	setStock: function (stock)
	{
		var $stock = $('.city-storefront__stock');
		$stock.find('option').removeAttr('selected');
		$stock.find('option[value="'+stock+'"]').attr('selected', 'selected');
		$stock.trigger('change');
	},

	setRegionsSsl: function (ssl)
	{
		if (ssl === null || ssl === undefined)
		{
			ssl = '';
		}

		var $select = $('.city-storefront__regions_ssl');
		$select.find('option').removeAttr('selected');
		$select.find('option[value="'+ssl+'"]').attr('selected', 'selected');
		$select.trigger('change');
	},

	setPublicStocks: function(public_stocks)
	{
		var $public_stocks = $('.js-storefront_public_stock_block');
		if (Array.isArray(public_stocks))
		{
			$public_stocks.find('.js-radio[value=""]')
				.prop('checked', true)
				.trigger('change');
			public_stocks.forEach(function(value) {
				$public_stocks.find('.js-checkbox[value="' + value + '"]').prop('checked', true);
			});
		}
		else
		{
			$public_stocks.find('.js-radio[value="' + public_stocks + '"]')
				.prop('checked', true)
				.trigger('change');
		}
	},

	setDropOutOfStock: function(drop_out_of_stock)
	{
		$('.js-drop_out_of_stock_block .js-radio[value="' + drop_out_of_stock + '"]').prop('checked', true);
	},

	setCurrency: function(currency)
	{
		var $stock = $('.city-storefront__currency');
		$stock.find('option').removeAttr('selected');
		$stock.find('option[value="'+currency+'"]').prop('selected', true);
		$stock.trigger('change');
	},

	setPayment: function (payment_id)
	{
		$('.city-storefront__payment').find('.radio-box__input, .check-box__input').removeAttr('checked');
		if (payment_id == '0')
		{
			$('.city-storefront__payment-value_0').attr('checked', 'checked').trigger('change');
		}
		else
		{
			if ($.isArray(payment_id))
			{
				$('.city-storefront__payment-value_array').attr('checked', 'checked').trigger('change');

				for (var i in payment_id)
				{
					if (payment_id.hasOwnProperty(i))
					{
						$('.city-storefront__payment-value_' + payment_id[i]).attr('checked', 'checked')
							.trigger('change');
					}
				}
			}
		}
	},

	setShipping: function (shipping_id)
	{
		$('.city-storefront__shipping').find('.radio-box__input, .check-box__input').removeAttr('checked');
		if (shipping_id == '0')
		{
			$('.city-storefront__shipping-value_0').attr('checked', 'checked').trigger('change');
		}
		else
		{
			if ($.isArray(shipping_id))
			{
				$('.city-storefront__shipping-value_array').attr('checked', 'checked').trigger('change');

				for (var i in shipping_id)
				{
					if (shipping_id.hasOwnProperty(i))
					{
						$('.city-storefront__shipping-value_' + shipping_id[i]).attr('checked', 'checked').trigger('change');
					}
				}
			}
		}
	},

	setPages: function (pages)
	{
		var $pages = $();
		var $pages_list = $('.city_storefront__pages-list');
		$pages_list.empty();
		var template = $('.template-box .template-page-link').contents('a');

		for (var i in pages)
		{
			if (pages.hasOwnProperty(i))
			{
				var _template = template.clone();
				_template.attr('href', '?action=storefronts#/pages/' + pages[i].id);
				_template.html(pages[i].name);
				$pages = $pages.add(_template);
			}
		}

		var is_first = true;

		$pages.each(function () {
			if (is_first)
			{
				is_first = false;
			}
			else
			{
				$pages_list.append(', ');
			}

			$pages_list.append($(this));
		});
	},

	resetFields: function ()
	{
		StorefrontSettings.setRobotsTxt('');
		StorefrontSettings.setHead('');

		var storefront_city_specific_options = $('html').find('.js-specific_settings_enabled_input:checked').val() == '1';
		if (!storefront_city_specific_options)
		{
			StorefrontSettings.setPayment('0');
			StorefrontSettings.setShipping('0');
			StorefrontSettings.setStock(0);
			StorefrontSettings.setRegionsSsl('');
			StorefrontSettings.setPublicStocks('0');
			StorefrontSettings.setDropOutOfStock('0');
			StorefrontSettings.setCurrency(0);
		}

		StorefrontSettings.setPages([]);
	},

	setStorefronts: function (storefronts)
	{
		var $city_storefront = $('.city__storefront-select .select-box__input');

		var $storefronts = $();

		for (var i in storefronts)
		{
			if (storefronts.hasOwnProperty(i))
			{
				var $storefront = $('<option value="' + storefronts[i].name + '" data-domain="' + storefronts[i].domain + '" data-route="' + storefronts[i].route + '">' + storefronts[i].title + '</option>');
				$storefronts = $storefronts.add($storefront);
			}
		}

		$city_storefront.find('option:not(:first)').remove();
		$city_storefront.append($storefronts);
		$city_storefront.trigger('change');
	}
};

var CitySettings = {
	setRegions: function (regions)
	{
		var $region_code = $('.city__region-code');
		$region_code.find('option:not(:first)').remove();

		var $regions = $();

		for (var i in regions)
		{
			if (regions.hasOwnProperty(i))
			{
				var $region = $('<option value="' + regions[i].code + '">' + regions[i].name + '</option>');
				$regions = $regions.add($region);
			}
		}

		$region_code.append($regions);
	}
};

$(function ()
{
	var html = $('html');

	$('.shop-regions__li').addClass('selected').removeClass('no-tab');

	StorefrontSettings.refreshFavicon();


	// Инициализация формы загрузки

	html.find('.city__form').on('submit', function () {
		var storefront = $('#city_storefront').val();
		var tmp = storefront.split('/');
		var domain = tmp.shift();
		$('.city-storefront__domain').val(domain);
		$('#form-upload').trigger('submit');
	});


	// Подгрузка регионов страны

	html.find('.city__country').on('change', function ()
	{
		CitySettings.setRegions([]);
	});

	html.find('.city__country').on('ajax.success', function (e, response)
	{
		CitySettings.setRegions(response.data);
	});


	// Подгрузка настроек витрины и формы клонирования

	html.find('.city__storefront-select').on('change', function ()
	{
		var option = $('option:selected', this);

		$('input[name="city[domain]"]').val(option.data('domain'));
		$('input[name="city[route]"]').val(option.data('route'));

		StorefrontSettings.hideLinkedFields();
		StorefrontSettings.resetFields();
	});

	html.find('.city__trigger-show-clone-form').on('click', function () {
		$.ajax({
			url: '?plugin=regions&module=data&action=storefront',
			method: 'post',
			dataType: 'json',
			data: { value: 'clone' },
			success: function (response) {
				var $data = $(response.data.form);
				var dialog = $data.waDialog({
					className: 'city__clone-storefront-dialog'
				});

				initCloneForm($data);

				$data.find('.clone-storefront__storefront').trigger('change');

				$data.find('.clone-storefront-dialog__trigger-close').on('click', function () {
					dialog.trigger('close');
					$(this).data('value', '');
					$(this).trigger('change');
				});

				$(this).data('value', '');
				$(this).trigger('change');
			}
		});
	});

	html.find('.city__storefront-select').on('ajax.success', function (e, response)
	{
		/**
		 * @type {Object} data
		 * @property {Object[]} domain
		 * @property {int|int[]} payment
		 * @property {int|int[]} shipping
		 * @property {int} stock
		 * @property {string} regions_ssl
		 * @property {string|string[]} public_stocks
		 * @property {int} drop_out_of_stock
		 * @property {Object[]} pages
		 */
		var data = response.data;
		var value = $(this).data('value');

		if (value != '' && data !== null && data.domain !== undefined)
		{
			/**
			 * @var {Object}
			 * @property {String} robots_txt
			 * @property {String} head
			 * @property {String} route_index
			 * @property {String} name
			 */
			var domain = data.domain;

			StorefrontSettings.setRobotsTxt(domain.robots_txt);
			StorefrontSettings.setHead(domain.head);

			var storefront_city_specific_options = html.find('.js-specific_settings_enabled_input:checked').val() == '1';
			if (!storefront_city_specific_options)
			{
				StorefrontSettings.setPayment(data.payment);
				StorefrontSettings.setShipping(data.shipping);
				StorefrontSettings.setStock(data.stock);
				StorefrontSettings.setRegionsSsl(data.regions_ssl);
				StorefrontSettings.setPublicStocks(data.public_stocks);
				StorefrontSettings.setDropOutOfStock(data.drop_out_of_stock);
				StorefrontSettings.setCurrency(data.currency);
			}

			StorefrontSettings.setPages(data.pages);
			StorefrontSettings.setSettingsLink(data.domain.name, data.domain.route_index);
			StorefrontSettings.showLinkedFields();
			BsUI.refreshWysiwyg($('form'));
			StorefrontSettings.refreshFavicon();
		}

		return false;
	});


	html.find('.js-storefront_public_stock_block .js-radio').on('change', function() {
		var $radio = $(this);
		if ($radio.is(":checked"))
		{
			$('.js-storefront_public_stock_block .js-checkbox')
				.prop("disabled", true)
				.prop("checked", false);

			$radio
				.parent().next("div")
				.find("input")
				.prop("disabled", false);
		}
	});

	html.find('.js-specific_settings_enabled_input').on('change', function() {
		var specific_enabled = $(this).val() == '1';
		$('.js-storefront_options_block').toggle(!specific_enabled);
		$('.js-storefront_specific_options_block').toggle(specific_enabled);
	});

	function initCloneForm(context)
	{
		BsUI.init(context);
		var form = context.find('.city__storefront-clone-form');

		form.on('submit', function ()
		{
			//StorefrontSettings.setStorefronts([]);
		});

		form.on('ajax.success', function (e, response)
		{
			StorefrontSettings.setStorefronts(response.data.storefronts);
			$('.city__clone-storefront-dialog').trigger('close');
			var select = $('.city__storefront-select .select-box__input');
			select.find('option[value="'+ response.data.new_storefront +'"]').attr('selected', 'selected');
			select.trigger('change');

			return false;
		});

		form.find('.clone-storefront__storefront').on('change', function ()
		{
			var $this = $(this).closest('form');
            var option = $(this).find('option:selected');
            $this.find('.clone-storefront__new-domain').val(option.data('domain'));
            $this.find('.clone-storefront__new-route').val(option.data('route'));
		});
	}
});
if (!window.shop_dp) {
	window.shop_dp = {
		plugin_url: '{$plugin_url}',
		dialog_url: '{$dialog_url}',
		service_url: '{$service_url}',
		calculate_url: '{$calculate_url}',
		svg_url: '{$svg_url}',
		point_url: '{$point_url}',
		city_search_url: '{$city_search_url}',
		city_save_url: '{$city_save_url}',
		location: {
			country_code: '{$country_code}',
			country_name: '{$country_name}',
			region_code: '{$region_code}',
			region_name: '{$region_name}',
			city: '{$city}'
		},
		loader: {
			assets: {},
			loadCSS: function(url) {
				$('<link>')
					.appendTo('head')
					.attr({
						type: 'text/css',
						rel: 'stylesheet',
						href: url
					});
			},
			load: function(asset, callback) {
				var self = this;

				var resolve = function (is_from_cache) {
					if (typeof callback === 'function' && asset in self.assets) {
						callback.call(window.shop_dp.loader.assets[asset], is_from_cache);
					}
				};

				if (window['shop_dp_is_loading_asset_' + asset]) {
					$(document).on('shop_dp_asset_loaded_' + asset, resolve);
					return false;
				}

				if (!(asset in this.assets) && !window['shop_dp_is_loading_asset_' + asset]) {
					window['shop_dp_is_loading_asset_' + asset] = true;

					$.ajax({
						dataType: 'script',
						cache: false,
						url:
							window.shop_dp.plugin_url +
							'js/' +
							(asset === 'core' ? 'core' : 'frontend.' + asset) +
							'.js',
						complete: function() {
							window['shop_dp_is_loading_asset_' + asset] = false;
							$(document).trigger('shop_dp_asset_loaded_' + asset);
							resolve(false);
						}
					});
				} else {
					resolve(true);
				}
			}
		},
		map_service: '{$map_service}',
		map_params: {$map_params|json_encode}
	};

	(function($) {
		window.shop_dp.loader.load('core');
	})(jQuery);
}

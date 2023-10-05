(function ($) {
	var waOrder = ['cart', 'form']
		.reduce(function (acc, entity) {
			acc[entity] = (function () {
				var deferred = $.Deferred();

				if (window.waOrder && window.waOrder[entity]) {
					deferred.resolve(window.waOrder[entity]);
				} else {
					$('#wa-order-' + entity + '-wrapper').on('ready', function (event, api) {
						deferred.resolve(api);
					});
				}

				return deferred;
			})();

			return acc;
		}, {});

	$('#wa-order-cart-wrapper')
		.on('rendered', function (event, api) {
			var items = api.cart && api.cart.items ? api.cart.items : void 0;
			if (!items) {
				return;
			}

			updateItems(items);
		});

	function updateItems(items) {
		waOrder.cart.then(function (cart) {
			$.each(items, function(i, item) {
				var $product = $('.wa-products .wa-product').filter('[data-id="' + item.id + '"]');
				if (!$product.length) {
					return;
				}

				var $price = $product.find('.js-product-price');
				var match = $price.html().match(/(.*)\//);
				var prevPrice = match && match[1] ? match[1] : void 0;
				if (prevPrice) {
					$price.html($price.html().replace(prevPrice, cart.formatPrice(item.price)));
				}
			});
		});
	}
})(jQuery);

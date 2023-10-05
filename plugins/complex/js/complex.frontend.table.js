(function($) {
	$.complexFrontendTable = function(
		table, skuSelector, skuTypeSelector, priceFormat, complexPrices, skuFeaturesKeys
	) {
		$(document).off('change.complex_sku').on('change.complex_sku', skuSelector, function() {
			var sku_id = $(this).val();
			if (!complexPrices[sku_id]) return;
			table.find('.complex-plugin-price').each(function() {
				var id = $(this).data('id');
				var wrapper = $(this).find('.value');
				if(wrapper.find('.price').length)
					wrapper = wrapper.find('.price');
				wrapper.html(complexPrices[sku_id][id][priceFormat]);
			});
		});

		$(document).off('change.complex_sku_type').on('change.complex_sku_type', skuTypeSelector, function() {
			var sku_f = '';
			$(skuTypeSelector).each(function() {
				var feature_id = $(this).data('feature-id');
				var value = $(this).val();

				sku_f += '|' + feature_id + ':' + value;
			});
			sku_f = sku_f.slice(1);

			sku_id = skuFeaturesKeys[sku_f];
			if(!complexPrices[sku_id]) {
				return;
			}

			table.find('.complex-plugin-price').each(function() {
				var id = $(this).data('id');
				if(!complexPrices[sku_id][id]) {
					return;
				}

				var wrapper = $(this).find('.value');
				if(wrapper.find('.price').length)
					wrapper = wrapper.find('.price');
				wrapper.html(complexPrices[sku_id][id][priceFormat]);
			});
		});
	}
})(jQuery);

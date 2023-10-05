'use strict';

(function($) {
	$.bundlingFrontend.onInit = function(cb) {
		$(document).on('click', '.bundling-buy-button', function() {
			let dat = $(this);
			let product_id = dat.data('product-id');
			let sku_id = dat.data('sku-id');
			let products = [{
				product_id: product_id,
				sku_id: sku_id,
				quantity: 1//dat.closest('.bundling-product').find('.bundling-product-quantity input').val()
			}];
			
			$.post($.bundlingFrontend.frontend_url + 'bundling/add2cart/', {
				products: products
			}, function(data) {
				dat.hide();
				dat.closest('.bundling-buy').find('.added').show();
			});
		});
		
		$('.bundling-product-quantity input', this.wrapper).on('keyup mouseup change', function() {
			let quantity = $.bundlingFrontend.parseQuantity($(this).val());
			let bundle_id = $(this).data('bundle-id');
			let product_id = $(this).data('product-id');
			let sku_id = $(this).data('sku-id');
			let product = $(this).closest('.bundling-product');
			let price = parseFloat(product.data('price'));
			let default_frontend_price = parseFloat($.bundlingFrontend.getProduct(product_id, sku_id, 'default_frontend_price'));
			
			var min = $(this).attr('min') || 1;
			var step = $(this).attr('step') || 1;

			if(quantity < min)
				quantity = min;
			
			$.bundlingFrontend.updateSelectedProducts({
				action: 'quantity',
				bundle: bundle_id,
				product_id: product_id,
				sku_id: sku_id,
				quantity: quantity
			});
			
			$.bundlingFrontend.changePrice();
			
			product.find('.bundling-product-price').html($.bundlingFrontend.formatPrice(price * quantity));
			product.find('.compare-price').html($.bundlingFrontend.formatPrice(default_frontend_price * quantity));
		});
		
		this.wrapper.find('.bundling-product-selector').on('change', function() {
			let bundle = $(this).closest('.bundling-bundle');
			let bundle_id = bundle.data('bundle-id');
			let multiple = bundle.data('multiple');
			let product_id = $(this).val();
			let product = $(this).closest('.bundling-product');
			let sku_id = product.data('sku-id');
			let price = product.data('price');
			let image = product.data('image');
			let quantity = $.bundlingFrontend.parseQuantity(product.find('.bundling-product-quantity input').val());
			
			if(!quantity)
				quantity = 1;
			
			if(parseInt(product_id)) {
				if(!multiple) {
					bundle.find('.bundling-product').removeClass('selected');
					product.addClass('selected');
					bundle.find('.bundling-bundle-title').addClass('selected');
					
					$.bundlingFrontend.updateSelectedProducts({
						action: '-',
						bundle: bundle_id
					});
					
					$.bundlingFrontend.updateSelectedProducts({
						action: '+',
						bundle: bundle_id,
						product_id: product_id,
						sku_id: sku_id,
						price: price,
						quantity: quantity,
						image: image
					});
				} else {
					product[$(this).is(':checked') ? 'addClass' : 'removeClass']('selected');
					if($(this).is(':checked')) {
						bundle.find('.bundling-bundle-title').addClass('selected');
						
						$.bundlingFrontend.updateSelectedProducts({
							action: '+',
							bundle: bundle_id,
							product_id: product_id,
							sku_id: sku_id,
							price: price,
							quantity: quantity,
							image: image
						});
					} else {
						if(!bundle.find('.bundling-product-selector:checked').length)
							bundle.find('.bundling-bundle-title').removeClass('selected');
						
						$.bundlingFrontend.updateSelectedProducts({
							action: '-',
							bundle: bundle_id,
							product_id: product_id,
							sku_id: sku_id
						});
					}
				}
				
				$.bundlingFrontend.changePrice();
			} else {
				bundle.find('.bundling-product').removeClass('selected');
				bundle.find('.bundling-bundle-title').removeClass('selected');
				
				$.bundlingFrontend.updateSelectedProducts({
					action: '-',
					bundle: bundle_id
				});
				
				$.bundlingFrontend.changePrice();
			}
		});

		if(typeof cb === 'function')
			cb();
	}
})(jQuery);
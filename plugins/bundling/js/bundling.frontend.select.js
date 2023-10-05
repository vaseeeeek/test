'use strict';

(function($) {
	$.bundlingFrontend.onInit = function(cb) {
		this.wrapper.find('select').change(function() {
			let bundle_id = $(this).data('id');
			let product_id = $(this).val();
			let multiple = $(this).data('multiple');
			let selected = $('option:selected', this);
			let sku_id = selected.data('sku-id');
			let price = selected.data('price');
			let url = selected.data('url');
			let image = selected.data('image');
			
			if(product_id) {
				let selected_wrapper = $(this).closest('tr').next('.bundling-about-selected-product');
				selected_wrapper.find('a.bundling-product-link, a.bundling-product-buy-link').attr('href', selected.data('url'));
				
				if(!multiple) {
					$.bundlingFrontend.updateSelectedProducts({
						action: '-',
						bundle: bundle_id
					});
					
					selected_wrapper.find('a.bundling-product-link img').attr('src', image);
					selected_wrapper.show();
				}
				
				$.bundlingFrontend.updateSelectedProducts({
					action: '+',
					bundle: bundle_id,
					product_id: product_id,
					sku_id: sku_id,
					price: price,
					quantity: 1,
					image: image
				});
				
				if(!multiple)
					$.bundlingFrontend.changePrice();
				else {
					let new_wrapper = selected_wrapper.clone().insertBefore(selected_wrapper);
					new_wrapper.find('a.bundling-product-link').data('bundle-id', bundle_id).data('product-id', product_id).data('price', price).attr('href', url).find('span').html(selected.html());
					new_wrapper.find('a.bundling-product-link img').attr('src', image);
					
					$('.bundling-product-quantity input', new_wrapper).val(1).on('keyup mouseup change', function() {
						let quantity = parseInt($(this).val());

						let wrapper = $(this).closest('.bundling-about-selected-product');
						let link_wrapper = wrapper.find('a.bundling-product-link');
						let selected_bundle_id = link_wrapper.data('bundle-id');
						let selected_product_id = link_wrapper.data('product-id');
						
						$.bundlingFrontend.updateSelectedProducts({
							action: 'quantity',
							bundle: bundle_id,
							product_id: selected_product_id,
							sku_id: sku_id,
							quantity: quantity
						});
							
						if(quantity <= 0) {
							$.bundlingFrontend.updateSelectedProducts({
								action: '-',
								bundle: bundle_id,
								product_id: selected_product_id
							});
							$('#bundling-select-' + selected_bundle_id).find('select option[value=' + selected_product_id + ']').show();
							wrapper.remove();
						}
							
						$.bundlingFrontend.changePrice();
					});
					
					selected.hide();
					$(this).val('');
						
					new_wrapper.show();
					$.bundlingFrontend.changePrice();
				}
			} else {
				$.bundlingFrontend.updateSelectedProducts({
					action: '-',
					bundle: bundle_id
				});
				
				$.bundlingFrontend.changePrice();
					
				$(this).closest('tr').next('.bundling-about-selected-product').hide();
			}
		}).closest('tr').find('input').on('keyup mouseup change', function() {
			$.bundlingFrontend.updateSelectedProducts({
				action: 'quantity',
				bundle: $(this).data('bundle-id'),
				quantity: parseInt($(this).val())
			});
				
			$.bundlingFrontend.changePrice();
		});

		if(typeof cb === 'function')
			cb();
	}
})(jQuery);
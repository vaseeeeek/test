'use strict';

(function($) {
	$.bundling = {
		dialog: function() {
			let products = $.product_list.getSelectedProducts(true);
			if (!products.count) {
				alert($_('Please select at least one product'));
			} else {
				$.bundlingDialog = $('#bundling-dialog').waDialog({
					width: 800,
					height: 360,
					url: '?plugin=bundling&module=dialog&action=choose&' + $.param(products.serialized),
					disableButtonsOnSubmit: true,
					onClose: function() {
						$(this).remove();
					}
				});
			}
		},
		removeAll: function (confirmation) {
			let products = $.product_list.getSelectedProducts(true);
			if (!products.count) {
				alert($_('Please select at least one product'));
			} else {
				if (confirm(confirmation)) {
					$.post('?plugin=bundling&module=dialog&action=removeAll&' + $.param(products.serialized), function () {
						$('#s-product-list-skus-container').hide();
						$.products.dispatch();
					}, 'json');
				}
			}
		},
		editProductBundles: function() {
			$.bundlingDialog.trigger('close');
			
			let products = $.product_list.getSelectedProducts(true);
			if (!products.count) {
				alert($_('Please select at least one product'));
			} else {
				$('#bundling-dialog').waDialog({
					url: '?plugin=bundling&module=dialog&action=editProductBundles&' + $.param(products.serialized),
					buttons: '<input id="bundling-submit" type="submit" value="' + $_('Save') + '" class="button green"/> ' + $_('or') + ' <a href="#" class="cancel">' + $_('close') + '</a>',
					disableButtonsOnSubmit: true,
					onSubmit: function(d) {
						$.post('?plugin=bundling&module=massEditProductBundles&' + $.param(products.serialized), $(this).serialize(), function() {
							d.trigger('close');
						}, 'json');
						
						return false;
					},
					onClose: function() {
						$(this).remove();
					}
				});
			}
		}
	}
})(jQuery);

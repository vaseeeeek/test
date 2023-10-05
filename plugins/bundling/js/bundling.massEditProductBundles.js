'use strict';

$.bundlingMassEditProductBundles = {
	init: function() {
		this.autocomplete();
		$(document).off('click', '.bundling-bundle-products ul.actions a').on('click', '.bundling-bundle-products ul.actions a', function(e) {
			let action = $(this).data('action');
			
			if(!$(this).parent().hasClass('disabled')) {
				if(action == 'delete' && $(this).data('insta')) {
					let product_ids_wrapper = $(this).closest('div.value').find('input[name=product_ids]');
					let product_ids = product_ids_wrapper.val().split(',');
					product_ids.splice(product_ids.indexOf($(this).data('insta').toString()), 1);
					product_ids_wrapper.val(product_ids.join(','));
					$(this).closest('tr').remove();
				} else {
					$(this).closest('ul.actions').find('li').removeClass('selected');
					$(this).closest('tr').find('input.bundle-product-action').val(action);
					$(this).parent().addClass('selected');
					
					$(this).closest('tr').attr('class', action);
					
					$(this).closest('.field.bundle').find('input[type=checkbox]').attr('checked', true);
				}
			}
			
			e.preventDefault();
		});
		
		$(document).off('click', '.bundling-edit-discount-link').on('click', '.bundling-edit-discount-link', function(e) {
			$(this).closest('td').find('.bundling-edit-discount').toggle();
			
			e.preventDefault();
		});
	},
	autocomplete: function(elem = '.product-autocomplete') {
		let autocompletes = $(elem).autocomplete({
			source: '?plugin=bundling&module=autocomplete&with_sku_name=true',
			minLength: 3,
			delay: 300,
			select: function(event, ui) {
				let bundle_id = $(this).data('bundle-id');
				let product_ids_wrapper = $(this).closest('div.value').find('input[name=product_ids]');
				let product_ids = product_ids_wrapper.val().split(',');
				if(product_ids.indexOf(ui.item.id) == -1) {
					let tr = $(`<tr class="all" id="bundling-bundle-${bundle_id}-product-${ui.item.id}"/>`);
					tr.append($('<td/>').html(ui.item.label))
					.append($('<td width="200" align="right"/>').append(
						$('<div align="right"/>').html(`<a class="bundling-edit-discount-link" href="#">${ui.item.price_html}</a> × <input title="${$_('Default quantity')}" class="short" name="default_quantity[${bundle_id}][${ui.item.id}]" type="text" value="1"/>`)
					).append(
						$('<div align="right" class="bundling-edit-discount" style="margin-top: 5px; display: none;"/>').html(`<b class="red">-</b> <input style="min-width: 43px !important; width: 43px !important;" placeholder="${$_('Discount')}, %" class="short" name="discount[${bundle_id}][${ui.item.id}]" type="text" value=""/> <b class="red">%</b>`)
					))
					.append($('<td width="180" align="right"/>').html($_('New')));
					tr.append($('<td align="right" width="300"/>')
						.append(`<input class="bundle-product-action" type="hidden" name="bundles[${bundle_id}][${ui.item.id}]" value="all"/>`)
						.append($('<ul class="actions menu-h"/>')
							.append($('<li class="disabled"/>').html(`<a>${$_('Leave')}</a>`))
							.append($('<li class="selected"/>').html(`<a href="#" data-action="all">${$_('Set to all')}</a>`))
							.append($('<li/>').html(`<a href="#" data-action="delete" data-insta="${ui.item.id}">${$_('Delete')}</a>`))));
					tr.insertBefore($(this).closest('tr'));
					
					product_ids.push(ui.item.id);
					product_ids_wrapper.val(product_ids.join(','));
					
					$(`#bundling-bundle-${bundle_id} input[type=checkbox]`).attr('checked', true);
				} else
					$('.actions li a[data-action="all"]', `#bundling-bundle-${bundle_id}-product-${ui.item.id}`).trigger('click');

				let autocomplete = $(this).data('autocomplete');
				autocomplete.do_not_close_autocomplete = 1;
				window.setTimeout(function() {
					autocomplete.do_not_close_autocomplete = false;
					autocomplete.menu.element.position($.extend({
						of: autocomplete.element
					}, autocomplete.options.position || { my: "left top", at: "left bottom", collision: "none" }));
				}, 0);

				return false;
			}
		});

		autocompletes.each(function() {
			let autocomplete = $(this).data('autocomplete');
			let oldClose = autocomplete.close;
			autocomplete.close = function(e) {
				if(this.do_not_close_autocomplete) {
					return false;
				}
				oldClose.apply(this, arguments);
			};
		});
	}
}
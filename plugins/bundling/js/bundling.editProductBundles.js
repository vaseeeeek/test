'use strict';

(function($) {
	$(document).on('click', '#bundling-add-bundle-button', function(e) {
		let product_id = $(this).data('product-id');
		$(this).attr('disabled', true);
		$('#bundling-bundles').append($('<div class="field" id="bundling-new-bundle"/>')
			.append($('<div class="name"/>')
				.append($('<i class="icon16 folder"/>'))
				.append($('<input id="bundling-new-bundle-title"/>').css({width: 130, float: 'right'}).attr('placeholder', $_('Type of accessory')))
				.append($('<label/>').css({display: 'block', marginTop: '10px'})
					.append(`<input id="bundling-new-bundle-multiple" type="checkbox"/> ${$_('Multiple select')}`)))
			.append($('<div class="value"/>').html(`<a href="#" class="bundling-new-bundle-button" data-action="save" data-product-id="${product_id}">${$_('Save')}</a> ${$_('or')} <a href="#" class="bundling-new-bundle-button" data-action="cancel">${$_('cancel')}</a>`)))
			.find('#bundling-new-bundle-title').keypress(function(e) {
				if(e.keyCode == 13) {
					$('.bundling-new-bundle-button[data-action="save"]').trigger('click');
					return false;
				}
			}).focus();
			
		e.preventDefault();
	});

	$(document).on('click', '.bundling-new-bundle-button', function(e) {
		let action = $(this).data('action');
		let product_id = $(this).data('product-id');
		switch(action) {
			case 'cancel':
				break;
			case 'save':
				let title = $('#bundling-new-bundle-title').val();
				let multiple = $('#bundling-new-bundle-multiple').is(':checked');
				if(title.length < 3) {
					alert($_('Min length of title is 3 symbols!'));
					return false;
					e.preventDefault();
				}
				
				$.post('?plugin=bundling&module=createBundle', {
					product_id: product_id,
					title: title,
					multiple: multiple ? 1 : 0
				}, function(data) {
					let id = data.data.id;
					let sort = data.data.sort;
					multiple = multiple ? 1 : 0;
					let checked_if_multiple = multiple ? ' checked' : '';
					$('#bundling-bundles').append($(`<div class="field bundle" id="bundling-bundle-${id}" data-bundle-id="${id}"/>`)
						.append($('<div class="name"/>')
							.append($('<i class="icon16 folder"/>'))
							.append($('<span/>').text(title))
							.append($(`<input name="sort[${id}]" class="bundling-bundle-sort" type="hidden" value="${sort}"/>`))
							.append($('<i class="icon16 sort count"/>'))
							.append($('<label/>').css({display: 'block', marginTop: '10px'})
								.html(`<input class="bundling-bundle-multiple" type="checkbox" disabled="" data-checked="${multiple}" ${checked_if_multiple}> ${$_('Multiple select')}`))
							.append($('<div class="bundling-bundle-actions hint"/>').css({marginTop: '10px'})
								.append($('<div class="actions"/>')
									.append(`<a href="#" class="bundling-bundle-edit" data-bundle-id="${id}"><i class="icon10 edit"></i> ${$_('edit')}</a>`)
									.append(` ${$_('or')} `)
									.append(`<a href="#" class="bundling-bundle-delete" data-product-id="${product_id}" data-bundle-id="${id}"><i class="icon10 delete"></i> ${$_('delete')}</a>`))
								.append($('<div class="edit-actions"/>').css({display: 'none'})
									.append(`<a href="#" class="bundling-bundle-edit-actions" data-bundle-id="${id}" data-action="save"><i class="icon10 yes"></i> ${$_('save')}</a>`)
									.append(` ${$_('or')} `)
									.append(`<a href="#" class="bundling-bundle-edit-actions" data-bundle-id="${id}" data-action="cancel"><i class="icon10 no"></i> ${$_('cancel')}</a>`))))
						.append($('<div class="value"/>')
							.append($('<div class="bundling-various-discount"/>').css({display: 'none'}).html(`<b>${$_('Discount')}</b> <input class="short" type="number"/> % ${$_('for chosing any product from this bundle')}`))
							.append($('<div class="bundling-fixed-discount"/>').css({display: 'none'}).html(`<label><input type="checkbox"/> ${$_('Customer have to chose product from this bundle to get fixed discount')}</label>`))
							.append($('<table class="related zebra"/>')
								.append($('<tbody/>')
									.append($('<tr/>')
										.append($('<td colspan="5"/>')
											.append(`<input class="product-autocomplete long ui-autocomplete-input" id="bundling-autocomplete-${id}" data-bundle-id="${id}" type="text" placeholder="${$_('Start typing product or SKU name')}" autocomplete="off" role="textbox" aria-autocomplete="list" aria-haspopup="true">`)))))));
											
					$.bundlingEditProductBundles.autocomplete(`#bundling-autocomplete-${id}`);
					$.bundlingEditProductBundles.updateDiscountSettings();
				});
				break;
		}
		
		$('#bundling-new-bundle').remove();
		$('#bundling-add-bundle-button').removeAttr('disabled');
		e.preventDefault();
	});
	
	$(document).on('click', '.bundling-bundle-delete', function(e) {
		let product_id = $(this).data('product-id');
		let bundle_id = $(this).data('bundle-id');
		
		if(confirm($_('Delete selected bundle?'))) {
			$.post('?plugin=bundling&module=deleteBundle', {
				product_id: product_id,
				bundle_id: bundle_id,
				_csrf: $('#bundling-form input[name="_csrf"]').val()
			}, function(data) {
				if(data.status == 'fail')
					alert(data.errors[0][0]);
				else
					$(`#bundling-bundle-${bundle_id}`).remove();
			});
		}
		
		e.preventDefault();
	});
	
	$(document).on('click', '.bundling-bundle-edit', function(e) {
		let bundle_id = $(this).data('bundle-id');
		let bundle = $(`#bundling-bundle-${bundle_id}`);
		let title = $('.name span', bundle);
		let actions = $('.bundling-bundle-actions', bundle);
		let multiple = $('.bundling-bundle-multiple', bundle);
		
		multiple.removeAttr('disabled');
		title.hide().after($(`<input id="bundling-bundle-edit-${bundle_id}"/>`).css({width: 100}).val(title.text()));
		$(`#bundling-bundle-edit-${bundle_id}`).focus().keypress(function(e) {
			if(e.keyCode == 13) {
				$(this).parent().find('.bundling-bundle-edit-actions[data-action="save"]').trigger('click');
				return false;
			}
		});
		$('.actions, .edit-actions', actions).toggle();
		
		e.preventDefault();
	});
	
	$(document).on('click', '.bundling-bundle-edit-actions', function(e) {
		let bundle_id = $(this).data('bundle-id');
		let bundle = $(`#bundling-bundle-${bundle_id}`);
		let action = $(this).data('action');
		let title = $('.name span', bundle);
		let input = $(`#bundling-bundle-edit-${bundle_id}`);
		let actions = $('.bundling-bundle-actions', bundle);
		let multiple = $('.bundling-bundle-multiple', bundle);

		function returnDefaultBundleView() {
			title.show();
			input.remove();
			$('.actions, .edit-actions', actions).toggle();
			multiple.attr('disabled', true);
		}
		
		if(action == 'save') {
			if(input.val().length < 3) {
				alert($_('Min length of title is 3 symbols!'));
				return false;
				e.preventDefault();
			}
		
			$.post('?plugin=bundling&module=editBundle', {
				bundle_id: bundle_id,
				title: input.val(),
				multiple: multiple.is(':checked') ? 1 : 0
			}, function(data) {
				title.text(input.val());
				returnDefaultBundleView();
			});
		} else {
			multiple.prop('checked', multiple.data('checked') == 1);
			returnDefaultBundleView();
		}
		
		e.preventDefault();
	});
	
	$(document).on('click', '.bundle table a.delete', function(e) {
		if(confirm($_('Delete selected product from list?'))) {
			let tr = $(this).closest('tr');
			tr.find('input').remove();
			$.bundlingEditProductBundles.save(function() {
				tr.remove();
			});
		}
		
		e.preventDefault();
	});
	
	$(document).on('submit', '#bundling-form', function() {
		$.bundlingEditProductBundles.save();
		return false;
	});
	
	$(document).on('change', '#bundling-give-discount', function() {
		$.bundlingEditProductBundles.updateDiscountSettings($(this).val());
	});
	
	$(document).on('click', 'a.bundling-set-discount', function(e) {
		let td = $(this).closest('td');
		
		if($(this).hasClass('has')) {
			td.find('.discount').toggle();
		}
		
		td.find('.edit-discount').toggle().find('input').focus();
		
		e.preventDefault();
	});
})(jQuery);

$.bundlingEditProductBundles = {
	product_id: 0,
	updateDiscountSettings: function(type = false) {
		let onChange = type !== false;
		if(type === false)
			type = $('#bundling-give-discount').val();
		
		$('#bundling-give-discount-various-combiner, #bundling-give-discount-fixed, .bundling-various-discount, .bundling-fixed-discount').hide();
		switch(type) {
			case 'fixed':
				$('#bundling-give-discount-fixed').show();
				$('.bundling-fixed-discount').show();
				if(onChange) {
					$('#bundling-give-discount-fixed').find('input').focus();
					$('.bundling-fixed-discount').find('input').prop('checked', true);
				}
				break;
			case 'various':
				$('#bundling-give-discount-various-combiner, .bundling-various-discount').show();
				break;
		}
	},
	formatPrice: function(format, price) {
		return format.replace(/0/, (parseFloat(price.toFixed(2))).toLocaleString('ru').replace('.', ','));
	},
	save: function(after) {
		$('#product-save-message').html($('<i class="icon16 loading"/>'));
		let data = $('#bundling-form').serialize();

		$.post($('#bundling-form').attr('action'), data, function(data) {
			$('#product-save-message').html($('<i class="icon16 ' + (data.status == 'ok' ? 'yes' : 'no') + '"/>'));

			setTimeout(function() {
				$('#product-save-message').html('');
			}, 3000);
			
			if(data.status == 'fail') {
				alert(data.errors[0][0]);
				return false;
			} else {
				$('.edit-discount input:visible').each(function() {
					let td = $(this).closest('td');
					let a = td.find('a.bundling-set-discount');
					let discount = parseInt(td.find('input').val());
					
					if(!discount)
						discount = 0;
					
					td.find('input').val(discount ? discount : '');
					let default_price = a.data('default-price');

					let ed = td.find('.edit-discount');
					ed.hide();
					a.show();
					
					if(discount) {
						let price = default_price * (100 - discount) / 100;
						
						if(a.hasClass('has')) {
							td.find('.discount').html(`-${discount}%`).show();
						} else {
							ed.before(' ').before($('<s class="default-price gray"/>').html(a.find('i').html())).before(' ').before($('<b class="discount" style="color: red;"/>').html(`-${discount}%`));
						}
						
						a.addClass('has').find('i').html($.bundlingEditProductBundles.formatPrice(td.find('.format').html(), price));
					} else {
						a.removeClass('has').find('i').html($.bundlingEditProductBundles.formatPrice(td.find('.format').html(), default_price));
						td.find('.default-price, .discount').remove();
					}
				});
				
				if(after)
					after();
				
				return true;
			}
		});
	},
	autocomplete: function(elem = '.product-autocomplete') {
		let autocompletes = $(elem).autocomplete({
			source: '?plugin=bundling&module=autocomplete&with_sku_name=true',
			minLength: 3,
			delay: 300,
			select: function(event, ui) {
				if(ui.item.product_id != $.bundlingEditProductBundles.product_id) {
					let is_category_groups = $(this).hasClass('category-bundle-groups');

					let bundle_id = $(this).data('bundle-id');
					if(is_category_groups) {
						bundle_id = ui.item.category_id * -1;
						$(`#bundling-bundle-${bundle_id}`).show();
					}
					
					let product_ids = [];
					$(`#bundling-bundle-${bundle_id} table tr`).each(function() {
						let sku_count = parseInt($(this).data('sku-count'));
						if(sku_count > 1)
							$(this).data('product-id') && $(this).data('sku-id') && product_ids.push($(this).find('input').val());
						else
							$(this).data('product-id') && product_ids.push($(this).find('input').val());
					});
					
					let id = ui.item.id;
					if(product_ids.indexOf(id) == -1) {
						let max_sort = 0;
						$(`#bundling-bundle-${bundle_id} .bundling-products`).find('.bundling-product-sort-key').each(function() {
							if(parseInt($(this).val()) > max_sort)
								max_sort = parseInt($(this).val());
						});
						
						let tr = $(`<tr id="bundling-${bundle_id}-${ui.item.product_id}" class="p" data-product-id="${ui.item.product_id}" data-sku-id="${ui.item.sku_id}" data-sku-count="${ui.item.sku_count}"></tr>`);
						let title = $('<div>').text(ui.item.name ? ui.item.name : ui.item.value).html();
						if(ui.item.sku_count > 1)
							title += ' - <b>' + $('<div>').text(ui.item.sku_name).html() + '</b>';
						tr.append($('<td/>').html(`<input name="bundle[${bundle_id}][products][]" type="hidden" value="${id}"/>` + ui.item.stock_status_icon + ' ' + title + (ui.item.sku_count ? ` <span class="gray">sku id ${ui.item.sku_id}</span>` : '')));
						tr.append($('<td align="right" width="100"/>').html(ui.item.price_html));
						tr.append('<td class="min-width">×</td>');
						tr.append($('<td align="right" width="60"/>').html(`<input title="${$_('Default quantity')}" class="short" name="bundle[${bundle_id}][quantities][${ui.item.product_id}]" type="text" value="1"/>`));
						tr.append(`<td align="right" width="50"><input class="bundling-product-sort-key" type="hidden" name="bundle[${bundle_id}][sort][${ui.item.product_id}]" value="${max_sort+1}"><a class="bundling-product-sort" href="#"><i class="icon16 sort"></i></a> <a class="delete" href="#"><i class="icon16 delete"></i></a></td>`);
						
						if(is_category_groups) {
							$(`#bundling-bundle-${bundle_id} .bundling-products`).append(tr);
						} else {
							tr.insertBefore($(this).closest('tr'));
						}
						
					$.bundlingEditProductBundles.save();
					}
				}

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
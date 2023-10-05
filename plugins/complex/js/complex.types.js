$.complexTypes = {
	saveRulesCallback: function(price_type_id, r, save) {
		if(r.status == 'ok') {
			var data = r.data;
			var id = data.id;
			var output = data.output;
			
			$('.c-price-types-rule-input', '#c-price-type-' + price_type_id).val(id);
			$('.c-price-types-rule-output', '#c-price-type-' + price_type_id).html(output).show();
			$('.c-price-types-rules-link', '#c-price-type-' + price_type_id).data('rule-id', id);
			$('.c-price-types-rules-link .label', '#c-price-type-' + price_type_id).html('Редактировать правила применения');

			$.wa.dialogHide();
		} else {
			if(typeof r == 'string' && r.indexOf('Parse error') != -1)
				r = {
					status: 'fail',
					errors: [[r.substr(r.indexOf(':') + 1, r.substr(r.indexOf(':') + 1).indexOf(' in <b>')).trim(), 'syntax-error']]
				};

			if(typeof r == 'string' && r.indexOf('Fatal error') != -1)
				r = {
					status: 'fail',
					errors: [[r.substr(r.indexOf(':') + 1, r.substr(r.indexOf(':') + 1).indexOf(' in <b>')).trim(), 'fatal-error']]
				}

			var error_str = r.errors[0][0];
			var error_params = r.errors[0][1].indexOf(':') != -1 ? r.errors[0][1].split(':') : r.errors[0][1];
			var error_key = r.errors[0][1].indexOf(':') != -1 ? error_params[0] : error_params;

			switch(error_key) {
				case 'field-is-empty':
				case 'field-can-only-be-integer':
				case 'field-smarty-compiler-error':
				case 'compare-style-is-not-correct':
					var field = $('[name$="[' + error_params[1] + '][' + error_params[2] + ']"]');
					if(field.hasClass('selectized')) {
						var selectize = field[0].selectize;
						selectize.$wrapper.addClass('error');
						selectize.on('focus', function() {
							$(this)[0].$wrapper.removeClass('error');
						});
					} else
						field.focus();
					break;
			}

			save.find('.error').show().find('span').html(error_str);
		}
	},
	assignButton: function(elem, options) {
		$(elem).each(function() {
			$(this).iButton({
				labelOn: '',
				labelOff: '',
				resizeHandle: true,
				resizeContainer: true,
				classContainer: 'ibutton-container mini'
			}).change(function() {
				if(!$(this).hasClass('c-available-prices-button')) {
					var id = $(this).closest('tr').data('id');

					$.post('?plugin=complex&action=toggleStatus', {
						id: id,
						status: this.checked ? 1 : 0
					});

					$(this).closest('div.s-ibutton-checkbox').find('span.status')
						.html(this.checked ? $_('On') : $_('Off')).toggleClass('s-off');
					$(this).closest('tr')[this.checked ? 'removeClass' : 'addClass']('hide');
				}
			});
		});
	}
};
	
(function($) {
	$.complexTypes.assignButton('.i-button-mini');
	
	var rulesDialog = function(price_type_id, id, callback) {
		$.get('?plugin=complex&action=rulesDialog&selected=' + id, function(data) {
			var classes = '';
			var params = {
				content: data,
				buttons: $('<div/>').append($('<input type="submit" class="button green" value="' + $_('Save rules') + '">').click(function() {
					var dat = $(this);
					var save = dat.closest('.dialog-buttons');
					save.find('.error').hide().find('span').html('');
					
					if(!dat.hasClass('loading')) {
						dat.addClass('loading');

						var f = $('#complex-rule-form');
						var f_data = f.serializeArray();
						
						if(f.data('form-action') == 'edit') {
							for(key in f_data) {
								if($('[name="' + f_data[key]['name'] + '"]', f).closest('.condition').hasClass('removed')) {
									delete f_data[key];
								}
							}
						}
						
						$.post(f.attr('action'), f_data, function(r) {
							dat.removeClass('loading');

							$.complexTypes.saveRulesCallback(price_type_id, r, save);
						}, 'json');
					}
				})).append(' ' + $_('or') + ' ').append($('<a href="javascript: //">' + $_('cancel') + '</a>').click(function() { $.wa.dialogHide(); })).append(' <div class="error" style="display: none;"><i class="icon16 no"></i><span></span></div>')
			};

			var dialog = $.wa.dialogCreate('complex-rules', params).addClass('complex ' + classes);
			
			if(typeof callback == 'function')
				callback(dialog);

			$.wa.waCenterDialog(dialog);
			
			if(id != 0) {
				var f = $('#complex-rule-form');
				var r = f.find('.rule-conditions');

				var select = r.find('.selectize.select-control').addClass('select-condition').data('use-as-dat', 1);
				select.selectize();
						
				$.complexRules.selectChangeHandler(select, null, null);
				if(r.find('.selectize.feature_key').length)
					$.complexRules.selectChangeFeatureKeyHandler(r.find('.selectize.feature_key'));
						
				$.complexRules.selectizeAll();
			}

			return dialog;
		});
	};
	
	$('#c-price-types-add a').click(function(e) {
		$(this).closest('p').hide();
		$('#c-price-types-table').show();
		$('#c-price-types-table .add').show();
		
		e.preventDefault();
	});
	
	$(document).off('change', '.c-price-types-default-style').on('change', '.c-price-types-default-style', function() {
		var d = $(this).closest('div').find('div');
		d[$(this).val() == '0' ? 'hide' : 'show']();
	});
	
	$(document).off('click', '.c-price-types-rules-link').on('click', '.c-price-types-rules-link', function(e) {
		var i = $(this).find('i.icon16');
		if(!i.hasClass('loading')) {
			i.addClass('loading');
			rulesDialog($(this).closest('tr').data('id'), $(this).data('rule-id'), function(d) {
				i.removeClass('loading');
			});
		}
		
		e.preventDefault();
	});
	
	$(document).off('click', '.c-price-type-helpers').on('click', '.c-price-type-helpers', function(e) {
		var id = $(this).data('id');
		
		var dialog = $.wa.dialogCreate('complex-helper', {
			content: '<h1>' + $_('Helpers') + '</h1><p>' + $_('To display "complex" prices together with main one, you can use special helpers') + '.</p><p><b class="variable">{shopComplexPlugin::price(\'' + id + '\', $product)}</b> &ndash; ' + $_('will return price of the main sku, or nothing, if there is no') + '</p><p><b class="variable">{shopComplexPlugin::price(\'' + id + '\', $product, \'format\')}</b> &ndash; ' + $_('will return formatted price of the main sku, or nothing, if there is no') + '</p><p><b class="variable">{shopComplexPlugin::skuPrice(\'' + id + '\', $sku)}</b> &ndash; ' + $_('will return price for selected sku, or nothing, if there is no') + '</p><p><b class="variable">{shopComplexPlugin::skuPrice(\'' + id + '\', $sku, \'format\')}</b> &ndash; ' + $_('will return formatted price for selected sku, or nothing, if there is no') + '</p><p><b class="variable">{shopComplexPlugin::algorithmStatus(\'' + id + '\', $product)}</b> &ndash; ' + $_('returns the status of algorithm to use "complex" prices') + ':<br/>0 &ndash; ' + $_('algorithm is enabled and will change the price in accordance with the established conditions') + ';<br/>-1 &ndash; ' + $_('algorithm is disabled, product will ALWAYS use main price (also returned if the complex price is disabled)') + ';<br/>1 &ndash; ' + $_('algorithm is disabled, product will ALWAYS use "complex" price') + '.</p><p><b class="variable">{shopComplexPlugin::isEnabled(\'' + id + '\')}</b> &ndash; ' + $_('is enabled selected "complex" price') + '</p><p><b class="variable">{shopComplexPlugin::checkConditions(\'' + id + '\', $product)}</b> &ndash; ' + $_('are the conditions met for this product') + '</p><p><b class="variable">{shopComplexPlugin::getPriceName(\'' + id + '\')}</b> &ndash; ' + $_('price name') + '</p>',
			buttons: $('<input type="submit" class="button" value="' + $_('Close') + '">').click(function() { $.wa.dialogHide(); })
		}).addClass('complex width500px height300px');

		$.wa.waCenterDialog(dialog);
		
		e.preventDefault();
	});
	
	$('.c-price-types-save').click(function(e) {
		var dat = $(this);
		var tr = dat.closest('tr');
		
		if(!dat.hasClass('loading')) {
			dat.addClass('loading');
			
			var data = tr.find('input, select').serializeArray();
			var form_action = tr.hasClass('add') ? 'new' : 'edit';
			
			$.post('?plugin=complex&action=' + form_action, data, function(r) {
				if(r.status == 'ok') {
					$('#c-price-type-0').before(r.data.output);
					$.complexTypes.assignButton('.i-button-mini');
					tr.find('.c-price-types-rules-link').data('rule-id', 0).find('.label').html($_('Set rules for using this price'));
					tr.find('input[name="name"]').val('');
					tr.find('.c-price-types-rule-input').val('0');
					tr.find('.c-price-types-rule-output').html('').hide();
					
					$('#c-price-type-0').hide();
					$('#c-price-types-add').show();
					
					dat.removeClass('loading');
				} else {
					alert(r.errors[0][0]);
					dat.removeClass('loading');
				}
			}, 'json');
		}
		
		e.preventDefault();
	});
	
	$('.c-price-types-cancel').click(function(e) {
		$('#c-price-types-add').show();
		$('#c-price-types-table .add').hide();
		if($('#c-price-types-table tr').length == 2)
			$('#c-price-types-table').hide();
	
		e.preventDefault();
	});
	
	$(document).off('click', '.c-price-type-edit-buttons a').on('click', '.c-price-type-edit-buttons a', function(e) {
		var tr = $(this).closest('tr');
		var action = $(this).data('action');
		
		switch(action) {
			case 'edit':
				tr.find('.view').hide();
				tr.find('.edit').each(function() {
					$(this).show().clone().addClass('default').hide().insertBefore(this);
				});
				
				$('.c-price-type-edit-buttons, .c-price-type-save-buttons', tr).toggle();
				
				break;
			case 'delete':
				var dialog = $.wa.dialogCreate('complex-delete-price-type', {
					content: '<h1>' + $_('Delete price') + '</h1><p>' + $_('If you delete this price, then <strong>ALL</strong> existing prices for products will be deleted at the same time.</p><p>This action can\'t be undone. Are you sure you want to do this?') + '</p>',
					buttons: $('<div/>').append($('<input type="submit" class="button delete" value="' + $_('Permanently delete') + '">').click(function() {
					var dat = $(this);
					if(!dat.hasClass('loading')) {
						dat.addClass('loading');
						
						$.post('?plugin=complex&action=delete', {
							id: tr.data('id')
						}, function(r) {
							if(r.status == 'ok') {
								tr.remove();
								$.wa.dialogHide();
							} else {
								dat.removeClass('loading');
								alert(r.errors[0][0]);
							}
						}, 'json');
					}
				})).append(' ' + $_('or') + ' ').append($('<a href="javascript: //">' + $_('cancel') + '</a>').click(function() { $.wa.dialogHide(); }))
				}).addClass('no-overflow width450px height200px');

				$.wa.waCenterDialog(dialog);
				
				break;
		}
		
		e.preventDefault();
	});
	$(document).off('click', '.c-price-type-save-buttons a').on('click', '.c-price-type-save-buttons a', function(e) {
		var dat = $(this);
		var action = dat.data('action');
		var tr = dat.closest('tr');
		
		switch(action) {
			case 'save':
				if(!dat.hasClass('loading')) {
					dat.addClass('loading');
					
					var data = tr.find('.edit:not(.default) input, .edit:not(.default) select').serializeArray();

					$.post('?plugin=complex&action=edit', data, function(r) {
						if(r.status == 'ok') {
							tr.hide();
							tr.after(r.data.output);
							tr.remove();
							$.complexTypes.assignButton('.i-button-mini');
						} else {
							alert(r.errors[0][0]);
							dat.removeClass('loading');
						}
					}, 'json');
				}
				
				break;
			case 'cancel':
				tr.find('.edit:not(.default)').remove();
				tr.find('.edit').removeClass('default').hide();
				tr.find('.view').show();
				
				$('.c-price-type-edit-buttons, .c-price-type-save-buttons').toggle();
				
				break;
		}
		
		e.preventDefault();
	});
	
	$('#c-transfer-no').click(function(e) {
		$('#complex_shop_complex_transfer').prop('checked', false);
		$('#c-transfer-block').hide();
		
		$.get('?plugin=complex&action=disableTransferBlock');
		
		e.preventDefault();
	});
	
	$('#c-price-types-table').sortable({
		handle: '.c-price-type-sort',
		items: '.c-price-type-tr',
		update: function(event, ui) {
			positions = $(this).sortable('toArray');
			sort = new Object();
			for(key in positions)
				sort[$('#' + positions[key]).data('id')] = key * 1 + 1;

			$.post('?plugin=complex&module=backend&action=saveSort', {
				sort: sort
			}, function(r) {
				
			}, 'json');
		}
	});
})(jQuery);
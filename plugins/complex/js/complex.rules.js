$.complexRules = {
	selectizeAll: function() {
		$('select.selectize:not(.selectized)').each(function() {
			$.complexRules.assignSelectize($(this));
		});
	},
	assignSelectize: function(dat) {
		dat.selectize({
			plugins: dat.data('plugins') ? dat.data('plugins').split(',') : []
		});
	},
	parseFeatureKey: function(value) {
		let matches = value.match(/^([0-9]+):(0|1):([a-z\.]+)$/);
		let feature = new Object();
		feature.id = matches[1]; feature.is_selectable = matches[2]; feature.type = matches[3];

		return feature;
	},
	removeConditionCallback: function(dat) {
		var tr = dat.closest('tr.condition');
		var rule = dat.closest('.rule');
		
		var action = tr.hasClass('new') ? 'remove' : 'toggleClass';

		tr.hasClass('new') ? (
			tr.next('tr.delimiter').remove() &&
			tr.remove()
		) : tr.toggleClass('removed');
		
		if(!tr.hasClass('new')) {
			var removed_conditions_wrapper = rule.find('[name="removed_conditions"]');
			var value = removed_conditions_wrapper.val() ? removed_conditions_wrapper.val().split(',') : [];
			
			if(!tr.hasClass('removed'))
				removed_conditions_wrapper.val(value.filter(e => e != tr.data('id')).join(','));
			else {
				value.push(tr.data('id'));
				removed_conditions_wrapper.val(value.join(','));
			}
		}
	},
	insertRegionsSelect: function(value, wrapper, select = false, default_value = false) {
		if(value) {
			wrapper.html($('<span class="small-load"/>'));
			
			$.getJSON('?plugin=complex&action=getRegions&country=' + value, function(data) {
				if(data.status == 'ok') {
					var regions_select = select || $('<select class="selectize"/>').attr('name', wrapper.data('name'));
					regions_select.append($('<option/>').append($('<option disabled/>')));
						$.when($.each(data.data, function(region_value_id, region_value_title) {
							regions_select.append($('<option/>').val(region_value_id).text(region_value_title));
						})).done(function() {
							wrapper.html(' ');
							regions_select.appendTo(wrapper);
							if(default_value)
								regions_select.val(default_value);
							regions_select.selectize();
							regions_select[0].selectize.focus();
						});
				} else {
					if(data.errors[0][0] != 'empty')
						alert(data.errors[0][0]);
					else
						wrapper.html('');
				}
			});
		} else {
			wrapper.html('');
		}
	},
	insertFeatureValueSelect: function(value, wrapper, select = false, default_value = false) {
		if(value) {
			wrapper.html($('<span class="small-load"/>'));
			var feature = this.parseFeatureKey(value);

			if(feature.is_selectable == 1) {
				$.getJSON('?plugin=complex&action=featureValues&feature_id=' + feature.id, function(r) {
					if(r.status == 'ok') {
						var feature_value_select = select || $('<select class="selectize"/>').attr('name', wrapper.data('name'));
						feature_value_select.append($('<option/>').append($('<option disabled/>')));
						$.when($.each(r.data, function(feature_value_id, feature_value_title) {
							feature_value_select.append($('<option/>').val(feature_value_id).text(feature_value_title));
						})).done(function() {
							wrapper.html(' ');
							feature_value_select.appendTo(wrapper);
							if(default_value)
								feature_value_select.val(default_value);
							feature_value_select.selectize();
							feature_value_select[0].selectize.focus();
						});
					} else
						alert(r.errors[0][0]);
				});
			} else {
				var feature_value_input = select || $('<input class="text"/>').attr('name', wrapper.data('name'));
				wrapper.html(' ');
				feature_value_input.appendTo(wrapper);
				feature_value_input.focus();
			}
		} else {
			wrapper.html('');
		}
	},
	insertShippingRatesSelect: function(value, wrapper, select = false, default_value = false) {
		if(value) {
			wrapper.html($('<span class="small-load"/>'));
			
			$.getJSON('?plugin=complex&action=getShippingRates&shipping_id=' + value, function(data) {
				if(data.status == 'ok') {
					var shipping_rate_select = select || $('<select class="selectize"/>').attr('name', wrapper.data('name'));
					shipping_rate_select.append($('<option/>'));
					$.when($.each(data.data, function(shipping_rate_id, shipping_rate_title) {
						shipping_rate_select.append($('<option/>').val(shipping_rate_id).text(shipping_rate_title));
					})).done(function() {
						wrapper.html(' ');
						shipping_rate_select.appendTo(wrapper);
						if(default_value)
							shipping_rate_select.val(default_value);
						shipping_rate_select.selectize();
						shipping_rate_select[0].selectize.focus();
					});
				} else
					wrapper.html('');
			});
		} else {
			wrapper.html('');
		}
	},
	addConditionCallback: function(dat, callback = null) {
		dat.find('i.icon16').attr('class', 'icon16 loading');
		var depth = dat.parents('.condition-group').size();
		
		$.get('?plugin=complex&action=addCondition&depth=' + depth, function(data) {
			var output = $(data);
			
			if(dat.closest('.add-condition').prev().hasClass('no-conditions'))
				dat.closest('.add-condition').prev().remove();
			
			dat.closest('.add-condition').before(output);
			
			dat.find('i.icon16').attr('class', 'icon16 add');
			
			var select = output.find('.selectize').addClass('select-condition');
			select.selectize();
			
			if(!callback)
				select[0].selectize.focus();
			
			$.complexRules.selectChangeHandler(select, dat, depth);
			
			if(callback)
				callback(select);
		});
	},
	selectChangeFeatureKeyHandler: function(select) {
		select.change(function() {
			$.complexRules.insertFeatureValueSelect($(this).val(), $(this).closest('.condition').find('.feature_value'));
		});
	},
	selectChangeHandler: function(select, dat, source_depth) {
		select.change(function() {
			if($(this).data('use-as-dat'))
				dat = $(this);
			
			rule = dat.closest('.rule');
			
			if(source_depth === null) {
				depth = $(this).data('depth');
			} else
				depth = source_depth;
			
				var wrapper = $(this).closest('.condition');
				
				if(wrapper.find('.value').find('input[name$="[control]"]').length) {
					
					var i = wrapper.find('.value').find('input[name$="[control]"]').attr('name').match(/conditions(\[[0-9]+\])?(\[([0-9]+)\])/)[3];
				} else {
					var i = (dat.closest('.conditions').find('.condition').length - 1) * (rule.hasClass('new') ? 1 : -1);

					if(!rule.hasClass('new'))
						i -= 2;
				}
				
				wrapper.find('.value').find('.field-output').remove();
				
				var field = $(this).val().split('|')[0];
				var type = $(this).val().split('|')[1];

				var value_fields = type.split(':');
				value_fields.push('control');
				value_fields.forEach(function(field_type) {
					var action = 'append';
					var field_output = '<b>' + $_('Control type') + ' ' + field_type + ' ' + $_('is not found') + '</b>';
					
					var _field_type = field_type.substr(0, 'compare'.length) == 'compare' ? 'compare' : field_type;
					var name = (depth > 1 ? ('group_conditions[' + dat.closest('.condition-group').data('group') + ']') : 'conditions') + '[' + i + '][' + _field_type + ']';

					switch(field_type) {
						case 'control':
							var control_value = field == 'group' ? ('group:' + i) : field;
							field_output = $('<input class="text" type="hidden"/>').val(control_value).attr('name', name);
							break;
						case 'group':
							if(depth == 1) {
								action = 'html';
								field_output = $('<div class="condition-group or" data-group="' + i + '"/>');
								field_output.append(
									$('<ul class="menu-h condition-mode"/>')
										.append('<input name="mode[' + i + ']" type="hidden" value="or"/>')
										.append('<li><a href="#" data-mode="and">' + $_('All conditions are met') + '</a></li>')
										.append('<li class="selected"><a href="#" data-mode="or">' + $_('At least one condition is met') + '</a></li>'))
									.append($('<div class="conditions"/>')
										.append($('<table class="conditions-table"/>')
											.append($('<tr class="no-conditions"/>').html('<td colspan="3">' + $_('Add conditions') + '</td>'))
											.append($('<tr class="add-condition"/>').html('<td colspan="3"><a href="#" class="inline-link bold"><i class="icon16 add"></i><b><i><strong>' + $_('Add condition') + '</strong></i></b></a></td>')))
									);
								} else
									field_output = $_('It is not possible to create a condition group inside an existing one');
							break;
						case 'categories':
						case 'types':
						case 'features':
						case 'feature_key':
						case 'shipping':
						case 'payment':
						case 'countries':
						case 'storefronts':
						case 'user_categories':
							field_output = $('<span class="small-load"/>').append($('<select class="' + field_type + '"/>').attr('name', name).hide());
							$.get('?plugin=complex&action=fieldValues&type=' + field_type, function(field_output_data) {
								var _select = field_output.removeClass('small-load').find('select').show();
								
								_select.html(field_output_data).addClass('selectize').selectize().change(function() {
									if(field == 'shipping' && field_type == 'shipping') {
										$.complexRules.insertShippingRatesSelect($(this).val(), wrapper.find('.rates'));
									}
									
									if(field_type == 'feature_key') {
										$.complexRules.insertFeatureValueSelect($(this).val(), wrapper.find('.feature_value'));
									}
									
									if(field_type == 'countries') {
										$.complexRules.insertRegionsSelect($(this).val(), wrapper.find('.regions'));
									}
								});
								
								_select[0].selectize.focus();
							});
							break;
						case 'regions':
						case 'rates':
						case 'feature_value':
							field_output = $('<span class="' + field_type + '"/>').data('name', name);
							break;
						case 'any':
							field_output = '';
							break;
						case 'input':
						case 'value':
						case 'key':
							field_output = $('<input class="text" type="text"/>').attr('name', name);
							break;
						default:
							if(field_type.substr(0, 'compare'.length) == 'compare') {
								if(field_type == 'compare')
									field_type = 'compare[!=;=;>=;<=;>;<]';
								
								var compare_field_values = field_type.substr('compare'.length + 1).slice(0, -1).split(';');
								var compare_field_select = $('<select class="selectize symbol"/>').attr('name', name);
								compare_field_select.append($('<option/>'));
								compare_field_values.forEach(function(compare_style) {
									compare_field_select.append($('<option value="' + compare_style + '"/>').html(compare_style == '!=' ? $_('NOT') : (compare_style == '==' ? $_('ALL') : compare_style)).attr('selected', (compare_style == '=' ? true : false)));
								});
								
								compare_field_select.change(function() {
									var input = $(this).closest('td.value').find('input[name$="[input]"]');
									if($(this).val() == '==')
										input.val('-1').hide();
									else
										input.val('').show().focus();
								});
								
								field_output = compare_field_select;
							}
							
							break;
					}
					
					wrapper.find('.value')[action]($('<span class="field-output"/>').append(field_output));
					$.complexRules.selectizeAll();
				});
				
				wrapper.find('.remove span').show();
			});
	}
};
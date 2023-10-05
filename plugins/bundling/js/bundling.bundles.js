'use strict';

(function($) {
	$(document).on('click', '.bundling-add-group-action-button', function(e) {
		let dat = this;
		let action = $(this).data('action');
		switch(action) {
			case 'add':
				let by = $(this).data('by');
				let group = $(this).data('id');
				let option = group ? group : $('#bundling-new-group-option').val();
				let feature_value = by == 'feature' ? (group ? group : $('#bundling-new-group-option-feature-values').val()) : null;
				let title = group ? $(this).closest('table').find('.add input[type=text]').val() : $(`#bundling-new-group-title`).val();
				let multiple = group ? $(this).closest('table').find('.add input[type=checkbox]').is(':checked') : $('#bundling-new-group-multiple').is(':checked');
				let subcategories = group ? 0 : $('#bundling-new-group-subcategories').is(':checked');
				if(option == 0 || !title || (by == 'feature' && !group && feature_value == 0)) {
					alert(!title ? $_('Set up the title of type of accessory') : $_('Select option to attach the accessory'));
					return false;
					e.preventDefault();
				}
				if(title.length < 3) {
					alert($_('Min length of title is 3 symbols!'));
					return false;
					e.preventDefault();
				}
			
				$.post('?plugin=bundling&module=addBundleGroup', {
					by: by,
					option: option,
					feature_value: feature_value,
					title: title,
					multiple: multiple ? 1 : 0,
					subcategories: subcategories ? 1 : 0
				}, function(data) {
					if(data.status == 'fail')
						alert(data.errors[0][0]);
					else {
						if(subcategories)
							location.reload();
						
						let id = data.data.id;
						let name = data.data.name;
						let title = data.data.title;
						if($(`#bundling-bundle-group-${option}`).length || $(`#bundling-bundle-group-${option}-${feature_value}`).length) {
							if($(`#bundling-bundle-group-${option}-${feature_value}`).length)
								option += '-' + feature_value;
							
							let table = group ? $(dat).closest('table') : $(`#bundling-bundle-group-${option}`);
							let new_group = $(`<tr id="bundling-bundle-${id}" class="bundling-bundle" data-id="${id}"/>`)
								.append($('<td/>')
									.append($('<i class="icon16 folder"/>'))
									.append($(`<input type="text" value="${title}"/>`))
									.append(' ')
										.append($('<label/>')
											.append($(`<input type="checkbox" class="bundling-bundle-multiple" id="bundling-bundle-multiple-${id}"/>`).prop('checked', multiple).data('checked', multiple ? 1 : 0))
											.append(` ${$_('Multiple select')}`)));
							let new_group_actions = $(`<tr id="bundling-bundle-actions-${id}" class="bundling-bundle-actions"/>`)
								.append($('<td class="hint actions"/>')
									.append($(`<a href="#" class="bundling-delete-bundle-button" data-bundle-id="${id}"><i class="icon10 delete"></i> ${$_('delete')}</a>`)))
								.append($('<td class="hint edit-actions"/>')
									.append($(`<a href="#" class="bundling-edit-action-button" data-action="save" data-id="${id}"><i class="icon10 yes"></i> ${$_('save')}</a> ${$_('or')} <a href="#" class="bundling-edit-action-button" data-action="cancel" data-id="${id}"><i class="icon10 no"></i> ${$_('cancel')}</a>`))
									.css({
										display: 'none'
									}));
							table.find('tr.add:first').before(new_group).before(new_group_actions);
						} else {
							$('#bundling-bundles')
								.append($('<h3/>').html(name))
								.append($('<div class="bundling-bundle-group"/>')
									.append($(`<table class="zebra bundling-bundle-group bundling-bundle-group-by-${by}" id="bundling-bundle-group-${option}"/>`)
										.append($(`<tr id="bundling-bundle-${id}" class="bundling-bundle" data-id="${id}"/>`)
											.append($('<td/>')
												.append($('<i class="icon16 folder"/>'))
												.append($(`<input type="text" value="${title}"/>`))
												.append(' ')
												.append($('<label/>')
													.append($(`<input type="checkbox" class="bundling-bundle-multiple" id="bundling-bundle-multiple-${id}"/>`).prop('checked', multiple).data('checked', multiple ? 1 : 0))
													.append(` ${$_('Multiple select')}`))))
										.append($(`<tr id="bundling-bundle-actions-${id}" class="bundling-bundle-actions"/>`)
											.append($('<td class="hint actions"/>')
												.append($(`<a href="#" class="bundling-delete-bundle-button" data-bundle-id="${id}"><i class="icon10 delete"></i> ${$_('delete')}</a>`)))
											.append($('<td class="hint edit-actions"/>')
												.append($(`<a href="#" class="bundling-edit-action-button" data-action="save" data-id="${id}"><i class="icon10 yes"></i> ${$_('save')}</a> ${$_('or')} <a href="#" class="bundling-edit-action-button" data-action="cancel" data-id="${id}"><i class="icon10 no"></i> ${$_('cancel')}</a>`))
												.css({
													display: 'none'
												})))
										.append($('<tr class="add"/>')
											.append($('<td/>')
												.append($_('New'))
												.append(' <i class="icon16 folder"/> ')
												.append($(`<input type="text" placeholder="${$_('Type of accessory')}"/>`))
												.append(' ')
												.append($('<label/>')
													.append('<input type="checkbox"/>')
													.append(` ${$_('Multiple select')}`)))
											.css({
												display: 'none'
											}))
										.append($('<tr class="add"/>')
											.append($('<td class="hint"/>')
												.append(`<a href="#" class="bundling-add-group-action-button" data-action="add" data-by="${by}" data-id="${option}"><i class="icon10 add"></i> ${$_('add')}</a>`)
												.append(` ${$_('or')} `)
												.append(`<a href="#" class="bundling-add-group-action-button" data-action="cancel" data-by="${by}" data-id="${option}"><i class="icon10 no"></i> ${$_('cancel')}</a>`))
											.css({
												display: 'none'
											})))
									.append($('<div class="hint"/>')
										.append(`<a class="bundling-add-group-for-this-button" data-id="${option}" href="#"><i class="icon10 add"></i>${$_('add')}</a>`)));
						}
						
						if(group)
							$(dat).parent().find('input[type=text]').val('');
						else {
							$(`#bundling-new-group-title`).val('');
							$(`#bundling-new-group-option`).val('');
						}
					}
				});
				break;
			case 'cancel':
				let id = $(this).data('id');
				$(this).closest('tr.add').hide().prev().hide();
				$(`a.bundling-add-group-for-this-button[data-id=${id}]`).show();
				break;
		}
		e.preventDefault();
	});
	
	$(document).on('click', '.bundling-add-group-for-this-button', function(e) {
		let id = $(this).data('id');
		$(`#bundling-bundle-group-${id} tr.add`).show();
		$(this).hide();
		e.preventDefault();
	});
	
	$(document).on('click', '.bundling-delete-bundle-button', function(e) {
		let bundle_id = $(this).data('bundle-id');
		if(confirm($_('Delete this bundle?')))
			$.post('?plugin=bundling&module=deleteBundle', {
				bundle_id: bundle_id
			}, function() {
				let table = $(`#bundling-bundle-${bundle_id}`).closest('table');
				$(`#bundling-bundle-${bundle_id}, #bundling-bundle-actions-${bundle_id}`).remove();
				if(table.find('tr').length == 2) {
					table.parent().prev().remove();
					table.parent().remove();
				}
			});
		
		e.preventDefault();
	});
	
	$(document).on('change', '.bundling-bundle-multiple', function() {
		let id = $(this).closest('tr').data('id');
		let actions = $(`#bundling-bundle-actions-${id}`);
		let input = $(this).closest('td').find('input[type=text]');
		
		$(this).data('changed', ($(this).data('checked') != ($(this).is(':checked') ? 1 : 0)));
			
		if($(this).data('changed')) {
			$('.actions', actions).hide();
			$('.edit-actions', actions).show();
		} else {
			if(input.val() == input.data('default-value')) {
				$('.actions', actions).show();
				$('.edit-actions', actions).hide();
			}
		}
	});
	
	$(document).on('focus blur', '.bundling-bundle input[type=text]', function(e) {
		let id = $(this).closest('tr').data('id');
		let actions = $(`#bundling-bundle-actions-${id}`);
		
		switch(e.type) {
			case 'focusin':
				if(!$(this).data('default-value'))
					$(this).data('default-value', $(this).val());
				$('.actions', actions).hide();
				$('.edit-actions', actions).show();
				break;
			case 'focusout':
				if($(this).val() == $(this).data('default-value') && !$(this).parent().find('input[type=checkbox]').data('changed')) {
					$('.actions', actions).show();
					$('.edit-actions', actions).hide();
				}
				break;
		}
	});
	
	$(document).on('change', '#bundling-new-group-option', function() {
		if($('#bundling-new-group-option-feature-values').length)
			$('#bundling-new-group-option-feature-values').remove();
		
		let by = $(this).data('by');
		let value = $(this).val();
		if(by == 'feature' && value != 0) {
			$(this).after(' <select id="bundling-new-group-option-feature-values"><option value="0"></option></select>');
			$.getJSON('?plugin=bundling&module=getFeatureValues', {
				id: value
			}, function(data) {
				for(let id in data.data) {
					let name = data.data[id];

					$('#bundling-new-group-option-feature-values')
						.append($('<option/>').attr('value', id).text(name));
				}
			});
		}
	});
	
	$(document).on('click', '.bundling-edit-action-button', function(e) {
		let id = $(this).data('id');
		let action = $(this).data('action');
		let actions = $(`#bundling-bundle-actions-${id}`);
		let input = $(`#bundling-bundle-${id} input[type=text]`);
		let multiple = $(`#bundling-bundle-multiple-${id}`);
		
		function returnDefaultBundleView() {
			$('.actions', actions).show();
			$('.edit-actions', actions).hide();
		}
		
		if(action == 'save') {
			$.post('?plugin=bundling&module=editBundle', {
				bundle_id: id,
				title: input.val(),
				multiple: multiple.is(':checked') ? 1 : 0
			}, function(data) {
				input.data('default-value', '');
				multiple.data('checked', multiple.is(':checked') ? 1 : 0).data('changed', false);
				returnDefaultBundleView();
			});
		} else {
			input.val(input.data('default-value'));
			returnDefaultBundleView();
		}
		
		e.preventDefault();
	});
	
	$(document).on('keypress', '.bundling-bundle input[type=text]', function(e) {
		let id = $(this).closest('tr').data('id');
		if(e.keyCode == 13)
			$('.bundling-edit-action-button[data-action="save"]', `#bundling-bundle-actions-${id}`).trigger('click');
	});
})(jQuery);
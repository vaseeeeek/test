/*
 * mail@shevsky.com
 */

'use strict';

(function($) {
	$.massupdating = {
		id: 1,
		dialog: function({
			module,
			action = '',
			buttons,
			on: {
				Load,
				Submit,
				Close
			},
			doAction = null,
			className = '',
			width = 900,
			height = 300
		}) {
			let products = $.product_list.getSelectedProducts(true);
			if (!products.count) {
				alert($_('Please select at least one product'));
			} else {
				$('.massupdating').addClass('loading');
				
				let htmlButtons = '';
				for(let key of Object.keys(buttons)) {
					let params = buttons[key];
					
					if(typeof params == 'string')
						key = params;
					
					switch(key) {
						case 'save':
							htmlButtons += '<input id="massupdating-submit" type="submit" value="' + $_(params.value ? params.value : 'Сохранить') + '" class="button ' + (params.class ? params.class : 'green') + '" ' + (params.disabled ? 'disabled' : '') + '/>';
							break;
						case 'cancel':
							htmlButtons += (params.or === false ? '' : $_('или') + ' ') + '<a href="#" class="cancel">' + $_(params.value ? params.value : 'Отмена') + '</a>';
							break;
						default:
							if(typeof params == 'object') {
								let unique = Math.round(new Date().getTime() + (Math.random() * 100));
								htmlButtons += '<input id="massupdating-dialog-' + module + '-' + key + '-button-' + unique + '" type="button" value="' + $_(params.value) + '" class="button ' + (params.class ? params.class : '') + '" ' + (params.disabled ? 'disabled' : '') + '/>';
								$(document).on('click', '#massupdating-dialog-' + module + '-' + key + '-button-' + unique, function() {
									params.onClick(products);
								});
							}
							
							break;
					}
					
					htmlButtons += ' ';
				}
				
				$.post('?plugin=massupdating&module=' + module + (action ? ('&action=' + action) : '') + (doAction ? ('&do=' + doAction) : ''), products.serialized, function(data) {
					$('.massupdating').removeClass('loading');
					
					if(data == 'error' || data == 'types_error')
						alert($_(data == 'error' ? 'Please select at least one product' : 'Для этого пользователя доступ запрещен'));

					$('#massupdating-dialog').waDialog({
						width: width,
						height: height,
						// className: className,
						content: data,
						buttons: htmlButtons,
						onLoad: function() {
							if($('.massupdating-dialog .variable').length)
							$('.massupdating-dialog .variable').click(function(){
								if(document.createRange) {
									let rng = document.createRange();
									rng.selectNode(this);
									var sel = window.getSelection();
									sel.removeAllRanges();
									sel.addRange(rng);
								} else {
									let rng = document.body.createTextRange();
									rng.moveToElementText(this);
									rng.select();
								}
							});
							
							if(typeof Load == 'function')
								Load();
						},
						onSubmit: function(d) {
							if(typeof Submit == 'function')
								Submit(this, products, d);
							
							return false;
						},
						onClose: function() {
							if(typeof Close == 'function')
								Close(this);
							
							$(this).remove();
						}
					});
				});
				
				
			}
		},
		getDialogData: function(products, elem) {
			if(!products)
				products = $.product_list.getSelectedProducts(true);
			if(!elem)
				elem = $('.massupdating-dialog').closest('form');
			return $(elem).serializeArray().concat(products.serialized);
		},
		toggleDialogButtons: function(state) {
			if(state == 'disabled' || state == 'active')
				$('.dialog-buttons input').attr('disabled', state == 'disabled');
			else {
				state = $('.dialog-buttons input').prop('disabled') ? 'active' : 'disabled';
				this.toggleDialogButtons(state);
			}
		},
		
		toggleField: function(code) {
			if($('#massupdating-field-' + code).length) {
				let elem = $('#massupdating-field-' + code);
				let is_disabled = elem.data('disabled') == 1;
				
				elem.find('input, textarea, select').attr('disabled', !is_disabled);
				elem.data('disabled', is_disabled ? 0 : 1);
				elem.css('pointer-events', is_disabled ? 'auto' : 'none');
				elem.css('opacity', is_disabled ? '1' : '0.5');
				$('#massupdating-freeze-' + code).html(is_disabled ? $_('Заморозить') : $_('Разморозить'));
			};
		},
		
		addFeatureValue: function(code) {
			$('#massupdating-feature-' + code).after($('<input placeholder="' + $_('Значение характеристики') + '" style="margin-left: 5px;" type="text" name="new_features[' + code + ']"/>'));
			$('#massupdating-features-add-' + code).remove();
		},
		
		addFeatureMultipleValue: function(code) {
			$('#massupdating-features-multiple').append('<div id="massupdating-features-multiple-user-' + this.id + '" style="margin-bottom: 5px;"><input class="groupbox checkbox" type="checkbox" name="new_multiple_features[' + code + '][]" checked disabled> <input placeholder="' + $_('Значение характеристики') + '" style="margin-left: 5px;" type="text" name="new_multiple_features[' + code + '][]"/> <a href="javascript: massupdating.removeFeatureMultipleValue(' + this.id + ');" class="inline-link"><i class="icon10 delete"></i></a></div>');
			
			this.id++;
		},
		
		removeFeatureMultipleValue: function(id) {
			$('#massupdating-features-multiple-user-' + id).remove();
		},
		
		initWysiwyg: function(elem) {
			$(elem).redactor({
				editorOnLoadFocus: true,
				deniedTags: false,
				minHeight: 175,
				source: false,
				paragraphy: false,
				replaceDivs: false,
				toolbarFixed: true,
				buttons: ['html', 'formatting', 'bold', 'italic', 'underline', 'deleted', 'unorderedlist', 'orderedlist', 'outdent', 'indent', 'image', 'video', 'table', 'link', 'alignment', '|', 'horizontalrule'],
				plugins: ['fontcolor', 'fontsize', 'fontfamily', 'table', 'video'],
				imageUpload: '?module=pages&action=uploadimage&r=2',
				imageUploadFields: '[name="_csrf"]:first',
				imageUploadErrorCallback: function(json) {
					alert(json.error);
				}
			});
		},
		
		destroyWysiwyg: function(elem) {
			$(elem).redactor('core.destroy').focus();
		},
		
		toggleWysiwyg: function(elem, key) {
			let a = $(elem.target);
			let action = a.hasClass('wysiwyg') ? 'wysiwyg' : 'html';
			if(a.closest('li').hasClass('selected')) // Нажатие по уже выбранному элементу
				return;
			
			$('#massupdating-wysiwyg-switcher-' + key + ' li').removeClass('selected');
			$('#massupdating-wysiwyg-switcher-' + key + ' li a.' + action).closest('li').addClass('selected');
			
			let wysiwyg = '#massupdating-textarea-' + key;

			action == 'wysiwyg' ? this.initWysiwyg(wysiwyg) : this.destroyWysiwyg(wysiwyg);
		},
		
		round: function(value, precision, mode, minus) {
			value = value * 1; // Преобразование в число
			
			if($.inArray(precision, ['1000', '100', '10', '0', '1', '2']) == -1)
				precision = 0;
			
			if($.inArray(mode, ['up', 'down']) == -1)
				mode = 'up';

			let rounded;
			if(precision == 0)
				rounded = Math[mode == 'up' ? 'ceil' : 'floor'](value);
			else if($.inArray(precision, ['1', '2']) > -1) {
				rounded = Math[mode == 'up' ? 'ceil' : 'floor'](value * (precision == 2 ? 100 : 10)) / (precision == 2 ? 100 : 10);
			} else if($.inArray(precision, ['1000', '100', '10']) > -1) {
				rounded = Math[mode == 'up' ? 'ceil' : 'floor'](value / precision) * precision;
				if(minus)
					rounded = rounded - 1;
			}
			
			return rounded;
		},
		
		checkFiles: function(elem) {
			let files = elem.prop('files');
			let good_files = 0;
			
			for(let i = 0; i < files.length; i++) {
				let format = files[i].name.split('.').pop();
				if(/^(gif|jpe?g|png)$/i.exec(format)) good_files++;			
			}
			
			return good_files == files.length;
		},
		
		heights: {
			'photo': 350,
			'prices': 355,
			'currencies': 280,
			'tags': 330,
			'badge': 220,
			'features': 500,
			'name': 350,
			'meta_title': 350,
			'summary': 360,
			'meta_keywords': 390,
			'meta_description': 390,
			'description': 585,
			'video_url': 250,
			'params': 420,
			'status': 200,
			'skus': 395,
			'url': 480,
			
			'massupdating': 700
		},
		
		edit: function(action = 'massupdating') {
			this.dialog({
				module: 'edit',
				doAction: action,
				width: action == 'massupdating' ? 1100 : 900,
				height: this.heights[action] || 300,
				// className: 'width1100px ' + (this.heights[action] ? this.heights[action] : ''),
				buttons: ['save', 'cancel'],
				on: {
					Load: function() {
						if($('.massupdating-video', this).length)
							$('.massupdating-video', this).keyup(function(e) {
								if(!$('#massupdating-video-load').length)
									$(this).after($('<i id="massupdating-video-load" class="massupdating-small-load"/>'));
								else
									$('#massupdating-video-load').attr('class', 'massupdating-small-load').show();
								var value = $(this).val();
								if(value.length == 0 && $('#massupdating-video-load').length) {
									$('#massupdating-video-load').remove();
								} else {
									$.getJSON('?plugin=massupdating&module=video&action=check', {
										value: value
									}, function(response) {
										if(response.status == 'ok') {
											$('#massupdating-video-load').attr('class', 'massupdating-small-load icon16 ' + response.data);
										} else
											alert(response.errors[0][0]);
									});
								}
							});
					}, Submit: function(dat, products, d) {
						if($('#massupdating-photo-action').length && ($('#massupdating-photo-action').val() == 'replace' || $('#massupdating-photo-action').val() == 'delete'))
								if(!confirm($_('Все имеющиеся сейчас фото выбранных товаров будут ' + ($('#massupdating-photo-action').val() == 'replace' ? 'заменены на новые' : 'удалены') + '. Продолжить?')))
									return false;
						
						let submitButton = $('input[type=submit]', dat);
						submitButton.attr('disabled', true).before($('<div class="massupdating-load"/>'));
						let data = new FormData();
						if($('input[type=file]', dat).length) {
							let file_data = $('input[type=file]', dat)[0].files;
								
							let check = $.massupdating.checkFiles($('#massupdating-photo'));
							if(!check) {
								alert($_('Files with extensions *.gif, *.jpg, *.jpeg, *.png are allowed only.'));
								$('.massupdating-load').hide();
								return false;
							} else {
								for(let i = 0; i < file_data.length; i++){
									data.append('files[]', file_data[i]);
								}
							}
						};
						
						let other_data = $(dat).serializeArray().concat(products.serialized);
						$.each(other_data, function(key, input) {
							data.append(input.name, input.value);
						});
						
						$.ajax({
							data: data,
							type: 'POST',
							contentType: false,
							processData: false,
							dataType: 'json',
							url: '?plugin=massupdating&module=edit&action=save',
							success: function(response) {
								$('.massupdating-load').hide();
								if(response.status == 'ok') {
									if($('#massupdating-reload').is(':checked')) {
										$('#s-product-list-skus-container').hide();
										$.products.dispatch();
									};
										
									d.trigger('close');
								} else {
									submitButton.removeAttr('disabled');
									alert(response.errors[0][0]);
								}
							}
						});
						
						return false;
					}
				}
			});
		},
		
		cross: function() {
			this.dialog({
				module: 'cross',
				width: 900,
				height: 400,
				buttons: ['save', 'cancel'],
				on: {
					Submit: function(dat, products, d) {
						if(($('input[name=cross_selling]:checked').val() == 2 && $('#massupdating-cross_selling-custom-ids').val() == 0) || ($('input[name=upselling]:checked').val() == 2 && $('#massupdating-upselling-custom-ids').val() == '0'))
							if(!confirm($_('Вы выбрали "Ввести товары вручную", но так и не заполнили их. Продолжить?')))
								return false;
								
						var submitButton = $('input[type=submit]', dat);
						submitButton.attr('disabled', true).before($('<div class="massupdating-load"/>'));
						$.post('?plugin=massupdating&module=cross&action=save',
							$.massupdating.getDialogData(products, dat),
							function(response) {
								$('.massupdating-load').hide();
								if(response.status == 'ok') {
										
									if($('#massupdating-reload').is(':checked')) {
										$('#s-product-list-skus-container').hide();
										$.products.dispatch();
									};
										
									d.trigger('close');
								} else {
									submitButton.val($_('Сохранить')).removeAttr('disabled');
									alert(response.errors[0][0]);
								}
							}, 'json'
						);
					}
				}
			});
		},
		
		generator: function() {
			this.dialog({
				module: 'generator',
				action: 'default',
				width: 900,
				height: 430,
				buttons: {
					save: {
						value: $_('Генерировать!'),
						class: 'blue',
					},
					cancel: { }
				},
				on: {
					Submit: function(dat, products, d) {
						if($('input[name="generator[mask]"]', dat).val().length) {
							var submitButton = $('input[type=submit]', dat);
							submitButton.attr('disabled', true).before($('<div class="massupdating-load"/>'));
							
							let data = $(dat).serializeArray().concat(products.serialized);
							$.post('?plugin=massupdating&module=edit&action=save', data, function(response) {
								if(response.status == 'ok') {
									$('.massupdating-load').hide().after('<div style="float: right; color: green; line-height: 35px;">' + response.data + '</div>');
										
									// d.trigger('close');
								} else {
									$('.massupdating-load').remove();
									
									submitButton.removeAttr('disabled');
									alert(response.errors[0][0]);
								}
							}, 'json');
						} else
							$('input[name="generator[mask]"]', dat).focus();
					}
				}
			});
		},
		
		far: function() {
			this.dialog({
				module: 'far',
				action: 'default',
				width: 900,
				height: 400,
				buttons: {
					find: {
						value: 'Найти',
						class: 'blue',
						onClick: function(products) {
							$.massupdating.findAndReplace.do($.massupdating.getDialogData(products), 'find');
						}
					},
					save: {
						value: $_('Найти и заменить'),
						// disabled: true
					},
					cancel: { }
				},
				on: {
					Submit: function(dat, products, d) {
						$.massupdating.findAndReplace.do($.massupdating.getDialogData(products, dat), 'findAndReplace');
					}
				}
			});
		}
	};

	$('.massupdating li').on('click', function (e) {
		e.stopImmediatePropagation();
	});
	
	$(document).on('click', '.massupdating-help-window', function(e) {
		let article = $(this).data('article'), title = $(this).data('title');
		
		window.open('?plugin=massupdating&module=help&article=' + article, title, 'width=450,height=400');
		
		e.preventDefault();
	});
})(jQuery);

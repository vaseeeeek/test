var BsUI = {
	is_folding_initialized: false,

	init: function (context)
	{
		BsUI.initForm(context);
		BsUI.initWysiwyg(context);
		BsUI.initSelectBox(context);
		BsUI.initRadioBox(context);
		BsUI.initCheckBox(context);
		BsUI.initOptionSet(context);
		BsUI.initFieldGroup(context);
		BsUI.initSelectTabs(context);
		BsUI.initBlockFolding(context);
		BsUI.initCodeMirror(context);
	},

	initForm: function (context)
	{
		context.find('.form_ajax').on('submit', function ()
		{
			var _this = $(this);

			if (_this.data('timer'))
			{
				clearTimeout(_this.data('timer'));
				_this.data('timer', false);
			}

			function changeStatus(status, timer)
			{
				_this.find('.submit-box__status .form-status').addClass('form-status_hidden');
				_this.find('.submit-box__status .form-status_' + status).removeClass('form-status_hidden');
				timer = timer ? timer : false;

				if (timer)
				{
					_this.data('timer', setTimeout(function () {
						_this.find('.submit-box__status .form-status').addClass('form-status_hidden');
					}, timer));
				}
			}

			changeStatus('loading');
			var datatype = _this.data('datatype');
			datatype = datatype ? datatype : 'html';

			$.ajax({
				url: _this.attr('action'),
				method: _this.attr('method'),
				data: _this.serialize(),
				dataType: datatype,
				success: function (response)
				{
					changeStatus('success', 1000);
					_this.trigger('ajax.success', [response]);

					return false;
				},
				error: function ()
				{
					changeStatus('error');
					_this.trigger('ajax.error');

					return false;
				}
			});

			return false;
		});
	},

	initWysiwyg: function (context)
	{
		if (typeof CodeMirror != 'undefined')
		{
			context.find('.text-area_syntax-highlight').each(function () {
				var _this = $(this);
				var cm;
				var cm_config = {
					mode: "text/plain",
					tabMode: "indent",
					height: "dynamic",
					lineWrapping: true,
					onChange: function ()
					{
						cm.save();
					}
				};

				if (_this.hasClass('text-area_syntax-highlight_html'))
				{
					cm_config.mode = "text/html";
				}
				else if (_this.hasClass('text-area_syntax-highlight_css'))
				{
					cm_config.mode = "text/css";
				}

				cm = CodeMirror.fromTextArea(this, cm_config);

				_this.on('change', function () {
					cm.setValue(_this.val());
					cm.refresh();
				});

				_this.on('syntax-highlight.reset', function () {
					cm.setValue(_this.val());
				});

				_this.on('syntax-highlight.refresh', function () {
					cm.refresh();
				});
			});

			BsUI.refreshWysiwyg(context, 20);
		}

		if (typeof $().redactor != 'undefined')
		{
			context.find('.text-area_wysiwyg').each(function () {
				var _this = $(this);
				if (_this.prev('.redactor-editor').length) return true;

				var value_backup = _this.val();

				try {
					_this.redactor({
						replaceDivs: false,
						deniedTags: false,
						paragraphy: false,
						imageUpload: '?module=pages&action=uploadimage&filelink=1',
						uploadImageFields: {
							_csrf: $('[name="_csrf"]').val()
						},
						syncCallback: function (html) {
							html = html.replace(/{[a-z$][^}]*}/gi, function (match, offset, full) {
								var i = full.indexOf("</script", offset + match.length);
								var j = full.indexOf('<script', offset + match.length);
								if (i == -1 || (j != -1 && j < i)) {
									match = match.replace(/&gt;/g, '>');
									match = match.replace(/&lt;/g, '<');
									match = match.replace(/&amp;/g, '&');
									match = match.replace(/&quot;/g, '"');
								}
								return match;
							});
							if (document.syncCallback) {
								html = syncCallback(html);
							}
							this.$textarea.val(html);
						}
					});
				} catch (e) {
					console.warn(e);

					var $redactor_box = _this.closest('.redactor-box');
					if ($redactor_box.length) {
						$redactor_box.after(_this);
						$redactor_box.remove();
						_this.show();
					}

					// _this.val(value_backup);
				}
			});
		}
	},

	refreshWysiwyg: function (context, timeout)
	{
		var refresh = function ()
		{
			if (typeof CodeMirror != 'undefined')
			{
				context.find(".text-area_syntax-highlight").each(function () {
					var _this = $(this);
					_this.trigger('syntax-highlight.reset');
					_this.trigger('syntax-highlight.refresh');
				});
			}
		};

		if (timeout)
		{
			setTimeout(function()
			{
				refresh();
			}, timeout);
		}
		else
		{
			refresh();
		}
	},

	initSelectBox: function (context)
	{
		context.find('.select-box__input').on('change', function (e) {
			e.stopPropagation();
			var _this = $(this);
			var select_box = _this.closest('.select-box');
			var disabled = _this.attr('disabled') ? true : false;
			var value = _this.val();
			var box_value = select_box.data('value');

			select_box.data('disabled', disabled);

			if (value !== box_value)
			{
				select_box.data('value', value);
				select_box.trigger('change');
			}

			return false;
		});

		context.find('.select-box').on('change', function (e) {
			e.stopPropagation();
			var _this = $(this);

			var input = _this.find('.select-box__input');
			var disabled = _this.data('disabled');
			var value = _this.data('value');
			var input_value = input.val();

			if (disabled)
			{
				input.attr('disabled', 'disabled');
			}
			else
			{
				input.removeAttr('disabled');
			}

			var options = input.find('option');
			options.removeAttr('selected');
			options.filter('option[value="'+value+'"]').attr('selected', 'selected');

			if (value !== input_value)
			{
				input.trigger('change');
			}
		});

		context.find('.select-box_ajax').on('change', function (e) {
			var _this = $(this);
			var value = _this.data('value');

			if (_this.data('timer'))
			{
				clearTimeout(_this.data('timer'));
				_this.data('timer', false);
			}

			function changeStatus(status, timer)
			{
				_this.find('.select-box__status .form-status').addClass('form-status_hidden');
				_this.find('.select-box__status .form-status_' + status).removeClass('form-status_hidden');
				timer = timer ? timer : false;

				if (timer)
				{
					_this.data('timer', setTimeout(function () {
						_this.find('.select-box__status .form-status').addClass('form-status_hidden');
					}, timer));
				}
			}

			changeStatus('loading');

			var option = _this.find('.select-box__input option:selected');
			var request_data = option && option.data ? option.data() : {};
			if (!request_data)
			{
				request_data = {};
			}

			request_data.value = _this.data('value');

			$.ajax({
				url: _this.data('action'),
				method: _this.data('method'),
				data: request_data,
				dataType: _this.data('datatype'),
				success: function (result) {
					changeStatus('success', 1000);
					_this.trigger('ajax.success', [result]);
				},
				error: function () {
					changeStatus('error');
				}
			});

			return false;
		});

		context.find('.select-box_ajax').on('ajax.success', function (e) {
			e.stopPropagation();
		});

		context.find('.select-box').each(function () {
			var _this = $(this);
			var input = _this.find('.select-box__input');
			var disabled = input.attr('disabled') ? true : false;
			var value = input.val();
			_this.data('disabled', disabled);
			_this.data('value', value);
		});
	},

	initRadioBox: function (context)
	{
		context.find('.radio-box__input').on('change', function () {
			var _this = $(this);
			var checked = _this.attr('checked') ? true : false;
			var disabled = _this.attr('disabled') ? true : false;
			var radio_box = _this.closest('.radio-box');
			var box_checked = radio_box.data('checked');
			radio_box.data('disabled', disabled);

			if (checked !== box_checked)
			{
				radio_box.data('checked', checked);
				radio_box.trigger('change');
			}

			if (checked)
			{
				var item = radio_box.closest('.radio-box-set__item');
				var radio_box_set = item.closest('.radio-box-set');
				var items = radio_box_set.find('.radio-box-set__item');
				var inputs = items.find('.radio-box__input').not(_this);
				inputs.trigger('change');
			}

			return false;
		});

		context.find('.radio-box').on('change', function (e) {
			e.stopPropagation();
			var _this = $(this);

			var input = _this.find('.radio-box__input');
			var disabled = _this.data('disabled');
			var checked = _this.data('checked');
			var input_checked = input.attr('checked') ? true : false;

			if (disabled)
			{
				input.attr('disabled', 'disabled');
			}
			else
			{
				input.removeAttr('disabled');
			}

			if (checked)
			{
				input.attr('checked', 'checked');
			}
			else
			{
				input.removeAttr('checked');
			}

			if (checked !== input_checked)
			{
				input.trigger('change');
			}
		});

		context.find('.radio-box').each(function () {
			var _this = $(this);
			var input = _this.find('.radio-box__input');
			var disabled = input.attr('disabled') ? true : false;
			var checked = input.attr('checked') ? true : false;
			_this.data('disabled', disabled);
			_this.data('checked', checked);
		});
	},

	initCheckBox: function (context)
	{
		context.find('.check-box__input').on('change', function (e) {
			e.stopPropagation();
			var _this = $(this);
			var checked = _this.attr('checked') ? true : false;
			var disabled = _this.attr('disabled') ? true : false;
			var check_box = _this.closest('.check-box');
			var box_checked = check_box.data('checked');
			check_box.data('disabled', disabled);

			if (checked !== box_checked)
			{
				check_box.data('checked', checked);
				check_box.trigger('change');
			}

			return false;
		});

		context.find('.check-box').on('change', function (e) {
			e.stopPropagation();
			var _this = $(this);

			var input = _this.find('.check-box__input');
			var disabled = _this.data('disabled');
			var checked = _this.data('checked');
			var input_checked = input.attr('checked') ? true : false;

			if (disabled)
			{
				input.attr('disabled', 'disabled');
			}
			else
			{
				input.removeAttr('disabled');
			}

			if (checked)
			{
				input.attr('checked', 'checked');
			}
			else
			{
				input.removeAttr('checked');
			}

			if (checked !== input_checked)
			{
				input.trigger('change');
			}
		});

		context.find('.check-box').each(function () {
			var _this = $(this);
			var input = _this.find('.check-box__input');
			var disabled = input.attr('disabled') ? true : false;
			var checked = input.attr('checked') ? true : false;
			_this.data('disabled', disabled);
			_this.data('checked', checked);
		});
	},

	initOptionSet: function (context)
	{
		context.find('.option-set__trigger').on('change', function (e) {
			e.stopPropagation();
			var _this = $(this);
			var checked = _this.data('checked');
			var option_set = _this.closest('.option-set');

			if (checked)
			{
				option_set.find('.option-set__item').data('disabled', false);
				option_set.find('.option-set__item').trigger('change');
				var triggers = option_set.find('.option-set__trigger').not(_this);
				triggers.trigger('change');
			}
			else
			{
				option_set.find('.option-set__item').data('disabled', true);
				option_set.find('.option-set__item').trigger('change');
			}
		});

		context.find('.option-set').each(function () {
			var _this = $(this);
			var trigger = _this.find('.option-set__trigger');
			trigger.trigger('change');
		});
	},

	initFieldGroup: function (context)
	{
		context.find('.field-group__trigger').on('change', function (e) {
			e.stopPropagation();
			var _this = $(this);
			var checked = _this.data('checked');
			var field_group = _this.closest('.field-group_extensible');

			if (checked)
			{
				field_group.addClass('field-group_expanded');
			}
			else
			{
				field_group.removeClass('field-group_expanded');
			}
		});

		context.find('.field-group_extensible').each(function () {
			var _this = $(this);
			var trigger = _this.find('.field-group__trigger');
			trigger.trigger('change');
		});
	},

	initSelectTabs: function (context)
	{
		context.find('.select-tabs__select').on('change', function (e) {
			e.stopPropagation();
			var _this = $(this);
			var value = _this.data('value');
			var select_tabs = _this.closest('.select-tabs');

			var tabs = select_tabs.find('.select-tabs__tab');
			tabs.removeClass('select-tabs__tab_open');
			tabs.filter('.select-tabs__tab_value_' + value).addClass('select-tabs__tab_open');
		});

		context.find('.select-tabs').each(function () {
			var _this = $(this);
			var select = _this.find('.select-tabs__select');
			select.trigger('change');
		});
	},

	initBlockFolding: function(context)
	{
		if (BsUI.is_folding_initialized)
		{
			return;
		}

		context.on('click', '.js-unfold_prev_block', function(e) {
			e.preventDefault();

			var $block = $(this).hide().parent('.js-block_folding');
			$block.prev().show();

			var $clone = $block.clone().data('is_before_block', 1);
			$block.prev().before($clone);

			$block.find('.js-fold_prev_block').show();
			$clone.find('.js-fold_prev_block').show();
		});

		context.on('click', '.js-fold_prev_block', function(e) {
			e.preventDefault();

			var $this = $(this);
			var $block = $this.parent('.js-block_folding');
			var is_before_block = $block.data('is_before_block');

			if (is_before_block == 1)
			{
				var $real_block = $block.next().next();
				$block.remove();
				$block = $real_block;
			}
			else
			{
				$block.prev().prev().remove();
			}

			$block.prev().hide();
			$block.find('.js-fold_prev_block').hide();
			$block.find('.js-unfold_prev_block').show();

			if (is_before_block != 1)
			{
				$(document).scrollTop($block.offset().top - 100);
			}
		});

		BsUI.is_folding_initialized = true;
	},

	initCodeMirror: function(context)
	{
		context.find('.bsui-smarty-textarea:not(.bsui-smarty-textarea_init)').each(function (i, item) {
			var $item = $(item);

			$item.addClass('bsui-smarty-textarea_init');

			var $textarea = $item.find('.bsui-smarty-textarea__control');
			var mode = $item.data('cm_mode');

			var need_refresh = true;

			var cm = CodeMirror.fromTextArea($textarea[0],
				{
					mode: mode ? mode : 'smartymixed',
					tabSize: 4,
					indentUnit: 4,
					indentWithTabs: true,
					height: "dynamic",
					viewportMargin: 2,
					lineWrapping: true,
					onChange: function (cm) {
						$textarea.val(cm.getValue());
					}
				});

			$item.on('refresh', function () {
				if ($textarea.val() == cm.getValue()) {
					if (need_refresh) {
						setTimeout(function () {
							$item.trigger('refresh');
							cm.refresh();
						}, 10);
					}

					need_refresh = false;

					return;
				}

				need_refresh = true;

				cm.setValue($textarea.val());
				setTimeout(function () {
					$item.trigger('refresh');
					cm.refresh();
				}, 10);
			});

			setTimeout(function () {
				$item.trigger('refresh');
			}, 0);
		});
	}
};

$(function () {
	BsUI.init($(document));
});

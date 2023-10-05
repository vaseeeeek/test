var RegionsSettings = {
	updatePageSelect: function () {
		var selects = $('.regions__page-templates .page-template__id');
		var ids = [];
		selects.each(function ()
		{
			var value = $(this).val();

			if (value != '')
			{
				ids.push(value);
			}
		});

		selects.each(function ()
		{
			var select = $(this);
			var options = select.find('option');
			options.removeAttr('disabled');
			options.each(function ()
			{
				var option = $(this);
				var val = option.val();

				if ($.inArray(val, ids) != -1 && val != select.val())
				{
					option.attr('disabled', 'disabled');
				}
			});
		});
	},

	initPageSelect: function (select)
	{
		select.on('change', function ()
		{
			RegionsSettings.updatePageSelect();
		});
	},

	initDeleteTrigger: function (field)
	{
		field.on('click', '.settings-page__trigger-delete-field', function () {
			var $this = $(this);
			var $field = $this.closest('.field-group__fields');
			var $helper = $field.prev('.field-group__helper');

			var field_id = $this.data('field_id');
			if (field_id)
			{
				$field.hide();
				$helper.hide();
				$this.data('deleted_field', 1);
				$field.find('[name^="params"]').prop('name', 'delete_params[' + field_id + ']');
			}
			else
			{
				$field.remove();
				$helper.remove();
			}

			RegionsSettings.updateParamsJsonField();
		});
	},

	initTemplateStorefrontSelection: function($context)
	{
		$context.find('.js-excluded_storefront_checkbox').on('change', function() {
			var $this = $(this);
			var $block = $this.closest('.js-template_edit_block');
			var $input = $block.find('.js-excluded_storefronts_input');

			var storefronts = $block
				.find('.js-excluded_storefront_checkbox:checked')
				.map(function(i, item) {return $(item).data('storefront')})
				.toArray()
				.join(',');

			$input.val(storefronts);
		});
	},

	initTemplateIgnoreDefaultCheck: function($context)
	{
		$context.find('.js-page_ignore_default_checkbox').on('change', function() {
			var $this = $(this);

			$this.closest('.js-template_edit_block')
				.find('.js-page_ignore_default_input')
				.val($this.prop('checked') ? 'Y' : 'N');
		});
	},

	initParamsSort: function()
	{
		var $params_list = $('.js-sortable_list');

		if ($params_list.destroy)
		{
			$params_list.destroy();
		}

		$params_list.sortable({
			distance: 5,
			opacity: 0.75,
			items: '>.js-sortable_list_item',
			axis: 'y',
			containment: 'parent',
			handle: 'i.sort',
			tolerance: 'pointer',
			update: RegionsSettings.updateParamsJsonField
		});

		RegionsSettings.updateParamsJsonField();
	},

	updateParamsJsonField: function()
	{
		var params = [];
		var new_param_id = -1;

		$('.js-sortable_list .js-param_input').each(function (i, item) {
			var $this = $(item);

			var match = $this.prop('name').match(/^(.+)\[(\d*)\]$/);
			if (match === null)
			{
				return;
			}

			var param = {
				name: $this.val(),
				sort: i + 1,
			};

			if (match[1] == 'params')
			{
				param.id = match[2];
				param.delete = false;
			}
			else if (match[1] == 'delete_params')
			{
				param.id = match[2];
				param.delete = true;
			}
			else if (match[1] == 'new_params')
			{
				param.id = new_param_id--;
				param.delete = false;
			}
			else
			{
				return;
			}

			params.push(param);
		});

		$('.js-params_json').val(JSON.stringify(params));
	},

	setSaveButtonState(state)
	{
		var states = {
			yellow: 'yellow',
			green: 'green'
		};
		var $save_button = $('.submit-box__button');

		if (!states.hasOwnProperty(state) || !$save_button.length)
		{
			return;
		}


		Object.keys(states).forEach(function(state) {
			$save_button.removeClass(states[state]);
		});

		$save_button.addClass(states[state]);
	}
};

$(function () {
	var html = $('html');

	html.find('#regions-settings-form').on('ajax.success', function (e, html_response) {
		$('.regions__params').replaceWith($('.regions__params', html_response));
		$('.helper').each(function (index) {
			$(this).replaceWith($('.helper', html_response).eq(index));
		});

		RegionsSettings.setSaveButtonState('green');

		RegionsSettings.initParamsSort();
	});

	$('.bsui-page__content').find('input, textarea, select').on('change', function() {
		RegionsSettings.setSaveButtonState('yellow');
	});

	html.on('click', '.js-add-param', function (e) {
		var btn = $(this);
		var field = btn.closest('.field-box');
		var template = $('.settings-page__template-new-param').contents().clone();
		var id = 'regions_new-param-' + (1 + $('.regions__params .regions__new-param').length);
		template.find('.field-box__label').attr('for', id);
		template.find('.field-box__input-text').attr('id', id);

		$('.js-sortable_list').append(template);

		RegionsSettings.updateParamsJsonField();

		RegionsSettings.initDeleteTrigger(template);
	});

	html.on('change', '.js-sortable_list .js-param_input', function() {
		RegionsSettings.updateParamsJsonField();
	});

	RegionsSettings.initDeleteTrigger(html);

	RegionsSettings.initPageSelect(html.find('.regions__page-templates .page-template__id'));
	RegionsSettings.updatePageSelect();
	RegionsSettings.initTemplateStorefrontSelection(html);
	RegionsSettings.initTemplateIgnoreDefaultCheck(html);

	// Триггер добавления страницы

	html.on('click', '.settings-page__trigger-add-page-template', function ()
	{
		var template = $('.templates-box .settings-page__template-new-page-template').clone();
		var id = (1 + $('.regions__page-templates .regions__page-template_new').length);
		var id_id = 'regions_new-page-template_url_' + id;
		var content_id = 'regions_new-page-template_content_' + id;

		var url = template.find('#regions_new-page-template_url_X');
		url.attr('id', id_id);
		template.find('[for=regions_new-page-template_url_X]').attr('for', id_id);
		var content = template.find('#regions_new-page-template_content_X');
		content.attr('id', content_id);
		content.addClass('text-area_wysiwyg');
		template.find('[for=regions_new-page-template_content_X]').attr('for', content_id);

		$(this).closest('.field-box').before(template);
		BsUI.init(template);
		initTriggerDeletePageTemplate(template);
		RegionsSettings.initPageSelect(url);
		RegionsSettings.updatePageSelect();
		RegionsSettings.initTemplateStorefrontSelection(template);
		RegionsSettings.initTemplateIgnoreDefaultCheck(template);
	});

	function initTriggerDeletePageTemplate(context)
	{
		context.on('click', '.settings-page__trigger-delete-page-template', function ()
		{
			$(this).closest('.regions__page-template').remove();
			RegionsSettings.updatePageSelect();
		});
	}

	RegionsSettings.initParamsSort();

	initTriggerDeletePageTemplate(html);
});
jQuery(function ($) {
	var trigger_environment_restore_url = $('#s-content').data('trigger_environment_restore_url');

	var region_image_index = 1;

	var last_new_order = undefined;

	var $filter_form = $('.js-filter_form');
	var $loading = $('.js-loading');
	var $table_wrap = $('.js-table_wrap');
	var $page_input = $filter_form.find('.js-page_input');
	var $page_limit = $filter_form.find('.js-page_limit');
	var $regions_table = $('.js-regions_table');

	$('.shop-regions__li').addClass('selected').removeClass('no-tab');

	$(document).on('click', '.js-delete-city', function () {
		if (confirm('Вы действительно хотите удалить регион?'))
		{
			var row = $(this).closest('.region__row');
			var id = row.data('id');

			$.ajax({
				url: '?plugin=regions&module=data&action=deleteCity',
				method: 'post',
				data: {
					id: id,
					_csrf: $('input[name="_csrf"]').val()
				},
				dataType: 'json',
				success: function () {
					row.slideDown('fast', function () {
						row.remove();
					});
				}
			})
		}
	});

	$(document).on('change', '#shop_regions_list .region__row .js-region_selected', function() {
		if (!$(this).is(':checked'))
		{
			$('.js-region_selected_all').prop('checked', false);
		}
		updateSelectedCount();
	});

	$(document).on('change', '.js-region_selected_all', function() {
		toggleAllRegionSelection($(this).is(':checked'));
	});

	$('#mass_actions_sidebar .js-action').on('click', function () {
		var $this = $(this);
		var action = $this.data('action');

		var selected_ids = $('#shop_regions_list .js-region_selected:checked')
			.closest('.region__row')
			.map(function(index, item) {
				return $(item).data('id')
			})
			.toArray();

		if (selected_ids.length == 0)
		{
			alert('Не выбран ни один регион');
			return;
		}

		if (action == 'delete' && !confirm('Вы действительно хотите удалить выбраные регионы?'))
		{
			return;
		}

		$.ajax({
			url: '?plugin=regions&module=data&action=massEdit',
			data: {
				action: action,
				region_ids: selected_ids,
				_csrf: $('input[name="_csrf"]').val()
			},
			type: 'POST',
			dataType: 'json',
			success: function (result) {
				reloadList();
			}
		});
	});

	$filter_form.find('.js-filter').on('change', function() {
		$(this).blur();

		$page_input.val(1);
		$filter_form.trigger('submit');
	});

	$('.js-filter_name').on('focus', function() {
		$(this)
			.one('mouseup', function() {
				$(this).select();
			})
			.select();
	});

	$(document).on('click', '.js-pagination .js-page_number', function() {
		var $this = $(this);
		var page_number = $this.data('page_number');

		if (page_number !== undefined && page_number > 0)
		{
			$page_input.val(page_number);
			$filter_form.trigger('submit');
		}

		return false;
	});

	$filter_form.on('submit', function(event) {
		reloadList();

		event.stopPropagation();
		return false;
	});

	$(document).on('click', '.js-set_limit', function(event) {
		$page_limit.val($(this).data('limit'));
		$page_input.val(1);
		reloadList();

		event.stopPropagation();
		return false;
	});

	// $.fn.select2.defaults.set('amdBase', '/wa-apps/shop/plugins/regions/js/');
	// $.fn.select2.defaults.set('amdLanguageBase', '/wa-apps/shop/plugins/regions/js/i18n/');
	// $.fn.select2.defaults.set('language', 'ru');
	$.fn.select2.defaults.set('language', {noResults: function() {return "Совпадений не найдено"}});

	$('.js-searchable_select').select2();

	initSortable();
	var timer = setInterval(submitListOrder, 400);





	function initSortable() {
		$('.js-is_custom_sortable').sortable({
			distance: 5,
			opacity: 0.75,
			items: '>tbody>tr',
			axis: 'y',
			containment: 'parent',
			handle: 'i.sort',
			tolerance: 'pointer',
			update: onListOrderUpdate
		});
	}

	function openInNewTab(url) {
		window.open('http://' + url, '_blank');
	}

	function toggleAllRegionSelection(toggle)
	{
		$('#shop_regions_list .js-region_selected').prop('checked', toggle);
		updateSelectedCount();
	}

	function updateSelectedCount()
	{
		var count = $('#shop_regions_list .js-region_selected:checked').length;
		var $count = $('.js-selected_count');
		$count.html(count);
		$count.toggleClass('invisible', count === 0);
	}

	function onListOrderUpdate(event) {
		var $table = $(event.target);

		last_new_order = $table.find('.region__row').map(function(i, obj) {return $(obj).data('id');}).toArray();
	}

	function submitListOrder() {
		if (last_new_order === undefined)
		{
			return;
		}

		var offset = $regions_table.data('pagination_offset');

		$.ajax({
			url: '?plugin=regions&module=data&action=sortRegions',
			type: 'POST',
			data: {
				order: last_new_order,
				offset: offset,
				_csrf: $('input[name="_csrf"]').val()
			},
			dataType: 'json'
		});

		last_new_order = undefined;
	}


	function reloadList() {
		toggleLoading(true);

		$.ajax({
			url: '?plugin=regions',
			type: 'GET',
			data: prepareFilterParams(),
			complete: function(xhr, status) {
				toggleLoading(false);

				if (status === 'success')
				{
					updateList(xhr.responseText);
					updateSelectedCount();
				}
			}
		});
	}

	function updateList(html) {
		var $html = $(html);
		var is_empty = $html.find('.js-table_wrap').data('empty') === true;

		if (!is_empty)
		{
			var $new_regions_table = $html.find('.js-regions_table');
			$regions_table.html($new_regions_table.html());

			$regions_table.data('pagination_offset', $new_regions_table.data('pagination_offset'));

			$('.js-after_table').html($html.find('.js-after_table').html());
			initSortable();
		}
		else
		{
			$('.js-pagination').html('');
		}

		$('.js-regions_empty_table_message').toggle(is_empty);
		$regions_table.toggle(!is_empty);
	}

	function toggleLoading(toggle) {
		// $loading.toggle(toggle);
		// $table_wrap.toggle(!toggle);
	}

	function prepareFilterParams() {
		var data = {
			page: $page_input.val(),
			limit: $page_limit.val(),
			_csrf: $('input[name="_csrf"]').val()
		};

		data['filter'] = {};
		data['filter_partial'] = {};

		$filter_form.find('[name*="filter["]').each(function(i, item) {
			var $item = $(item);
			var field = $item.data('field');
			var value = $item.val();

			if (field == 'region_full_code')
			{
				var region_info = value.split('|');
				data['filter']['region_country_iso3'] = region_info[0];
				data['filter']['region_code'] = region_info[1];
			}
			else if (!$item.is(':checkbox') || $item.prop('checked'))
			{
				data['filter'][field] = value;
			}
		});

		$filter_form.find('[name*="filter_partial["]').each(function(i, item) {
			var $item = $(item);
			data['filter_partial'][$item.data('field')] = $item.val();
		});

		return data;
	}
});
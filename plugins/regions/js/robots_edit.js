jQuery(function($) {
	var $form = $('.js-robots_form');
	var $domain_select = $('.js-domain_select');

	var $textarea = $('.js-robots_textarea');
	var $textarea_backup = $('.js-robots_backup_textarea');

	var $backup_wrap = $('.js-robots_backup_wrap');

	$domain_select.on('change', function() {
		var $status_block = $form.find('.js-select-box__status');
		changeStatus($status_block, 'loading');
		toggledDomainCheckboxes(false);

		$.ajax({
			url: '?plugin=regions&action=robots',
			type: 'GET',
			data: {
				domain: $domain_select.val()
			},
			complete: function(xhr, status) {
				if (status === 'success')
				{
					updateTextarea(xhr.responseText);

					if ($domain_select.val() == "")
					{
						toggledDomainCheckboxes(true);
					}

					changeStatus($status_block, 'success', 1000);
				}
				else
				{
					changeStatus($status_block, 'error');
				}
			}
		});
	});

	$form.on('submit', function() {
		var $status_block = $form.find('.js-submit-box__status');
		changeStatus($status_block, 'loading');

		var domains = $('.js-domains:checked').map(function(i, item) {return $(item).val()}).toArray();
		var selected_domain = $domain_select.val();

		$.ajax({
			url: $form.prop('action'),
			type: 'POST',
			data: {
				is_submit: true,
				domain: selected_domain,
				robots_content: $textarea.val(),
				domains: domains
			},
			complete: function(xhr, status) {
				if (status === 'success')
				{
					changeStatus($status_block, 'success', 1000);
					domains.forEach(function(domain, i) {
						$form.find('.js-domains[value="' + domain + '"]').closest('label').removeClass('is_custom');
						$domain_select.find('option[value="' + domain + '"]').removeClass('is_custom');
					});

					if (selected_domain !== '')
					{
						$form.find('.js-domains[value="' + selected_domain + '"]').prop('checked', false).closest('label').addClass('is_custom');
						$domain_select.find('option[value="' + selected_domain + '"]').addClass('is_custom');
					}
				}
				else
				{
					changeStatus($status_block, 'error');
				}
			}
		});

		return false;
	});

	$('.js-domain_checkbox_block .js-mass_select').on('click', function() {
		var $this = $(this);
		var action = $this.data('action');

		switch (action)
		{
			case 'all':
				$form.find('.js-domains').prop('checked', true);
				break;
			case 'none':
				$form.find('.js-domains').prop('checked', false);
				break;
			case 'invert':
				$form.find('.js-domains').each(function(i, item) {
					var $item = $(item);
					$item.prop('checked', !$item.prop('checked'));
				});
				break;
		}

		return false;
	});

	$('.js-copy_backup').on('click', function() {
		$textarea.val($textarea_backup.val());

		return false;
	});

	function updateTextarea(html) {
		var $html = $(html);

		var $new_textarea = $html.find('.js-robots_textarea');
		$textarea.val($new_textarea.val());
		$textarea.prop('placeholder', $new_textarea.prop('placeholder'));

		var backup_val = $html.find('.js-robots_backup_textarea').val();
		$textarea_backup.val(backup_val);

		$backup_wrap.toggleClass('hidden', !backup_val);
	}

	function changeStatus($item, status, timer)
	{
		$item.find('.form-status').addClass('form-status_hidden');
		$item.find('.form-status_' + status).removeClass('form-status_hidden');
		timer = timer ? timer : false;

		if (timer)
		{
			$item.data('timer', setTimeout(function () {
				$item.find('.form-status').addClass('form-status_hidden');
			}, timer));
		}
	}

	function toggledDomainCheckboxes(toggle)
	{
		$form.find('.js-checkbox').prop('checked', toggle);
		$form.find('.js-domain_checkbox_block').toggle(toggle);
	}
});
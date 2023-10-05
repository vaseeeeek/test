<?php

class shopRegionsInitShippingFormChangeMonitoringHandlerAction implements shopRegionsIHandlerAction
{
	public function execute($handler_params)
	{
		wa()->getResponse()->addJs('wa-content/js/jquery-plugins/jquery.cookie.js');

		$routing = new shopRegionsRouting();
		$current_city = $routing->getCurrentCity();

		$settings = new shopRegionsSettings();

		if (!$current_city || !$settings->auto_select_city_enable)
		{
			return '';
		}

		return '
<script>
jQuery(function($) {
	if ($.cookie(\'shop_region_remember_address\'))
	{
		return;
	}

	var selector = \'[name$="[address.shipping][city]"], [name$="[address.shipping][region]"], [name$="[address.shipping][country]"]\';
	var $change_listener = $(document).on(\'change\', selector, function() {
		$.cookie(\'shop_region_remember_address\', \'1\', {expires: 200, path: \'/\'});
		$change_listener.off(\'change\');
	});
});
</script>';
	}
}

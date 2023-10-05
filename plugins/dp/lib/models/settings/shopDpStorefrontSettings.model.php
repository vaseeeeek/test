<?php

class shopDpStorefrontSettingsModel extends shopDpComponentSettingsModel
{
	protected $table = 'shop_dp_storefront_settings';
	protected $required = array(
		'country',
		'region',
		'city'
	);
	protected $component_id = 'storefront_id';
}
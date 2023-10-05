<?php
return array(
	'shop_dp_points' => array(
		'hash' => array('varchar', 32, 'null' => 0),
		'search_hash' => array('varchar', 32),
		'shipping_id' => array('int', 11, 'null' => 0),
		'service' => array('varchar', 32),
		'fixed_service' => array('varchar', 32),
		'storefront_id' => array('varchar', 255),
		'custom' => array('int', 1, 'null' => 0, 'default' => '0'),
		'code' => array('varchar', 255, 'null' => 0),
		'country_code' => array('varchar', 3, 'null' => 0),
		'country_name' => array('varchar', 255),
		'region_code' => array('varchar', 8, 'null' => 0),
		'region_name' => array('varchar', 255),
		'city_code' => array('varchar', 8),
		'city_name' => array('varchar', 255, 'null' => 0),
		'address' => array('varchar', 255, 'null' => 0),
		'full_address' => array('text'),
		'name' => array('varchar', 255),
		'address_comment' => array('text'),
		'phone' => array('varchar', 24),
		'email' => array('varchar', 255),
		'note' => array('text'),
		'coord_x' => array('float'),
		'coord_y' => array('float'),
		'dressing_room' => array('int', 1, 'null' => 0, 'default' => '0'),
		'cashless' => array('int', 1, 'null' => 0, 'default' => '0'),
		'nearest_station' => array('varchar', 255),
		'metro_station' => array('varchar', 255),
		'worktime_string' => array('text'),
		'type' => array('varchar', 255),
		':keys' => array(
			'PRIMARY' => 'hash',
			'search_hash_shipping_id' => array('search_hash', 'shipping_id'),
		),
	),
	'shop_dp_points_worktime' => array(
		'point_hash' => array('varchar', 32, 'null' => 0),
		'day' => array('int', 1, 'null' => 0),
		'period' => array('varchar', 11),
		':keys' => array(
			'PRIMARY' => array('point_hash', 'day'),
		),
	),
	'shop_dp_settings' => array(
		'name' => array('varchar', 50, 'null' => 0),
		'value' => array('text'),
		':keys' => array(
			'PRIMARY' => 'name',
		),
	),
	'shop_dp_shipping' => array(
		'id' => array('int', 11, 'null' => 0),
		'hash' => array('varchar', 32, 'null' => 0, 'default' => '0'),
		'service' => array('varchar', 32, 'null' => 0),
		'update_datetime' => array('datetime'),
		':keys' => array(
			'PRIMARY' => array('id', 'hash'),
		),
	),
	'shop_dp_storefront_groups' => array(
		'id' => array('int', 11, 'null' => 0),
		'storefronts' => array('text', 'null' => 0),
		':keys' => array(
		),
	),
	'shop_dp_storefront_settings' => array(
		'storefront_id' => array('varchar', 255, 'null' => 0),
		'name' => array('varchar', 50, 'null' => 0),
		'value' => array('longtext'),
		':keys' => array(
			'PRIMARY' => array('storefront_id', 'name'),
		),
	),
	'shop_dp_theme_settings' => array(
		'theme_id' => array('varchar', 255, 'null' => 0),
		'name' => array('varchar', 50, 'null' => 0),
		'value' => array('text'),
		':keys' => array(
			'PRIMARY' => array('theme_id', 'name'),
		),
	),
);

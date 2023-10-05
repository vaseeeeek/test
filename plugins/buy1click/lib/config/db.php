<?php
return array(
	'shop_buy1click_settings' => array(
		'name' => array('varchar', 50, 'null' => 0),
		'value' => array('text'),
		':keys' => array(
			'PRIMARY' => 'name',
		),
	),
	'shop_buy1click_storefront_settings' => array(
		'storefront_id' => array('varchar', 50, 'null' => 0),
		'name' => array('varchar', 50, 'null' => 0),
		'value' => array('text'),
		':keys' => array(
			'PRIMARY' => array('storefront_id', 'name'),
		),
	),
	'shop_buy1click_temp_cart' => array(
		'code' => array('varchar', 50, 'null' => 0),
		'last_update' => array('datetime'),
		':keys' => array(
			'PRIMARY' => 'code',
		),
	),
);

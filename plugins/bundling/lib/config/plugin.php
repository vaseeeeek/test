<?php
return array (
	'name' => _wp('Product Bundles'),
	'description' => _wp('Bundles with accessories for each product'),
	'img' => 'img/bundling.png',
	'icon' => array(
		64 => 'img/bundling.64.png'
	),
	'version' => '4.2.1',
	'vendor' => '1015472',
	'frontend' => true,
	'handlers' => 
	array(
		'frontend_product' => 'frontendProduct',
		'backend_product' => 'backendProduct',
		'backend_products' => 'backendProducts',
		'order_calculate_discount' => 'orderCalculateDiscount'
	),
);

<?php
return array (
	'name' => _wp('Complex prices'),
	'description' => _wp('Advanced management of wholesale and other prices'),
	'img' => 'img/complex.png',
	'version' => '1.4.10',
	'vendor' => '1015472',
	'frontend' => true,
	'handlers' => 
	array(
		'routing' => 'routing',

		'backend_order_edit' => 'backendOrderEdit',
		'backend_product_edit' => 'backendProductEdit',
		'backend_product_sku_settings' => 'backendProductSkuSettings',
		'frontend_head' => 'frontendHead',
		'frontend_product' => 'frontendProduct',
		'frontend_products' => 'frontendProducts',
		'products_export' => 'productsExport',
		'product_custom_fields' => 'productCustomFields',
		'product_save' => 'productSave',
		'products_collection' => 'productsCollection',
		
		'cart_add' => 'cartAdd',
		'cart_delete' => 'cartDelete',

		'frontend_order_cart_vars' => 'frontendOrderCartVars',
		'frontend_order' => 'frontendOrder'
	),
);

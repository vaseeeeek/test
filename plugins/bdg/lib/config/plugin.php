<?php

return array(
	'name' => 'Ярлыки',
	'description' => '',
	'vendor' => '929600',
	'version' => '1.3.008',
	'img' => 'img/bdg.png',
	'shop_settings' => true,
	'frontend' => true,
	'handlers' => array(
		'backend_products' => 'backendProducts',
		'backend_product' => 'backendProduct',
		'product_delete' => 'productDelete',
		'frontend_head' => 'frontendHead',
	),
);
//EOF
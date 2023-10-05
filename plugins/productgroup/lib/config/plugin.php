<?php

return array(
	'name' => 'Группы товаров',
	'description' => 'Объединяйте товары в единые группы по параметрам!',
	'img' => 'img/plugin.png',
	'vendor' => '934303',
	'version' => '1.1',
	'shop_settings' => true,
	'frontend' => true,
	'handlers' => array(
		'backend_product' => 'handleBackendProduct',
		'backend_products' => 'handleBackendProductsList',
		'product_delete' => 'handleProductDelete',
		'frontend_head' => 'handleFrontendHead',
		'frontend_product' => 'handleFrontendProduct',
	),
);
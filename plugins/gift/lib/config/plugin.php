<?php

return array(
	'name' => 'Подарок к товару',
	'description' => '',
	'vendor' => '929600',
	'version' => '1.3',
	'img' => 'img/gift.png',
	'shop_settings' => true,
	'frontend' => true,
	'handlers' => array(
		'frontend_cart' => 'frontendCart',
		'frontend_checkout' => 'frontendCheckout',
		'order_action.create' => 'orderActionCreate',
		'backend_product' => 'backendProduct',
		'frontend_product' => 'frontendProduct',
		'backend_order' => 'backendOrder',
	),
);
//EOF
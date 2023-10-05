<?php
return array (
	'name' => 'Нормальная маска для телефона',
	'img' => 'img/phonemask.png',
	'version' => '3.5.0',
	'vendor' => '1200329',
	'custom_settings' => false,
	'handlers' => array(
		'frontend_order' => 'frontendOrder',
		'frontend_checkout' => 'frontendCheckout',
		'frontend_product' => 'frontendProduct',
		'frontend_cart'  => 'frontendCart',
		'frontend_footer'  => 'frontendFooter',
		'backend_order_edit'  => 'backendOrderEdit',
		'backend_menu' => 'backendMenu',
	),
);

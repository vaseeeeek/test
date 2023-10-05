<?php

return array(
	'name' => 'Уведомление о поступлении',
	'description' => '',
	'version' => '3.2.2',
	'frontend'    => true,
	'shop_settings' => true,
	'vendor' => '955450',
	'img' => 'img/logo.png',
	'handlers' => array(
		'frontend_head' => 'frontend_head',
		'frontend_product' => 'frontend_product',
		'product_delete'   => 'product_delete',
		'backend_reports' => 'backend_reports',
		'product_save' => 'product_save',
		'backend_product' => 'backend_product',
		'product_mass_update' => 'product_mass_update',
	)
);
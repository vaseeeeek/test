<?php
return array(
	'name' => 'Информация о доставке и оплате',
	'description' => 'на информационных страницах и в карточках товара',
	'img' => 'img/dp.png',
	'version' => '1.18.0',
	'vendor' => '934303',
	'frontend' => true,
	'custom_settings' => true,
	'handlers' => array(
		'frontend_head' => 'frontendHead',
		'frontend_product' => 'frontendProduct'
	),
);

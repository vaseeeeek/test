<?php

return array(
	'name' => 'Купить в 1 клик',
	'description' => 'Быстрая покупка товаров из карточки товара и корзины',
	'img' => 'img/icon.png',
	'shop_settings' => true,
	'version' => '1.28.0',
	'vendor' => '934303',
	'handlers' => array(
		'routing' => 'getRoutingRules',
		'frontend_head' => 'handleFrontendHead',
		'frontend_product' => 'handleFrontendProduct',
		'frontend_cart' => 'handleFrontendCart',
		'backend_order' => 'handleBackendOrder',
		'backend_reports_channels' => 'handleBackendReportsChannels',
		'order_action.edit' => 'handleEditOrderAction',
	),
);

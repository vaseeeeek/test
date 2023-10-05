<?php

return array(
	'courier' => array(
		'name' => 'Курьер',
		'service_title' => 'Курьерская доставка',
		'service_description' => 'Можно указать зоны доставки и установить индивидуальные для каждой зоны стоимости курьерской доставки',
		'settings' => true,
		'type' => 'courier',
		'plugins' => array('courier', 'tkkit', 'sydsek', 'bxb', 'boxberry'),
		'plugins_settings' => array(
			'tkkit' => array(
				'delivery' => array(
					'method' => 'array_one_key_not_empty',
					'value' => 'courier'
				)
			),
			'sydsek' => array(
				'delivery_methods' => array(
					'method' => 'one_in_array',
					'value' => 'to_door'
				)
			),
			'bxb' => array(
				'delivery_methods' => array(
					'method' => 'one_in_array',
					'value' => 'courier'
				)
			),
			'boxberry' => array(
				'courier_mode' => array(
					'method' => 'allowed_values',
					'value' => array('all', 'prepayment'),
				)
			),
		)
	),
	'store' => array(
		'name' => 'Интернет-магазин',
		'placemark' => 'source:/icons/store.svg',
		'color' => '#1f9bd7',
		'service_title' => 'Собственный список пунктов выдачи',
		'service_description' => 'Установить для этого способа доставки пункты выдачи интернет-магазина',
		'settings' => true,
		'type' => 'points',
		'plugins' => array('pickup', 'regionalpickup', 'pvz')
	),
	'pochta' => array(
		'name' => 'Почта России',
		'service_title' => 'Почта России',
		'settings' => false,
		'type' => 'pochta',
		'plugins' => array('pochta', 'allpr', 'russianpost', 'russianpostworld', 'simplerussianpost'),
		'set_settings' => array(
			'allpr' => array(
				'shipping_calculation_product' => 'general'
			)
		)
	),
	'cdek' => array(
		'name' => 'СДЭК',
		'image' => 'source:/services/cdek.png',
		'placemark' => 'source:/icons/cdek.svg',
		'color' => '#6baa10',
		'service_title' => 'Пункты выдачи СДЭК',
		'calculate' => true,
		'settings' => false,
		'type' => 'points',
		'plugins' => array('sydsek', 'cdek'),
		'plugins_settings' => array(
			'sydsek' => array(
				'delivery_methods' => array(
					'method' => 'in_array',
					'value' => 'to_sklad'
				)
			)
		)
	),
	'kit' => array(
		'name' => 'ТК КИТ',
		'image' => 'source:/services/kit.png',
		'placemark' => 'source:/icons/kit.svg',
		'color' => '#0c549f',
		'service_title' => 'Пункты выдачи ТК КИТ',
		'calculate' => true,
		'settings' => false,
		'type' => 'points',
		'plugins' => array('tkkit'),
		'plugins_settings' => array(
			'tkkit' => array(
				'delivery' => array(
					'method' => 'array_key_not_empty',
					'value' => 'terminal'
				)
			)
		)
	),
	'dpd' => array(
		'name' => 'DPD',
		'image' => 'source:/services/dpd.png',
		'placemark' => 'source:/icons/dpd.svg',
		'color' => '#eb0638',
		'service_title' => 'Пункты выдачи DPD',
		'calculate' => true,
		'settings' => true,
		'type' => 'points',
		'plugins' => array('mydpd', 'axidpd'),
		'plugins_settings' => array(
			'mydpd' => array(
				'delivery_type' => array(
					'method' => 'array_key_not_empty',
					'value' => 'point'
				)
			)
		),
		'take_settings' => array(
			'mydpd' => array(
				'client_number' => array(
					'method' => 'copy',
					'field' => 'number'
				),
				'client_key' => array(
					'method' => 'copy',
					'field' => 'key'
				)
			)
		)
	),
	'boxberry' => array(
		'name' => 'Боксберри',
		'image' => 'source:/services/boxberry.png',
		'placemark' => 'source:/icons/boxberry.svg',
		'color' => '#ed1651',
		'service_title' => 'Пункты выдачи Boxberry',
		'type' => 'points',
		'settings' => true,
		'plugins' => array('bxb', 'axibxb', 'boxberry'),
		'plugins_settings' => array(
			'bxb' => array(
				'delivery_methods' => array(
					'method' => 'in_array',
					'value' => 'point',
				)
			),
			'boxberry' => array(
				'point_mode' => array(
					'method' => 'allowed_values',
					'value' => array('all', 'prepayment'),
				)
			),
		),
		'take_settings' => array(
			'bxb' => array(
				'token' => array(
					'method' => 'copy',
					'field' => 'token'
				)
			),
			'boxberry' => array(
				'token' => array(
					'method' => 'copy',
					'field' => 'token'
				)
			)
		)
	),
	'pickpoint' => array(
		'name' => 'PickPoint',
		'image' => 'source:/services/pickpoint.png',
		'placemark' => 'source:/icons/pickpoint.svg',
		'color' => '#f47822',
		'service_title' => 'Пункты выдачи PickPoint',
		'type' => 'points',
		'plugins' => array('dpickpoint', 'ppoint', 'pickpoint'),
		'settings' => false,
	),
	'pek' => array(
		'name' => 'ПЭК',
		'image' => 'source:/services/pek.png',
		'placemark' => 'source:/icons/pek.svg',
		'color' => '#26206a',
		'service_title' => 'Пункты выдачи ТК ПЭК',
		'settings' => true,
		'type' => 'points',
		'plugins' => array('pecom')
	),
	'easyway' => array(
		'name' => 'EasyWay',
		'image' => 'source:/services/easyway.png',
		'placemark' => 'source:/icons/easyway.svg',
		'color' => '#2FC4FC',
		'service_title' => 'Пункты выдачи EasyWay',
		'settings' => true,
		'type' => 'points',
		'plugins' => array()
	),
	'dellin' => array(
		'name' => 'Деловые Линии',
		'image' => 'source:/services/dellin.png',
		'placemark' => 'source:/icons/dellin.svg',
		'color' => '#F8AC18',
		'service_title' => 'Пункты выдачи ТК Деловые Линии',
		'settings' => true,
		'type' => 'points',
		'plugins' => array()
	),
	'iml' => array(
		'name' => 'IML',
		'image' => 'source:/services/iml.png',
		'placemark' => 'source:/icons/iml.svg',
		'color' => '#ffb94a',
		'service_title' => 'Пункты выдачи IML',
		'type' => 'points',
		'plugins' => array('iml')
	),
	'yandexdelivery' => array(
		'name' => 'Яндекс.Доставка',
		'image' => 'source:/services/yandexdelivery.png',
		'placemark' => 'source:/icons/yandexdelivery.svg',
		'color' => '#fcca04',
		'agregator_services_params' => array(
			'status' => '0',
			'params' => array(
				'cdek' => array(
					'service_name' => 'СДЭК',
					'name' => 'СДЭК',
					'image' => 'source:/services/cdek.png',
					'placemark' => 'source:/icons/cdek.svg',
					'color' => '#6baa10'
				),
				'boxberry' => array(
					'service_name' => 'Боксберри',
					'name' => 'Боксберри',
					'image' => 'source:/services/boxberry.png',
					'placemark' => 'source:/icons/boxberry.svg',
					'color' => '#ed1651'
				),
				'dpd' => array(
					'service_name' => 'ТК DPD',
					'name' => 'ТК DPD',
					'image' => 'source:/services/dpd.png',
					'placemark' => 'source:/icons/dpd.svg',
					'color' => '#eb0638'
				),
				'maxipost' => array(
					'service_name' => 'МаксиПост',
					'name' => 'МаксиПост',
					'image' => 'source:/services/maxipost.png',
					'placemark' => 'source:/icons/maxipost.svg',
					'color' => '#134782'
				),
				'pickpoint' => array(
					'service_name' => 'PickPoint',
					'name' => 'PickPoint',
					'image' => 'source:/services/pickpoint.png',
					'placemark' => 'source:/icons/pickpoint.svg',
					'color' => '#f15a25'
				),
				'pek' => array(
					'service_name' => 'ТК ПЭК',
					'name' => 'ТК ПЭК',
					'image' => 'source:/services/pek.png',
					'placemark' => 'source:/icons/pek.svg',
					'color' => '#26206a'
				),
				'strizh' => array(
					'service_name' => 'СТРИЖ',
					'name' => 'СТРИЖ',
					'image' => 'source:/services/strizh.png',
					'placemark' => 'source:/icons/strizh.svg',
					'color' => '#d62226'
				),
				'pochta' => array(
					'service_name' => 'Почта России',
					'name' => 'Почта России',
					'image' => 'source:/services/pochta.png',
					'placemark' => 'source:/icons/pochta.svg',
					'color' => '#d62226'
				),
			)
		),
		'service_title' => 'Пункты выдачи Яндекс.Доставка',
		'settings' => false,
		'type' => 'points',
		'plugins' => array('yandexdelivery')
	)
);

<?php

/*
 * mail@shevsky.com
 */
 
return array(
	'on' => array(
		'value' => 1,
		'control_type' => 'checkbox',
		'title' => 'Плагин включен',
	),
	
	'generate_thumbs' => array(
		'value' => 0,
		'control_type' => 'checkbox',
		'title' => 'Генерировать миниатюры фотографий при загрузке',
	),
	
	'features' => array(
		'value' => array(),
		'control_type' => 'groupbox',
		'title' => 'Характеристики',
		'options_callback' => array(
			'shopMassupdatingPlugin',
			'getFeatures'
		),
		'description' => 'Выберите здесь характеристики, которые Вы хотите видеть по умолчанию в окне редактирования характеристик.'
	),
	
	'debug' => array(
		'value' => 0,
		'control_type' => 'checkbox',
		'title' => 'Вести лог ошибок',
	),
);
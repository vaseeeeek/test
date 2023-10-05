<?php

$badges = array(
	array(
		'name' => 'Цена снижена',
		'color' => '#dd1624',
		'code' => '<div class="-b-" c="#dd1624">Цена снижена</div>',
	),
	array(
		'name' => 'Хит продаж',
		'color' => '#a616e2',
		'code' => '<div class="-b-" c="#a616e2">Хит продаж</div>',
	),
	array(
		'name' => 'Новинка',
		'color' => '#1cbe29',
		'code' => '<div class="-b-" c="#1cbe29">Новинка</div>',
	),
	array(
		'name' => 'Распродажа',
		'color' => '#feb056',
		'code' => '<div class="-b-" c="#feb056">Распродажа</div>',
	),
);

$model = new shopBdgPluginBadgeModel;
if ( $model->query('SELECT * FROM shop_bdg_badge WHERE 1')->count() == 0 )
	foreach ( $badges as $data )
		$model->insert($data);

$from = wa()->getDataPath("plugins/bdg/", true, 'shop').'badge.css';
$to = wa()->getDataPath("plugins/bdg/_/css/", true, 'shop').'shopBdgPlugin.css';
if ( file_exists($from) )
	copy($from,$to);
<?php

return array(
	'shop_bdg_badge' => array(
		'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
		'name' => array('varchar', 255),
		'color' => array('varchar', 7),
		'code' => array('text'),
		':keys' => array(
			'PRIMARY' => 'id'
		),
	),
	'shop_bdg_product_badge' => array(
		'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
		'badge_id' => array('int', 11, 'unsigned' => 1, 'null' => 0),
		'product_id' => array('int', 11, 'unsigned' => 1, 'null' => 0),
		':keys' => array(
			'PRIMARY' => 'id',
			'badge_id' => 'badge_id',
			'product_id' => 'product_id',
		),
	),
);
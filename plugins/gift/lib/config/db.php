<?php
return array(
	'shop_gift_product_gift' => array(
		'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
		'product_id' => array('int', 11, 'null' => 0),
		'gift_id' => array('int', 11, 'null' => 0),
		'order' => array('int', 11, 'null' => 0, 'default' => '0'),
		':keys' => array(
			'PRIMARY' => 'id',
			'product_id' => array('product_id'),
		),
	),
);
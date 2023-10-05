<?php

$model = new waModel();

try {
	$model->query('SELECT route_url FROM shop_arrived');
} catch (waDbException $e) {
	$sql = 'ALTER TABLE `shop_arrived` ADD `route_url` VARCHAR(255) NOT NULL AFTER `domain`';
	$model->exec($sql);
}
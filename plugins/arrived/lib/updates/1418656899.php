<?php

$model = new waModel();

try {
	$model->query('SELECT sended FROM shop_arrived');
} catch (waDbException $e) {
	$sql = 'ALTER TABLE `shop_arrived` ADD `sended` TINYINT(1) NOT NULL DEFAULT "0"';
	$model->exec($sql);
}
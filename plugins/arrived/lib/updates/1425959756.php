<?php

$model = new waModel();

try {
	$model->query('SELECT expired,date_sended FROM shop_arrived');
} catch (waDbException $e) {
	$sql = 'ALTER TABLE `shop_arrived` ADD `date_sended` DATETIME NOT NULL AFTER `sended`, ADD `expired` INT(1) NOT NULL DEFAULT "0" AFTER `date_sended`';
	$model->exec($sql);
}
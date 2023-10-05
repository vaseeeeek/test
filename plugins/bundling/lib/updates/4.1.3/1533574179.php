<?php

$model = new waModel();

try {
	$model->exec("ALTER TABLE `shop_bundling_products` ADD `params` TEXT NULL DEFAULT NULL;");
} catch (waDbException $e) {
	waLog::log($e, 'bundling.update.log');
}
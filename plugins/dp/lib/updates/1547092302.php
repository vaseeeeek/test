<?php

// v1.4

$model = new waModel();

try {
	$model->exec("ALTER TABLE `shop_dp_points` ADD COLUMN `storefront_id` VARCHAR(255) NULL;");
	$model->exec("ALTER TABLE `shop_dp_points` ADD COLUMN `custom` INT(1) NOT NULL DEFAULT '0';");
	$model->exec("ALTER TABLE `shop_dp_points` ADD INDEX `search_hash_shipping_id` (`search_hash`, `shipping_id`);");
} catch(waDbException $e) {}

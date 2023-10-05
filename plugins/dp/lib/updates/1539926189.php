<?php

// v1.1

$model = new waModel();

try {
	$model->exec("ALTER TABLE `shop_dp_points` CHANGE `country_name` `country_name` VARCHAR(255) NULL DEFAULT NULL;");
	$model->exec("ALTER TABLE `shop_dp_points` CHANGE `region_name` `region_name` VARCHAR(255) NULL DEFAULT NULL;");
	$model->exec("ALTER TABLE `shop_dp_points` CHANGE `nearest_station` `nearest_station` VARCHAR(255) NULL DEFAULT NULL;");
	$model->exec("ALTER TABLE `shop_dp_points` CHANGE `metro_station` `metro_station` VARCHAR(255) NULL DEFAULT NULL;");
} catch(waDbException $e) {}
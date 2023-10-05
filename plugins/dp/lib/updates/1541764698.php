<?php

// v1.3

$model = new waModel();

try {
	$model->exec("ALTER TABLE `shop_dp_points` ADD COLUMN `type` VARCHAR(255) NULL;");
} catch(waDbException $e) {}
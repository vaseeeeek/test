<?php

// v1.2

$model = new waModel();

try {
	$model->exec("ALTER TABLE `shop_dp_points` CHANGE `coord_x` `coord_x` FLOAT NULL, CHANGE `coord_y` `coord_y` FLOAT NULL;");
} catch(waDbException $e) {}
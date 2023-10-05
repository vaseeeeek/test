<?php

$model = new waModel();
$model->exec("ALTER TABLE `shop_product` ADD `complex_plugin_toggle_prices` INT(11) NOT NULL DEFAULT '0';");
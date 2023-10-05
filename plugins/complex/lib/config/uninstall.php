<?php
$price_model = new shopComplexPluginPriceModel();

$price_model->exec("ALTER TABLE `shop_product` DROP `complex_plugin_toggle_prices`;");

foreach($price_model->getAll() as $price)
	$price_model->deletePrice($price['id']);
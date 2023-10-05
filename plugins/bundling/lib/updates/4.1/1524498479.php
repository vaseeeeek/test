<?php

$model = new waModel();

try {
    $model->exec("CREATE TABLE IF NOT EXISTS `shop_bundling_categories` (
  `category_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `multiple` int(1) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;");
	
	$model->exec("ALTER TABLE `shop_bundling_products` CHANGE `default_quantity` `default_quantity` DECIMAL(15,4) NOT NULL DEFAULT '1.0000';");
	
	$model->exec("ALTER TABLE `shop_bundling_bundles` CHANGE `title` `title` VARCHAR(255) NULL DEFAULT NULL;");
	
	$model->exec("ALTER TABLE `shop_bundling_bundles` ADD `subcategories` INT(1) NOT NULL DEFAULT '0';");
} catch (waDbException $e) {
	waLog::log($e, 'bundling.update.log');
}
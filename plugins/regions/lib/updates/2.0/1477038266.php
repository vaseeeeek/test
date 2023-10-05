<?php

$model = new waModel();

try
{
	$model->query('SELECT 1 FROM `shop_regions_city_settings` LIMIT 0');
}
catch (Exception $e)
{
	$create = '
CREATE TABLE `shop_regions_city_settings` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`city_id` INT NOT NULL,
	`storefront_settings` TEXT NOT NULL,
	PRIMARY KEY (`id`),
	INDEX `city_id` (`city_id`)
)
COLLATE=\'utf8_general_ci\'
ENGINE=MyISAM
';

	$model->exec($create);
}
<?php

$model = new waModel();

try
{
	$model->query('SELECT 1 FROM `shop_regions_user_environment` LIMIT 0');
}
catch (Exception $e)
{
	$create = 'CREATE TABLE `shop_regions_user_environment` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`key` VARCHAR(32) NOT NULL DEFAULT \'\',
	`cookies` TEXT,
	`time` INT(11) NOT NULL,
	PRIMARY KEY (`id`),
	INDEX `key` (`key`),
	INDEX `time` (`time`)
)
COLLATE=\'utf8_general_ci\'
ENGINE=MyISAM';

	$model->exec($create);
}
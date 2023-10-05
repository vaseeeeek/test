<?php

$model = new waModel();

try
{
	$model->query('SELECT `id` FROM `shop_regions_robots_option` LIMIT 0');
}
catch (Exception $e)
{
	$create = '
CREATE TABLE `shop_regions_robots_option` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`domain` VARCHAR(255) NOT NULL,
	`is_custom` TINYINT NOT NULL DEFAULT \'0\',
	`robots_last_modified_time` INT NOT NULL,
	PRIMARY KEY (`id`),
	INDEX `domain` (`domain`)
)
COLLATE=\'utf8_general_ci\'
ENGINE=MyISAM
;
';

	$model->exec($create);
}
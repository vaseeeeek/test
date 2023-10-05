<?php

$model = new waModel();

try
{
	$model->query('SELECT `is_default_for_storefront` FROM `shop_regions_city`');
}
catch (Exception $e)
{
	$alter = 'ALTER TABLE `shop_regions_city`
	ADD COLUMN `is_default_for_storefront` TINYINT(1) NULL DEFAULT 0,
	ADD INDEX `is_default_for_storefront` (`is_default_for_storefront`);';

	$update = 'UPDATE `shop_regions_city` `src`, (SELECT `id` FROM `shop_regions_city` GROUP BY `storefront`) `ids`
	SET `src`.`is_default_for_storefront` = 1
	WHERE `src`.`id` = `ids`.`id`;';

	$model->exec($alter);
	$model->exec($update);
}
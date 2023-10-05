<?php

$model = new waModel();

try
{
	$model->exec('SELECT `create_datetime` FROM `shop_regions_city` LIMIT 1');
}
catch (Exception $e)
{
	$alter_sql = '
ALTER TABLE `shop_regions_city`
	ADD COLUMN `create_datetime` DATETIME NULL AFTER `route`,
	ADD COLUMN `update_datetime` DATETIME NULL AFTER `create_datetime`,
	ADD INDEX `create_datetime` (`create_datetime`),
	ADD INDEX `update_datetime` (`update_datetime`);
';

	$update_datetime_sql = '
UPDATE `shop_regions_city`
SET `create_datetime` = :t,
	`update_datetime` = :t;
';

	$model->exec($alter_sql, array('t' => date('Y-m-d H:i:s')));
}
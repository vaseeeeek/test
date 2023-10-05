<?php

$model = new waModel();

try
{
	$model->exec('SELECT image_size FROM shop_productgroup_group LIMIT 1');
}
catch (Exception $e)
{
	$model->exec('
ALTER TABLE `shop_productgroup_group`
	ADD COLUMN `image_size` VARCHAR(20) NULL DEFAULT NULL AFTER `related_feature_id`;
');
}
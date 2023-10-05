<?php

$model = new waModel();

try
{
	$model->exec('
ALTER TABLE `shop_regions_page_template`
	CHANGE COLUMN `ignore_default` `ignore_default` ENUM(\'Y\',\'N\') NOT NULL DEFAULT \'N\' AFTER `content`
');
}
catch (waException $exception)
{}

$cleaner = new shopRegionsCleaner();
$cleaner->clean();
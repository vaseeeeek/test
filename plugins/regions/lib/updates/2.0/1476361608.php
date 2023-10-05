<?php

$model = new waModel();

try
{
	$model->query('SELECT `sort` FROM `shop_regions_city`');
}
catch (Exception $e)
{
	$alter = 'ALTER TABLE `shop_regions_city`
	ADD COLUMN `sort` INT NOT NULL DEFAULT 0,
	ADD INDEX `sort` (`sort`);
';
	$init_sort_by_name = '
UPDATE `shop_regions_city` `t1`, (
	SELECT `id`, @rn:=@rn+1 AS `row_index`
	FROM (
		SELECT `id`
		FROM `shop_regions_city`
		ORDER BY FIND_IN_SET(`id`, (
			SELECT GROUP_CONCAT(`id` SEPARATOR \',\')
			FROM (SELECT `id`, 1 as `group` FROM `shop_regions_city` ORDER BY `name`) `t`
			GROUP BY `t`.`group`
		))
	) `t1`, (SELECT @rn:=0) `t2`
) `t2`
SET `t1`.`sort` = `t2`.`row_index`
WHERE `t1`.`id` = `t2`.`id`
';

	$model->exec($alter);
	$model->exec($init_sort_by_name);
}


$settings_model = new waAppSettingsModel();
$value = $settings_model->get('shop.regions', 'window_sort');

if (empty($value))
{
	$settings_model->set('shop.regions', 'window_sort', shopRegionsCityModel::WINDOW_SORT_BY_NAME);
}
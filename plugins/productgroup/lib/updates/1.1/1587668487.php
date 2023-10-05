<?php

$model = new waModel();

try
{
	$model->exec('SELECT 1 FROM `shop_productgroup_group_settings` LIMIT 1');
}
catch (Exception $e)
{
	$model->exec('
CREATE TABLE `shop_productgroup_group_settings` (
	`group_id` INT UNSIGNED NOT NULL,
	`scope` VARCHAR(10) NOT NULL,
	`name` VARCHAR(64) NOT NULL,
	`value` TEXT NULL,
	PRIMARY KEY (`group_id`, `scope`, `name`)
)
COLLATE=\'utf8_general_ci\'
ENGINE=MyISAM
;
');

	$groups = $model->query('
SELECT *
FROM shop_productgroup_group
');

	$setting_insert_query = '
INSERT INTO shop_productgroup_group_settings
(group_id, scope, name, value)
VALUES
(:group_id, :scope, :name, :value)
';

	$settings_scope_migration = array(
		'show_in_stock_only' => 'PRODUCT',
		'show_on_primary_product_only' => 'PRODUCT',
		'image_size' => 'PRODUCT',
	);

	foreach ($groups as $group)
	{
		foreach ($settings_scope_migration as $name => $scope)
		{
			$insert_params = array(
				'group_id' => $group['id'],
				'scope' => $scope,
				'name' => $name,
				'value' => $group[$name],
			);

			$model->exec($setting_insert_query, $insert_params);
		}
	}

	$model->exec('
ALTER TABLE `shop_productgroup_group`
	DROP COLUMN `show_in_stock_only`,
	DROP COLUMN `show_on_primary_product_only`,
	DROP COLUMN `image_size`;
');
}


try
{
	$model->exec('
ALTER TABLE `shop_productgroup_group`
	DROP COLUMN `is_shown_in_category`;
');
}
catch (Exception $e)
{
}
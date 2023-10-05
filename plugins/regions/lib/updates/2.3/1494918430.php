<?php

$model = new waModel();

try
{
	$model->exec('SELECT `domain_id` FROM `shop_regions_city`');
}
catch (waException $e)
{
	$queries = array();

	$queries[] = '
ALTER TABLE `shop_regions_user_environment`
	DROP COLUMN `id`;
';

	$queries[] = '
ALTER TABLE `shop_regions_city_settings`
	DROP COLUMN `id`,
	DROP PRIMARY KEY,
	DROP INDEX `city_id`,
	ADD PRIMARY KEY (`city_id`);
';

	$queries[] = '
ALTER TABLE `shop_regions_city`
	ADD COLUMN `domain_id` INT UNSIGNED NOT NULL AFTER `sort`,
	ADD COLUMN `route` VARCHAR(255) NOT NULL AFTER `domain_id`,
	ADD INDEX `domain_id` (`domain_id`),
	ADD INDEX `is_popular` (`is_popular`),
	ADD INDEX `is_enable` (`is_enable`),
	ADD INDEX `country_iso3` (`country_iso3`),
	ADD INDEX `region_code` (`region_code`),
	ADD INDEX `storefront` (`storefront`);
';

	$queries[] = '
ALTER TABLE `shop_regions_page_template`
	DROP COLUMN `id`,
	ALTER `url` DROP DEFAULT;
';

	$queries[] = '
ALTER TABLE `shop_regions_page_template`
	CHANGE COLUMN `url` `url` VARCHAR(255) NOT NULL FIRST,
	ADD COLUMN `ignore_default` ENUM(\'Y\',\'N\') NOT NULL DEFAULT \'N\' AFTER `content`,
	ADD INDEX `ignore_default` (`ignore_default`),
	ADD PRIMARY KEY (`url`);
';

	$queries[] = '
CREATE TABLE `shop_regions_settings` (
	`name` VARCHAR(64) NOT NULL,
	`value` TEXT NULL,
	PRIMARY KEY (`name`)
)
COLLATE=\'utf8_general_ci\'
ENGINE=MyISAM
';

	$queries[] = '
CREATE TABLE `shop_regions_page_template_excluded_storefront` (
	`page_url` VARCHAR(255) NOT NULL,
	`storefront` VARCHAR(255) NOT NULL,
	`page_route_hash` VARCHAR(40) NOT NULL,
	UNIQUE INDEX `page_route_hash` (`page_route_hash`),
	INDEX `storefront` (`storefront`),
	INDEX `page_url` (`page_url`)
)
COLLATE=\'utf8_general_ci\'
ENGINE=MyISAM
';

	$queries[] = '
ALTER TABLE `shop_regions_param`
	ADD COLUMN `sort` INT UNSIGNED NOT NULL DEFAULT \'0\' AFTER `name`,
	ADD INDEX `sort` (`sort`);
';

	$queries[] = '
UPDATE `shop_regions_param`
SET `sort` = `id`;
';

	foreach ($queries as $query)
	{
		$model->exec($query);
	}



	// проставляем регионам domain_id и route
	wa('site');

	$city_model = new shopRegionsCityModel();
	$domain_model = new siteDomainModel();
	$routing = wa()->getRouting();

	foreach ($domain_model->getAll('id') as $domain_id => $domain_row)
	{
		$shop_routes = $routing->getByApp('shop', $domain_row['name']);
		foreach ($shop_routes as $route)
		{
			$storefront = $domain_row['name'] . '/' . $route['url'];

			$data = array(
				'domain_id' => $domain_id,
				'route' => $route['url'],
			);
			$city_model->updateByField('storefront', $storefront, $data);
		}
	}


	// перенос настроек
	$regions_settings_model = new shopRegionsSettingsModel();
	$app_settings_model = new waAppSettingsModel();

	$settings = $app_settings_model->get('shop.regions');
	unset($settings['update_time']);

	foreach ($settings as $name => $value)
	{
		$success = $regions_settings_model->insert(array(
			'name' => $name,
			'value' => $value,
		), waModel::INSERT_ON_DUPLICATE_KEY_UPDATE);
	}


	// права по-умолчанию
	$contacts_model = new waContactModel();
	$contacts_query = $contacts_model->select('id')->where('is_user = 1')->query();

	foreach ($contacts_query as $contact_row)
	{
		$contact = new waContact($contact_row['id']);
		if (!$contact->isAdmin() && $contact->getRights('shop', 'settings') != 0)
		{
			$contact->setRight('shop', shopRegionsPlugin::EDIT_RIGHT_NAME, 1);
		}
	}
	unset($contacts);
}
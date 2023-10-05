<?php

$cleaner = new shopBuy1clickCleaner();
$cleaner->clean();

$model = new waModel();

$model->query('
create table if not exists shop_buy1click_temp_cart (
	code varchar(50) not null,
	last_update datetime null default null,
	primary key (code)
)
engine=MyISAM;
');

$model->query('
drop table if exists shop_buy1click_placeholder_settings;
');

$model->query('
update shop_buy1click_storefront_settings
set `value` = replace(`value`, \'first_name\', \'firstname\')
where `name` = \'product_form_selected_fields\' or `name` = \'cart_form_selected_fields\';
');

$model->query('
update shop_buy1click_storefront_settings
set `value` = replace(`value`, \'last_name\', \'lastname\')
where `name` = \'product_form_selected_fields\' or `name` = \'cart_form_selected_fields\';
');

$model->query('
update shop_buy1click_storefront_settings
set `value` = replace(`value`, \'middle_name\', \'middlename\')
where `name` = \'product_form_selected_fields\' or `name` = \'cart_form_selected_fields\';
');

// todo понять что он делает. или забить
//$storefront_storage = shopBuy1clickPlugin::getStorageFactory()->createStorefrontStorage();
//$storefronts = $storefront_storage->getAllStorefronts();
//$settings_storage = shopBuy1clickPlugin::getStorageFactory()->createStorefrontSettingsStorage();
//
//foreach ($storefronts as $storefront_id)
//{
//	$settings = $settings_storage->get($storefront_id);
//	$settings_storage->store($settings);
//}

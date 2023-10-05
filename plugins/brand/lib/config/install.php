<?php

$page_storage = new shopBrandPageStorage();
$main_page = $page_storage->getById(shopBrandPageStorage::MAIN_PAGE_ID);

if (!$main_page)
{
	$main_page = $page_storage->getMainPage()->assoc();
	$page_storage->savePages(array($main_page), array());
}

unset($page_storage);
unset($main_page);


$settings_storage = new shopBrandSettingsStorage();
$settings = $settings_storage->getSettings();

if (!$settings->brand_feature_id)
{
	$feature_model = new shopFeatureModel();

	foreach (array('Бренд', 'Брэнд', 'Производитель') as $brand_feature_name)
	{
		$features = $feature_model->getByField('name', $brand_feature_name, true);
		if (count($features) == 1)
		{
			$feature = array_pop($features);

			$settings->brand_feature_id = $feature['id'];

			break;
		}
		elseif (count($features) > 1)
		{
			break;
		}
	}
}


$settings->use_optimized_images = !shopBrandHelper::isServerNginx() && waSystemConfig::systemOption('mod_rewrite');


$settings_storage->store($settings);


$plugin_image_storage = new shopBrandImageStorage();
$plugin_image_storage->createThumbFile();

<?php

try
{
	$cleaner = new shopBuy1clickCleaner();
	$cleaner->clean();
	
	$storefront_settings_model = new shopBuy1clickStorefrontSettingsModel();
	$rows = $storefront_settings_model->getByField(array(
		'name' => 'storefront_is_enabled',
		'value' => '0',
	), true);
	
	foreach ($rows as $row)
	{
		if ($row['storefront_id'] == '*')
		{
			continue;
		}
		
		$storefront_settings_model->deleteByField(array(
			'storefront_id' => $row['storefront_id'],
		));
	}
}
catch (Throwable $ignored)
{

}
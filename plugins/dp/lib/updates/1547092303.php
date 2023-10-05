<?php

// v1.4

$storefront_settings_model = new shopDpStorefrontSettingsModel();
$shipping_settings_values = $storefront_settings_model->getByField(array('name' => 'shipping_settings'), 'storefront_id');

$points_model = new shopDpPointsModel();

$aliases = array(
	'country' => 'country_code',
	'region' => 'region_code',
	'city' => 'city_name'
);

foreach($shipping_settings_values as $storefront_id => $shipping_setting) {
	$value_json = $shipping_setting['value'];
	$value = json_decode($value_json, true);

	if($value === null && json_last_error() !== JSON_ERROR_NONE) {
		continue;
	};

	foreach($value as $shipping_id => $shipping_settings) {
		$is_have_points = isset($shipping_settings['store']['points']);

		if(!$is_have_points) {
			continue;
		};

		$points = $shipping_settings['store']['points'];

		$params = compact('storefront_id', 'shipping_id');
		$params['custom'] = 1;

		foreach($points as $key => $point) {
			$hash = "{$point['country']}:{$point['region']}:{$point['city']}";

			$point['search_hash'] = $hash;
			$point['code'] = $key;

			$points_model->savePoint($point, $params, $aliases);

			// todo удалить пункты выдачи, хранящиеся на данный момент в json в след. обновлении, после того, как можно будет точно быть уверенным, что ничего не сломается после этого этапа
		}
	};
};

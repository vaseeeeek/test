<?php

class shopDpPluginSettingsSaveController extends waJsonController
{
	protected static $settings_model;
	protected static $storefront_groups_model;
	protected $required = array(
		'country' => 'Страна по умолчанию',
		'region' => 'Регион по умолчанию',
		'city' => 'Город по умолчанию'
	);

	public function preExecute()
	{
		self::$settings_model = new shopDpSettingsModel();
		self::$storefront_groups_model = new shopDpStorefrontGroupsModel();
	}

	public function execute()
	{
		$basic_settings_json = waRequest::post('basic_settings');
		$basic_settings = json_decode($basic_settings_json, true);

		$storefronts_settings_json = waRequest::post('storefronts_settings');
		$storefronts_settings = json_decode($storefronts_settings_json, true);

		$custom_points_json = waRequest::post('custom_points');
		if($custom_points_json) {
			$custom_points = json_decode($custom_points_json, true);
		}

		$themes_settings_json = waRequest::post('themes_settings');
		$themes_settings = json_decode($themes_settings_json, true);
		if(array_key_exists('*', $themes_settings))
			unset($themes_settings['*']);

		$templates_json = waRequest::post('templates');
		if($templates_json) {
			$templates = json_decode($templates_json, true);
		}

		$storefront_groups_json = waRequest::post('storefront_groups');
		$storefront_groups = json_decode($storefront_groups_json, true);

		if(isset($storefront_groups)) {
			self::$storefront_groups_model->set($storefront_groups);
		} else {
			self::$storefront_groups_model->truncate();
		}

		if(!$basic_settings || !$storefronts_settings) {
			$this->setError('Настройки не найдены, перезагрузите страницу');
		}

		if(isset($templates)) {
			if(array_key_exists('*', $templates)) {
				unset($templates['*']);
			}

			$templates_instance = new shopDpTemplates();
			$templates_instance->set($templates);
		}

		if(isset($custom_points)) {
			$points_model = new shopDpPointsModel();

			foreach($custom_points as $storefront_id => $shipping_points) {
				if(!$shipping_points) {
					$points_model->deleteCustomPoints(null, $storefront_id);
					continue;
				}

				foreach($shipping_points as $shipping_id => $points) {
					if ($points === null)
					{
						$points_model->deleteCustomPoints($shipping_id, $storefront_id);
					}

					if(!is_array($points)) {
						continue;
					}

					$points_model->deleteCustomPoints($shipping_id, $storefront_id);

					$params = compact('storefront_id', 'shipping_id');
					$params['custom'] = 1;

					foreach($points as $key => $point) {
						$hash = "{$point['country_code']}:{$point['region_code']}:{$point['city_name']}";

						$point['search_hash'] = $hash;
						$point['code'] = $key;

						$points_model->savePoint($point, $params);
					}
				}
			}
		}

		try {
			$settings = array(
				'basic' => $basic_settings,
				'storefronts' => $storefronts_settings,
				'themes' => $themes_settings
			);
			self::$settings_model->set($settings);

			$stylesheet_instance = new shopDpStylesheet($themes_settings);
			$stylesheet_instance->render();
		} catch(Exception $e) {
			$field = $e->getMessage();

			$this->setError(sprintf('Поле <b>"%s"</b> обязательно для заполнения', $this->required[$field]), $field);
		}
	}
}

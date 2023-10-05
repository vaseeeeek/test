<?php

class shopDpPluginSettingsAction extends waViewAction
{
	public function execute()
	{
		$plugin = shopDpPlugin::getInstance('settings');
		$version = floatval($plugin->getVersion());

		$plugin_url = $plugin->getPluginStaticUrl();
		$data_url = wa()->getDataUrl('plugins/dp/data', true, 'shop');
		$settings = $plugin->getSettings();

		$storefronts = $plugin->getEnv()->getStorefronts();
		$storefront_groups = $plugin->getEnv()->getStorefrontGroups($settings);
		$routes = $plugin->getEnv()->getRoutes();

		$themes = $plugin->getEnv()->getThemes();

		$plugin_name = 'dp';

		$shipping_methods = $plugin->getEnv()->getShippingPlugins(true);
		$payment_methods = $plugin->getEnv()->getPaymentPlugins();

		$services_instance = new shopDpServices();
		$services = $services_instance->getAll();

		$points_model = new shopDpPointsModel();
		$custom_points_data = $points_model->getAllCustomPoints();
		$custom_points = $this->prepareCustomPoints($custom_points_data);

		$templates_instance = new shopDpTemplates();
		$templates = $templates_instance->get();
		$template_names = $templates_instance->getNames();

		$currency = shopDpPluginHelper::getCurrencyInfo('shop');

		$is_enabled_regions_plugin = $plugin->getEnv()->isEnabledRegionsPlugin();
		$is_enabled_freedelivery_plugin = $plugin->getEnv()->isEnabledFreedeliveryPlugin();
		$is_enabled_ip_plugin = $plugin->getEnv()->isEnabledIpPlugin();
		$is_available_shop_schedule = $plugin->getEnv()->isAvailableShopSchedule();

		$date_formats = array(
			'human' => wa_date('humandate'),
			'd.m.Y' => date('d.m.Y')
		);

		$vars = compact('plugin_name', 'plugin_url', 'data_url', 'settings', 'templates', 'template_names', 'services', 'shipping_methods', 'payment_methods', 'storefronts', 'themes', 'storefront_groups', 'version', 'routes', 'currency', 'is_enabled_regions_plugin', 'is_enabled_freedelivery_plugin', 'is_enabled_ip_plugin', 'is_available_shop_schedule', 'date_formats', 'custom_points');
		$this->view->assign($vars);
		$this->view->assign('plugin', $vars);
	}

	private function prepareCustomPoints(array $custom_points_data)
	{
		$custom_points = [];
		foreach ($custom_points_data as $storefront => $method_points)
		{
			$custom_points[$storefront] = [];

			foreach ($method_points as $shipping_id => $points)
			{
				$custom_points[$storefront][$shipping_id] = [];

				foreach ($points as $point_key => $point)
				{
					if (isset($point['worktime']) && is_array($point['worktime']))
					{
						foreach (array_keys($point['worktime']) as $worktime_key)
						{
							$point['worktime'][$worktime_key] = is_array($point['worktime'][$worktime_key]) && isset($point['worktime'][$worktime_key]['period'])
								? $point['worktime'][$worktime_key]['period']
								: ':';
						}
					}

					$custom_points[$storefront][$shipping_id][$point_key] = $point;
				}
			}
		}

		return $custom_points;
	}
}

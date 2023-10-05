<?php

class shopDpSettingsFiller
{
	protected static $env;

	public function fillSettings(&$settings, $params)
	{
		$is_fill = false;

		$config = new shopDpServiceConfig();

		$map_params = isset($settings['basic']['map_params']) && is_array($settings['basic']['map_params'])
			? ($settings['basic']['map_params'])
			: array();

		if (!isset($map_params['google_key'])) {
			$google_key = wa()->getMap('google')->getSettings('key');
			if ($google_key) {
				$map_params['google_key'] = $google_key;
			}
		}

		if (!isset($map_params['yandex_key'])) {
			$yandex_key = wa()->getMap('yandex')->getSettings('apikey');
			if ($yandex_key) {
				$map_params['yandex_key'] = $yandex_key;
			}
		}

		if (count($map_params) > 0)
		{
			$settings['basic']['map_params'] = $map_params;
		}

		if(!empty($params['shipping_methods'])) {
			foreach($params['shipping_methods'] as $shipping) {

				/**
				 * Автовключение новых способов доставки
				 */

				if(!isset($settings['storefronts']['*']['shipping_status'][$shipping['id']])) {
					if(empty($settings['storefronts']['*']['shipping_status']))
						$settings['storefronts']['*']['shipping_status'] = array();

					if(!empty($shipping['status']))
						$settings['storefronts']['*']['shipping_status'][$shipping['id']] = true;
				}

				/**
				 * Автоподключение сервисов к способам доставки
				 */

				if(!isset($settings['storefronts']['*']['shipping_service'][$shipping['id']])) {
					if(empty($settings['storefronts']['*']['shipping_service']))
						$settings['storefronts']['*']['shipping_service'] = array();

					$settings['storefronts']['*']['shipping_service'][$shipping['id']] = '';

					foreach($config->config as $service => $params) {
						$plugins = ifset($params, 'plugins', null);
						$is_plugins_array = is_array($plugins);
						if(!$is_plugins_array) {
							continue;
						}

						$is_plugin_in_array = in_array($shipping['plugin'], $plugins);
						if(!$is_plugin_in_array) {
							continue;
						}

						$result = true;

						if(!empty($params['plugins_settings']) && array_key_exists($shipping['plugin'], $params['plugins_settings'])) {
							$plugin_settings = $params['plugins_settings'][$shipping['plugin']];
							$plugin = shopShipping::getPlugin($shipping['plugin'], $shipping['id']);

							foreach($plugin_settings as $plugin_field => $plugin_value_data) {
								$plugin_value_method = $plugin_value_data['method'];
								$plugin_value_inverted = ifempty($plugin_value_data, 'inverted', false);
								$plugin_value = ifset($plugin_value_data, 'value', null);

								if($plugin->$plugin_field !== null) {
									$plugin_source_value = $plugin->$plugin_field;

									switch($plugin_value_method) {
										case 'in_array':
											$result = $result && in_array($plugin_value, $plugin_source_value);
											break;
										case 'one_in_array':
											$result = $result && count($plugin_source_value) == 1 && in_array($plugin_value, $plugin_source_value);
											break;
										case 'array_key_not_empty':
											$result = $result && !empty($plugin_source_value[$plugin_value]);
											break;
										case 'array_one_key_not_empty':
											$result = $result && count($plugin_source_value) == 1 && !empty($plugin_source_value[$plugin_value]);
											break;
										case 'checked':
											$result = $result && $plugin_source_value;
											break;
										case 'allowed_values':
											$result = $result && in_array($plugin_source_value, $plugin_value);
											break;
										default:
											$result = null;
											break;
									}

									if($result !== null && $plugin_value_inverted)
										$result = !$result;
								} else
									$result = false;
							}
						}

						$settings['storefronts']['*']['shipping_service'][$shipping['id']] = $result === true ? $service : '';

						if($result === true) {
							$is_fill = true;

							$settings['storefronts']['*']['shipping_actuality'][$shipping['id']] = '604800';

							if(!empty($params['set_settings']) && array_key_exists($shipping['plugin'], $params['set_settings'])) {
								$set_settings = $params['set_settings'][$shipping['plugin']];

								foreach($set_settings as $settings_field => $settings_value) {
									$settings['storefronts']['*'][$settings_field][$shipping['id']] = $settings_value;
								}
							}

							if(!empty($params['take_settings']) && array_key_exists($shipping['plugin'], $params['take_settings'])) {
								$take_settings = $params['take_settings'][$shipping['plugin']];
								$set_settings = array();
								$plugin = shopShipping::getPlugin($shipping['plugin'], $shipping['id']);

								foreach($take_settings as $plugin_field => $plugin_value_data) {
									$plugin_value_method = $plugin_value_data['method'];
									$plugin_value_field = $plugin_value_data['field'];

									if($plugin->$plugin_field !== null) {
										$plugin_source_value = $plugin->$plugin_field;

										switch($plugin_value_method) {
											case 'copy':
												$set_settings[$plugin_value_field] = $plugin_source_value;
												break;
										}
									}
								}

								if(!empty($set_settings)) {
									foreach($set_settings as $field => $value) {
										if(empty($settings['storefronts']['*']['shipping_settings']))
											$settings['storefronts']['*']['shipping_settings'] = array();
										if(empty($settings['storefronts']['*']['shipping_settings'][$shipping['id']][$service]))
											$settings['storefronts']['*']['shipping_settings'][$shipping['id']] = array(
												$service => array()
											);

										$settings['storefronts']['*']['shipping_settings'][$shipping['id']][$service][$field] = $value;
									}
								}
							}
						}
					}
				}

				/**
				 * Автоподстановка сроков доставки
				 */

				$is_shipping_rate_calculated = !empty($shipping['dp_rates']);
				$is_shipping_estimated_date_calculated = !empty($shipping['dp_rates']['est_delivery']);
				$is_empty_shipping_date = empty($settings['storefronts']['*']['shipping_date'][$shipping['id']]);

				if($is_shipping_estimated_date_calculated && $is_empty_shipping_date) {
					$estimated_date = $shipping['dp_rates']['est_delivery'];

					if(is_string($estimated_date))
						$estimated_date = array($estimated_date);

					$shipping_date = array(
						'format' => 'fixed',
						'value' => array(
							'fixed' => $estimated_date
						)
					);
				} elseif(!$is_shipping_rate_calculated && $is_empty_shipping_date) {
					$shipping_date = array(
						'format' => 'calculate',
						'value' => array()
					);
				}

				if(!empty($shipping_date)) {
					if(empty($settings['storefronts']['*']['shipping_date']))
						$settings['storefronts']['*']['shipping_date'] = array();

					$settings['storefronts']['*']['shipping_date'][$shipping['id']] = $shipping_date;

					$is_fill = true;
				}

				/**
				 * Автоподстановка стоимостей доставок
				 */

				$is_shipping_cost_calculated = !empty($shipping['dp_rates']['cost']);
				$is_empty_shipping_cost = empty($settings['storefronts']['*']['shipping_cost'][$shipping['id']]);

				if($is_shipping_cost_calculated && $is_empty_shipping_cost) {
					$cost = $shipping['dp_rates']['cost'];

					$shipping_cost = array(
						'format' => 'fixed',
						'sync' => true,
						'value' => array(
							'fixed' => $cost
						)
					);
				} elseif(!$is_shipping_cost_calculated && $is_empty_shipping_cost) {
					$shipping_cost = array(
						'format' => 'calculate',
						'value' => array()
					);
				}

				if(!empty($shipping_cost)) {
					if(empty($settings['storefronts']['*']['shipping_cost']))
						$settings['storefronts']['*']['shipping_cost'] = array();

					$settings['storefronts']['*']['shipping_cost'][$shipping['id']] = $shipping_cost;

					$is_fill = true;
				}
			}
		}

		return $is_fill;
	}
}

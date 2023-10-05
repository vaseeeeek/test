<?php

class shopDpEnv
{
	protected $routing;
	protected $plugin_url;
	protected $plugin_path;
	protected $storefront_id;
	protected $theme_id;
	protected $registered_groups;
	protected $themes = array();

	private $data_url;
	private $service_config;
	private $integrations = array();
	private $systems = array();
	private $configs = array();
	private $models = array();
	private $storage;
	private $user;
	private $contact;
	private $dimension_features;

	/**
	 * @param string $app
	 * @return waSystem
	 */
	protected function getSystem($app = 'shop')
	{
		if(!isset($this->systems[$app])) {
			$this->systems[$app] = wa($app === 'wa' ? null : $app);
		}

		return $this->systems[$app];
	}

	/**
	 * @param string $app
	 * @return waSystemConfig
	 */
	public function getConfig($app = 'shop')
	{
		if(!isset($this->configs[$app])) {
			$this->configs[$app] = $this->getSystem($app)->getConfig();
		}

		return $this->configs[$app];
	}

	/**
	 * @param string $model
	 * @param string $app
	 * @return mixed
	 */
	protected function getModel($model, $app = 'shop')
	{
		$key = "$app/$model";

		if(!isset($this->models[$key])) {
			$class_name = sprintf('%s%sModel', $app, ucfirst($model));
			if(class_exists($class_name)) {
				$this->models[$key] = new $class_name();
			}
		}

		return $this->models[$key];
	}

	/**
	 * @return shopPluginModel
	 */
	protected function getPluginModel()
	{
		return $this->getModel('plugin');
	}

	/**
	 * @return shopFeatureModel
	 */
	protected function getFeatureModel()
	{
		return $this->getModel('feature');
	}

	/**
	 * @return shopDpStorefrontGroupsModel
	 */
	protected function getDpStorefrontGroupsModel()
	{
		return $this->getModel('dpStorefrontGroups');
	}

	/**
	 * @return waAppSettingsModel
	 */
	protected function getWaAppSettingsModel()
	{
		return $this->getModel('appSettings', 'wa');
	}

	public function getStorage()
	{
		if(!isset($this->storage)) {
			$this->storage = $this->getSystem()->getStorage();
		}

		return $this->storage;
	}

	public function getUser()
	{
		if(!isset($this->user)) {
			$this->user = $this->getSystem()->getUser();
		}

		return $this->user;
	}

	/**
	 * @return waContact
	 */
	public function getContact()
	{
		if(!isset($this->contact)) {
			if($this->getUser()->isAuth()) {
				$this->contact = $this->getUser();
			} else {
				$data = $this->getStorage()->get('shop/checkout');
				$this->contact = isset($data['contact']) ? $data['contact'] : null;
			}

			if (!$this->contact) {
				$this->contact = $this->getSystem()->getUser();
			}
		}

		return $this->contact;
	}

	public function saveContact(waContact $contact)
	{
		if($this->getUser()->isAuth()) {
			$contact->save();
		} else {
			$data = $this->getStorage()->get('shop/checkout');
			$data['contact'] = $contact;
			$this->getStorage()->set('shop/checkout', $data);
		}
	}

	/**
	 * @return waRouting
	 */
	protected function getRouting()
	{
		if(!isset($this->routing)) {
			$this->routing = $this->getSystem()->getRouting();
		}

		return $this->routing;
	}

	/**
	 * @return string
	 */
	public function getPluginUrl()
	{
		if(!isset($this->plugin_url)) {
			$this->plugin_url = $this->getSystem()->getAppStaticUrl('shop') . 'plugins/dp/';
		}

		return $this->plugin_url;
	}

	/**
	 * @return string
	 */
	public function getPluginPath()
	{
		if(!isset($this->plugin_path)) {
			$this->plugin_path = $this->getSystem()->getAppPath('plugins/dp/');
		}

		return $this->plugin_path;
	}

	/**
	 * @return array
	 */
	public function getRegisteredGroups()
	{
		if(!isset($this->registered_groups)) {
			$this->registered_groups = array();
			$path = $this->getPluginPath() . 'lib/config/data/groups.php';

			if(file_exists($path)) {
				$this->registered_groups = include($path);
			}
		}

		return $this->registered_groups;
	}

	/**
	 * @return string
	 */
	public function getDataUrl()
	{
		if(!isset($this->data_url)) {
			$this->data_url = $this->getSystem()->getDataUrl('plugins/dp/data', true, 'shop');
		}

		return $this->data_url;
	}

	/**
	 * @param string $path
	 * @return string
	 */
	public function getStaticUrl($path)
	{
		$plugin_url = $this->getPluginUrl();
		$data_url = $this->getDataUrl();

		if(preg_match('/^source:\//', $path)) {
			return str_replace('source:/', "{$plugin_url}img/", $path);
		}

		if(preg_match('/^data:\//', $path)) {
			return str_replace('data:/', "{$data_url}/", $path);
		}

		if(preg_match('/^icon:\//', $path)) {
			if (strpos($path, '?src=') !== false) {
				$url = substr($path, strpos($path, '?src=') + strlen('?src='));
				$url = str_replace('%23', '#', $url);

				$url = preg_replace_callback('/^data:image\/svg\+xml;charset=utf-8,(.*)$/s', wa_lambda('$matches', 'return "data:image/svg+xml;base64," . base64_encode($matches[1]);'), $url);

				return str_replace('%plugin_url%', $plugin_url, $url);
			} else {
				$path = str_replace('icon://', '', $path);
				if(strpos($path, ':') !== false) {
					$path = substr($path, 0, strpos($path, ':'));
				}

				return "{$plugin_url}img/icons{$path}.svg";
			}
		}

		return $path;
	}

	/**
	 * @param shopProduct|array|string|int $product
	 * @return float
	 */
	public function getProductWeight($product)
	{
		if(!$product instanceof shopProduct) {
			if(is_array($product) && isset($product['id']))
				$product = new shopProduct($product['id']);
			elseif(wa_is_int($product))
				$product = new shopProduct($product);
			else
				return null;
		}

		$feature = $this->getFeatureModel()->getByCode('weight');

		$weight = 0;

		if($feature) {
			$values_model = $this->getFeatureModel()->getValuesModel($feature['type']);
			$values = $values_model->getProductValues(array($product->getId()), $feature['id']);

			if(!empty($values[$product->getId()])) {
				$weight = $values[$product->getId()];
			} elseif(!empty($values['skus'])) {
				if(!empty($values['skus'][$product['sku_id']])) {
					$weight = $values['skus'][$product['sku_id']];
				} else {
					$weight = array_shift($values['skus']);
				}
			}
		}

		return (float) $weight;
	}

	/**
	 * @param shopProduct|array|string|int $product
	 * @param $dimension
	 * @return float
	 * @throws waException
	 */
	public function getProductDimension($product, $dimension)
	{
		if(!$product instanceof shopProduct) {
			if(is_array($product) && isset($product['id']))
				$product = new shopProduct($product['id']);
			elseif(wa_is_int($product))
				$product = new shopProduct($product);
			else
				return null;
		}

		$dimension_features = $this->getDimensionFeatures();

		if (!isset($dimension_features[$dimension]))
		{
			return 0;
		}

		$dimension_feature = $dimension_features[$dimension];

		$dimension = 0;
		$values_model = $this->getFeatureModel()->getValuesModel($dimension_feature['type']);
		$values = $values_model->getProductValues(array($product->getId()), $dimension_feature['id']);

		if (!empty($values[$product->getId()]))
		{
			$dimension = $values[$product->getId()];
		}
		elseif (!empty($values['skus']))
		{
			if (!empty($values['skus'][$product['sku_id']]))
			{
				$dimension = $values['skus'][$product['sku_id']];
			}
			else
			{
				$dimension = array_shift($values['skus']);
			}
		}

		return $dimension;
	}

	/**
	 * @param shopProduct|array|string|int $product
	 * @param bool $cart_items
	 * @return array
	 */
	public function getCalculateParams($product = null, $cart_items = true)
	{
		$params = array();

		if ($product !== null) {
			if(!$product instanceof shopProduct) {
				if(is_array($product) && isset($product['id']))
					$product = new shopProduct($product['id']);
				elseif(wa_is_int($product))
					$product = new shopProduct($product);
			}

			if($product instanceof shopProduct) {
				$params['items'] = array(
					array(
						'price' => $product['skus'][$product['sku_id']]['price'],
						'currency' => $product['currency'],
						'quantity' => 1,
						'weight' => $this->getProductWeight($product),
						'width' => $this->getProductDimension($product, 'width'),
						'length' => $this->getProductDimension($product, 'length'),
						'height' => $this->getProductDimension($product, 'height'),
						'category' => $product['category_id'],
						'type' => $product['type_id'],
						'product' => $product
					)
				);
			}
		}

		if ($cart_items) {
			$cart = new shopCart();

			$params['cart_items'] = array();

			foreach($cart->items() as $item) {
				$item_product = $item['product'];

				$cart_item = array(
					'price' => $item['price'],
					'currency' => $item['currency'],
					'quantity' => $item['quantity'],
					'weight' => $item['type'] === 'product' ? $this->getProductWeight($item_product) : 0,
					'width' => $this->getProductDimension($item_product, 'width'),
					'length' => $this->getProductDimension($item_product, 'length'),
					'height' => $this->getProductDimension($item_product, 'height'),
					'category' => -1,
					'type' => -1,
					'product' => $item_product,
				);

				$params['cart_items'][] =$cart_item;

				if ($product && $product->id == $item_product['id'])
				{
					$params['items'][0]['quantity'] += 1;
					$cart_item['quantity'] += 1;
				}
				else
				{
					$params['items'][] = $cart_item;
				}
			}
		}

		return $params;
	}

	/**
	 * @return shopDpServiceConfig
	 */
	private function getServiceConfig()
	{
		if(!isset($this->service_config)) {
			$this->service_config = new shopDpServiceConfig();
		}

		return $this->service_config;
	}

	/**
	 * @param bool $calculate
	 * @return array
	 * @throws waException
	 */
	public function getShippingPlugins($calculate = false)
	{
		$shipping_plugins = $this->getPluginModel()->listPlugins('shipping', array(
			'all' => 1
		));

		$payment_plugins = $this->getPluginModel()->listPlugins('payment', array(
			'all' => 1
		));

		$disabled_payment_plugins_json = $this->getWaAppSettingsModel()->get('shop', 'shipping_payment_disabled');
		$disabled_payment_plugins = json_decode($disabled_payment_plugins_json, true);

		foreach($shipping_plugins as $shipping_id => &$shipping_plugin) {
			if($calculate && shopDpFactory::isCalculatorExists($shipping_plugin['plugin'])) {
				$shipping_plugin['dp_rates'] = shopDpFactory::createCalculator($shipping_id, $shipping_plugin['plugin'], false)->calculateRates();
			}


			$shipping_plugin['dp_payment'] = array();
			$shipping_plugin['dp_payment_available_count'] = 0;

			foreach($payment_plugins as $payment_id => $payment_plugin) {
				$available = true;

				if(!empty($disabled_payment_plugins[$payment_id])) {
					if(in_array($shipping_id, $disabled_payment_plugins[$payment_id])) {
						$available = false;
					} else {
						$shipping_plugin['dp_payment_available_count']++;
					}
				} else {
					$shipping_plugin['dp_payment_available_count']++;
				}

				$shipping_plugin['dp_payment'][$payment_id] = $available;
			}
		}

		return $shipping_plugins;
	}

	/**
	 * @return array
	 */
	public function getPaymentPlugins()
	{
		return $this->getPluginModel()->listPlugins('payment', array(
			'all' => 1
		));
	}

	/**
	 * @param string $theme_id
	 * @return waTheme
	 */
	public function getTheme($theme_id)
	{
		if(!isset($this->themes[$theme_id])) {
			$this->themes[$theme_id] = new waTheme($theme_id, 'shop');
		}

		return $this->themes[$theme_id];
	}

	/**
	 * @param bool $with_instance
	 * @return array
	 */
	public function getThemes($with_instance = false)
	{
		$themes = array();

		$first_theme_id = null;
		foreach($this->getRouting()->getByApp('shop') as $routes) {
			foreach($routes as $route) {
				if(!array_key_exists('theme', $route)) {
					continue;
				}

				$first_theme_id = $route['theme'];

				break 2;
			}
		}

		foreach($this->getSystem()->getThemes('shop') as $theme) {
			$theme_assoc = array(
				'id' => $theme->id,
				'name' => $theme->getName(),
			);

			if($with_instance) {
				$theme_assoc['instance'] = $theme;
			}

			if ($first_theme_id == $theme->id) {
				array_unshift($themes, $theme_assoc);
			} else {
				$themes[] = $theme_assoc;
			}
		}

		return $themes;
	}

	/**
	 * @return string
	 */
	public function getCurrentTheme()
	{
		return waRequest::getTheme();
	}

	/**
	 * @return array
	 */
	public function getRoutes()
	{
		$domains = $this->getRouting()->getByApp('shop');
		$routes = array();

		foreach($domains as $domain => $domain_routes) {
			foreach($domain_routes as $route) {
				if((!method_exists($this->getRouting(), 'isAlias') || !$this->routing->isAlias($domain)) && isset($route['url'])) {
					$routes["$domain/{$route['url']}"] = $this->getSystem()->getRouteUrl('shop/frontend', array(), false, $domain, $route['url']);
				}
			}
		}

		return $routes;
	}

	/**
	 * @return array
	 */
	public function getStorefronts()
	{
		$routes = $this->getRoutes();
		return array_keys($routes);
	}

	/**
	 * @return array
	 */
	public function getStorefrontGroups()
	{
		return $this->getDpStorefrontGroupsModel()->getAll('id');
	}

	/**
	 * @return array
	 */
	public function getStorefrontGroupKeys()
	{
		$groups = $this->getDpStorefrontGroupsModel()->getAll('id', false, false);
		$keys = array();

		foreach(array_keys($groups) as $group) {
			$keys[] = "group:$group";
		}

		return $keys;
	}

	/**
	 * @return string
	 */
	public function getCurrentStorefront()
	{
		if(!isset($this->storefront_id)) {
			$route = $this->getRouting()->getRoute();

			if($route['app'] === 'shop') {
				$domain = $this->getRouting()->getDomain();
				$url = $domain . '/' . $route['url'];

				$this->storefront_id = $url;
			} else {
				return null;
			}
		}

		return $this->storefront_id;
	}

	/**
	 * @param string|null $storefront_id
	 * @return string
	 */
	public function getStorefrontGroup($storefront_id = null)
	{
		if($storefront_id === null) {
			$storefront_id = $this->getCurrentStorefront();
		}

		$storefront_groups = $this->getStorefrontGroups();

		if(!empty($storefront_groups)) {
			foreach($storefront_groups as $group_id => $group_storefronts) {
				if(array_search($storefront_id, $group_storefronts) !== false) {
					return $group_id;
					break;
				}
			}
		}

		return null;
	}

	private function getActiveSettingsMerged($settings, $storefront_id, $storefront_group, $prefix, $process_by = false, $fields = array(), $settings_config = array(), $is_null_if_wo_diff = false, $is_all_variants = false)
	{
		$values = array();

		if(!$process_by) {
			foreach($fields as $field) {
				$values[$field] = $this->getActiveSettingsValue($settings, "{$prefix}_{$field}", $storefront_id, $storefront_group, $settings_config, $is_null_if_wo_diff, $is_all_variants);
			}

			return $values;
		}

		$merged = array();

		$process_by_values = $this->getActiveSettingsValue($settings, "{$prefix}_{$process_by}", $storefront_id, $storefront_group, $settings_config, $is_null_if_wo_diff, $is_all_variants);

		foreach($fields as $field)
			$values[$field] = $this->getActiveSettingsValue($settings, "{$prefix}_{$field}", $storefront_id, $storefront_group, $settings_config, $is_null_if_wo_diff, $is_all_variants);

		foreach($process_by_values as $id => $processed_value) {
			if($processed_value) {
				$merged[$id][$process_by] = $processed_value;

				foreach($fields as $field)
					$merged[$id][$field] = ifset($values, $field, $id, null);
			}
		}

		return $merged;
	}

	private function nullWODiffSettings(&$settings, $settings_config = array(), $storefront_id = null, $storefront_group = null)
	{
		if(!empty($settings_config['storefronts']['*'])) {
			$config = $settings_config['storefronts']['*'];

			if($storefront_id === null) {
				$storefront_id = $this->getCurrentStorefront();
			}

			if($storefront_group === null) {
				$storefront_group = $this->getStorefrontGroup($storefront_id);
			}

			foreach($config as $field => $value) {
				$is_wo_diff_global = !isset($settings['*'][$field]) || $settings['*'][$field] === $value;
				if($is_wo_diff_global) {
					$settings['*'][$field] = null;
					continue;
				}

				$is_wo_diff_storefront_id = !isset($settings[$storefront_id][$field]) || $settings[$storefront_id][$field] === $value;
				if($is_wo_diff_storefront_id) {
					$settings[$storefront_id][$field] = null;
					continue;
				}

				$is_wo_diff_storefront_group = !isset($settings[$storefront_group][$field]) || $settings[$storefront_group][$field] === $value;
				if($is_wo_diff_storefront_group) {
					$settings[$storefront_group][$field] = null;
					continue;
				}
			}
		}
	}

	public function getActiveSettings($settings, $name = null, $storefront_id = null, $settings_config = array(), $is_null_if_wo_diff = false, $is_all_variants = false)
	{
		if($name === null) {
			return $settings;
		} else {
			if(is_string($name) && isset($settings['basic'][$name])) {
				return $settings['basic'][$name];
			} else {
				if(is_string($name) && substr($name, 0, strlen('design_')) === 'design_') {
					$general_value = ifset($settings, 'themes', '*', $name, null);

					return ifset($settings, 'themes', $this->getCurrentTheme(), $name, $general_value);
				}

				if($storefront_id === null) {
					$storefront_id = $this->getCurrentStorefront();
				}

				$storefront_group = $this->getStorefrontGroup($storefront_id);

				if($is_null_if_wo_diff)
					$this->nullWODiffSettings($settings, $settings_config);

				switch($name) {
					case 'shipping_methods':
						return $this->getActiveSettingsMerged($settings, $storefront_id, $storefront_group, 'shipping', 'status', array('title', 'description', 'shipping', 'service', 'settings', 'payment', 'region_availability', 'pay_on_ship', 'image', 'group', 'actuality', 'placemark_image', 'placemark_color', 'async', 'schedule', 'calculation_product'), $settings_config, $is_null_if_wo_diff, $is_all_variants);
						break;
					case 'payment_methods':
						return $this->getActiveSettingsMerged($settings, $storefront_id, $storefront_group, 'payment', false, array('title', 'image'), $settings_config, $is_null_if_wo_diff, $is_all_variants);
						break;
					default:
						if(is_array($name)) {
							return $this->getActiveSettingsMerged($settings, $storefront_id, $storefront_group, $name['prefix'], false, $name['fields'], $settings_config, $is_null_if_wo_diff, $is_all_variants);
						} else {
							return $this->getActiveSettingsValue($settings, $name, $storefront_id, $storefront_group, $settings_config, $is_null_if_wo_diff, $is_all_variants);
						}
						break;
				}
			}
		}
	}

	private function replaceSettingsValueWithinConfig($name, $value, $settings_config, $is_null_if_wo_diff = false)
	{
		if(!$is_null_if_wo_diff) {
			return $value;
		} else {
			if(!empty($settings_config['storefronts']['*'][$name])) {
				$default_value = $settings_config['storefronts']['*'][$name];

				if($value === $default_value)
					return null;
			}

			return $value;
		}
	}

	public function getActiveSettingsValue($settings, $name, $storefront_id = null, $storefront_group = null, $settings_config = array(), $is_null_if_wo_diff = false, $is_all_variants = false)
	{
		$general_storefront_value = ifset($settings, 'storefronts', '*', $name, null);

		if($is_all_variants) {
			$output = array();

			if(!empty($settings['storefronts'])) {
				foreach($settings['storefronts'] as $storefront_id => $fields) {
					if(array_key_exists($name, $fields)) {
						$output[$storefront_id] = $this->replaceSettingsValueWithinConfig($name, $fields[$name], $settings_config, $is_null_if_wo_diff);
					}
				}
			}
		} else {
			if($storefront_group !== null) {
				$storefront_group_value = ifset($settings, 'storefronts', "group:$storefront_group", $name, null);
				$general_storefront_value = ifset($storefront_group_value, $general_storefront_value);
			}

			if(isset($settings['storefronts'][$storefront_id])) {
				$value = ifset($settings, 'storefronts', $storefront_id, $name, $general_storefront_value);
			} else {
				$value = $general_storefront_value;
			}

			$output = $this->replaceSettingsValueWithinConfig($name, $value, $settings_config, $is_null_if_wo_diff);
		}

		return $output;
	}

	public function getServiceStaticParams($settings, $service, $agregator = null)
	{
		$params = array(
			'service_image' => null,
			'service_name' => null,
			'map_placemark_image' => null,
			'map_placemark_color' => null,
		);

		if(!empty($settings[$service])) {
			$config = $settings[$service];
			if($agregator)
				$config = ifempty($config, 'agregator', 'params', $agregator, null);

			$params['service_image'] = !empty($config['image']) ? $this->getStaticUrl($config['image']) : null;
			$params['service_name'] = !empty($config['name']) ? $config['name'] : null;

			$params['map_placemark_image'] = !empty($config['placemark']) ? $this->getStaticUrl($config['placemark']) : null;
			$params['map_placemark_color'] = !empty($config['color']) ? $config['color'] : null;

			if($agregator === null)
				$params['agregator'] = ifset($config, 'agregator', null);
		} else {
			$config = $this->getServiceConfig()->get($service);

			if($agregator)
				$config = ifempty($config, 'agregator', 'params', $agregator, null);

			if(!empty($config)) {
				$params['service_image'] = !empty($config['image']) ? $this->getStaticUrl($config['image']) : null;
				$params['service_name'] = !empty($config['name']) ? $config['name'] : null;
				$params['map_placemark_image'] = !empty($config['placemark']) ? $this->getStaticUrl($config['placemark']) : null;
				$params['map_placemark_color'] = !empty($config['color']) ? $config['color'] : null;

				if($agregator === null)
					$params['agregator'] = ifset($config, 'agregator', null);
			}
		}

		return $params;
	}

	public function isAvailableForRegion($location, $available, $region_availability)
	{
		if($available && !empty($region_availability['availability']) && !empty($region_availability['regions'])) {
			$style = intval($region_availability['availability']);

			if($style !== 0) {
				$available_by_region = $style === -1;

				foreach($region_availability['regions'] as $row) {
					$result = $row['country'] === $location->getCountry();

					if($row['region']) {
						$result = $result && $row['region'] === $location->getRegion();
					}

					if($row['city']) {
						$result = $result && $location->checkForCity($row['city']);
					}

					if($style === 1) {
						// Стиль проверки "Выбранные регионы"

						if($result) {
							$available_by_region = true;
							break;
						} else {
							continue;
						}
					} elseif($style === -1) {
						// Стиль проверки "НЕ выбранные регионы"

						if($result) {
							$available_by_region = false;
							break;
						} else {
							continue;
						}
					}
				}

				return $available_by_region;
			}
		} else {
			return $available;
		}
	}

	public function getPluginIntegration($plugin, $params = array())
	{
		$plugin = ucfirst($plugin);
		$integration_instance = "{$plugin}Plugin";

		$integration = $this->getIntegration($integration_instance, $params);

		if($integration) {
			return $integration;
		}

		return null;
	}

	protected function getAuth()
	{
		return $this->getConfig()->getAuth();
	}

	public function isEnabledAuth()
	{
		$auth = $this->getAuth();
		$is_enabled_auth = !empty($auth);

		return $is_enabled_auth;
	}

	public function isEnabledIpPlugin()
	{
		return $this->isEnabledShopPlugin('ip') && class_exists('shopIpPlugin');
	}

	public function isReadyIpPluginCityApi()
	{
		return $this->isEnabledIpPlugin() && method_exists('shopIpPlugin', 'getCityApi');
	}

	public function isAvailableShopSchedule()
	{
		$config = $this->getConfig();
		$version = $this->getSystem()->getVersion();

		return version_compare($version, '8.0', '>=') && method_exists($config, 'getStorefrontSchedule');
	}

	public function isEnabledRegionsPlugin()
	{
		return $this->isEnabledShopPlugin('regions');
	}

	public function isEnabledDelpayfilterPlugin()
	{
		return $this->isEnabledShopPlugin('delpayfilter');
	}

	public function isEnabledShippingtricksPlugin()
	{
		return $this->isEnabledShopPlugin('shippingtricks');
	}

	public function isEnabledFreedeliveryPlugin()
	{
		return $this->isEnabledShopPlugin('freedelivery');
	}

	public function isFrontend()
	{
		return wa()->getEnv() === 'frontend';
	}

	private function isEnabledShopPlugin($plugin)
	{
		$plugin_info = $this->getConfig()->getPluginInfo($plugin);

		return !empty($plugin_info);
	}

	private function getIntegration($instance_name, $params = array())
	{
		if (!array_key_exists($instance_name, $this->integrations)) {
			$is_enabled_function = "isEnabled{$instance_name}";
			$instance = null;

			if ($this->$is_enabled_function()) {
				$class = "shopDpIntegration{$instance_name}";

				if (class_exists($class)) {
					$instance = new $class($params);
				}
			}

			$this->integrations[$instance_name] = $instance;
		}

		return $this->integrations[$instance_name];
	}

	private function getDimensionFeatures()
	{
		if (is_array($this->dimension_features))
		{
			return $this->dimension_features;
		}

		$this->dimension_features = array();

		$app_settings = new waAppSettingsModel();
		$shipping_dimensions_raw = $app_settings->get('shop', 'shipping_dimensions');
		$shipping_dimensions = preg_split('@\D+@', $shipping_dimensions_raw);

		$dimension_features = array();

		if (count($shipping_dimensions) === 3)
		{
			list($height, $width, $length) = $shipping_dimensions;
			foreach (compact('height', 'width', 'length') as $dimension => $feature_id)
			{
				$feature = $this->getFeatureModel()->getById($feature_id);
				if ($feature)
				{
					$dimension_features[$dimension] = $feature;
				}
			}
		}
		elseif (count($shipping_dimensions) === 1 && wa_is_int($shipping_dimensions[0]))
		{
			// родительская и три дочерних
			$size_features = $this->getFeatureModel()
				->select('*')
				->where('id = :id OR parent_id = :id', array('id' => $shipping_dimensions[0]))
				->fetchAll('id');

			if (count($size_features) === 4)
			{
				$main_feature = $size_features[$shipping_dimensions[0]];

				foreach (array('height', 'width', 'length',) as $index => $dimension)
				{
					foreach ($size_features as $_feature)
					{
						if ($_feature['code'] == "{$main_feature['code']}.{$index}")
						{
							$dimension_features[$dimension] = $_feature;
						}
					}
				}
			}
		}

		if (count($dimension_features) === 3)
		{
			$this->dimension_features = $dimension_features;
		}

		return $this->dimension_features;
	}
}

<?php

class shopDpService implements ArrayAccess
{
	const LOG_FILE = 'dp.service.log';

	protected static $env;

	public $id;
	public $params;
	public $agregators;
	public $plugin_url;
	public $shipping_fields;

	protected $data = array();
	protected $calculate_params = array();
	protected $calculate_mode = array(
		'cost' => 'inline',
		'estimated_date' => 'inline',
		'mode' => 'product'
	);
	protected $payment_methods = array();
	protected $plugin;
	protected $frontend;
	protected $location;
	protected $freedelivery_plugin_integration;
	protected $caller;

	private $service_config;

	/**
	 * @param array|string|int $service
	 * @param array $params
	 * @param array $options
	 */
	public function __construct($service, $params = array(), $options = array())
	{
		extract($options);

		if(isset($env) && $env instanceof shopDpEnv) {
			self::$env = $env;
		}

		if(isset($plugin))
			$this->plugin = $plugin;

		if(isset($frontend))
			$this->frontend = $frontend;

		if(isset($location))
			$this->location = $location;

		if(isset($service_config))
			$this->service_config = $service_config;

		if(isset($calculate_mode))
			$this->calculate_mode = $calculate_mode;

		if(isset($calculate_params))
			$this->calculate_params = $calculate_params;

		if(isset($payment_methods))
			$this->payment_methods = $payment_methods;

		if(isset($caller))
			$this->caller = $caller;

		if(is_array($service)) {
			$this->id = $service['id'];
			$this->data = $service;
		} else {
			$this->id = $service;

			if(!isset($shipping_methods))
				$shipping_methods = $this->getPlugin()->getSettings('shipping_methods');

			$this->data = ifset($shipping_methods, $this->id, null);
			$this['id'] = $this->id;

			if(empty($this->data)) {
				return null;
			}
		}

		if(!isset($fields))
			$fields = $this->getPlugin()->getSettings('shipping_fields');

		$this->shipping_fields = $fields;

		$default_params = array(
			'integration' => 'existing',
			'break_on_unavailable' => false,
			'process_points' => false,
			'sort_points' => false,
			'open_point_if_one' => false
		);
		$this->params = array_merge($default_params, $params);

		$this->plugin_url = $this->getEnv()->getPluginUrl();

		if(empty($this->params['no_process'])) {
			$this->process();
		} else {
			$this->processType();
		}
	}

	private function getServiceConfig()
	{
		if(!isset($this->service_config)) {
			$this->service_config = new shopDpServiceConfig();
		}

		return $this->service_config;
	}

	public function getLocation()
	{
		if(!isset($this->location)) {
			$this->location = new shopDpLocation('service');
		}

		return $this->location;
	}

	protected function getFrontend()
	{
		if(!isset($this->frontend)) {
			$this->frontend = new shopDpFrontend();
		}

		return $this->frontend;
	}

	protected function getCalculateParams()
	{
		return $this->calculate_params;
	}

	protected function getCalculateMode($name = null)
	{
		if($name === 'mode' && in_array($this->caller, array('product', 'calculate'))) {
			$calculate_mode = $this['calculation_product'];

			if($calculate_mode) {
				return $calculate_mode;
			}
		}

		if($name !== null) {
			return ifset($this->calculate_mode, $name, null);
		} else {
			return $this->calculate_mode;
		}
	}

	protected function getCalculateOptions()
	{
		return array(
			'mode' => $this->getCalculateMode(),
			'params' => $this->getCalculateParams(),
			'free' => $this->getFreeCostParams()
		);
	}

	protected function getPlugin()
	{
		if(!isset($this->plugin)) {
			$this->plugin = shopDpPlugin::getInstance('service');
		}

		return $this->plugin;
	}

	protected static function getEnv()
	{
		if(!isset(self::$env))
			self::$env = new shopDpEnv();

		return self::$env;
	}

	private function setUnavailable($break = false)
	{
		$this['available'] = false;

		if($break)
			$this->params['break_on_unavailable'] = true;

		return true;
	}

	public function isAvailable()
	{
		return $this['available'] === true;
	}

	public function stringAvailable()
	{
		return $this->isAvailable() ? 'true' : 'false';
	}

	public function isAsync()
	{
		return $this->caller === 'product' && !empty($this['async']);
	}

	protected function getShippingInstance()
	{
		if(!isset($this->shipping_instance)) {
			$this->shipping_instance = new shopDpShipping($this->getCalculateParams(), array(
				'cache_mode' => $this->getCalculateMode('mode'),
				'cache_actuality' => $this->getPlugin()->getSettings('cache_calculate'),
				'location' => $this->getLocation(),
				'plugin' => $this->getPlugin()
			));
		}

		return $this->shipping_instance;
	}

	protected function getPaymentInstance()
	{
		if(!isset($this->payment_instance))
			$this->payment_instance = new shopDpPayment();

		return $this->payment_instance;
	}

	protected function getShipping($shipping_id)
	{
		return $this->getShippingInstance()->get($shipping_id);
	}

	private function getShippingCost()
	{
		$settings = $this->getPlugin()->getSettings('shipping_cost');

		if(!empty($settings[$this->getId()]))
			return $settings[$this->getId()];
		else
			return false;
	}

	private function getShippingEstimatedDate()
	{
		$settings = $this->getPlugin()->getSettings('shipping_date');

		if(!empty($settings[$this->getId()]))
			return $settings[$this->getId()];
		else
			return false;
	}

	protected function costShipping($shipping = null, $calculate = false)
	{
		if($shipping === null) {
			$shipping = $this['shipping_method'];

			if(!$shipping) {
				return null;
			}
		}

		$cost = $this->getShippingCost();
		$mode = $this->getCalculateMode('mode');

		if($mode === 'no') {
			return null;
		}

		if($cost !== false && !$calculate) {
			if(!empty($cost['format']) && (isset($cost['value'][$cost['format']]) || !empty($cost['sync']))) {
				if($cost['format'] === 'calculate' || !empty($cost['sync'])) {
					return $this->costShipping($shipping, true);
				}

				return $cost['value'][$cost['format']];
			} else {
				if(!empty($cost['format']) && ($cost['format'] === 'calculate' || !empty($cost['sync']))) {
					return $this->costShipping($shipping, true);
				} else {
					return null;
				}
			}
		} else {
			return $this->getShippingInstance()->costShipping($shipping);
		}
	}

	/**
	 * @return shopDpIntegrationFreedeliveryPlugin
	 */
	protected function getFreedeliveryPluginIntegration()
	{
		if(!isset($this->freedelivery_plugin_integration)) {
			$this->freedelivery_plugin_integration = $this->getEnv()->getPluginIntegration('freedelivery', array(
				'location' => $this->getLocation(),
				'calculate_params' => $this->getCalculateParams()
			));
		}

		return $this->freedelivery_plugin_integration;
	}

	protected function getFreeCostParams()
	{
		$rules = null;

		$freedelivery_plugin_integration = $this->getFreedeliveryPluginIntegration();
		if($freedelivery_plugin_integration instanceof shopDpIntegration) {
			$rules = $freedelivery_plugin_integration->getRules($this->getId());
		}

		return $rules;
	}

	public function isFreeCost()
	{
		$is_free_cost = false;

		$freedelivery_plugin_integration = $this->getFreedeliveryPluginIntegration();
		if($freedelivery_plugin_integration instanceof shopDpIntegration) {
			$is_free_cost = $freedelivery_plugin_integration->isFree($this->getId());
		}

		return $is_free_cost;
	}

	protected function estimatedDateShipping($shipping = null, $calculate = false)
	{
		if ($shipping === null)
		{
			$shipping = $this['shipping_method'];
		}

		$estimated_date = $this->getShippingEstimatedDate();
		$mode = $this->getCalculateMode('estimated_date');

		if ($mode === 'no')
		{
			return null;
		}

		if ($estimated_date)
		{
			if (!$calculate)
			{
				if (!empty($estimated_date['format']) && !empty($estimated_date['value'][$estimated_date['format']]))
				{
					if ($estimated_date['format'] === 'calculate')
					{
						return $this->estimatedDateShipping($shipping, true);
					}

					return $estimated_date;
				}
				else
				{
					if (!empty($estimated_date['format']) && $estimated_date['format'] === 'calculate')
					{
						return $this->estimatedDateShipping($shipping, true);
					}
					else
					{
						return null;
					}
				}
			}
			else
			{
				$date = $this->getShippingInstance()->estimatedDateShipping($shipping);

				return array(
					'format' => 'calculated',
					'value' => $date,
				);
			}
		}
		else
		{
			return null;
		}
	}

	protected function getPayment($shipping_id)
	{
		if($this['payment'] === null) {
			return $this->getPaymentInstance()->getAvailableForShipping($shipping_id);
		} else {
			return $this->getPaymentInstance()->get($this['payment']);
		}
	}

	protected function getPaymentMethods()
	{
		$payment_methods = $this->getPayment($this->getId());
		foreach($payment_methods as $id => &$payment_method) {
			if(empty($payment_method['status'])) {
				unset($payment_methods[$id]);
				continue;
			}

			if(!empty($this->payment_methods['title'][$id]))
				$payment_method['title'] = $this->payment_methods['title'][$id];
			else
				$payment_method['title'] = $payment_method['name'];

			if(!empty($this->payment_methods['image'][$id]))
				$payment_method['image'] = $this->getEnv()->getStaticUrl($this->payment_methods['image'][$id]);
			else
				$payment_method['image'] = $payment_method['logo'];
		}

		$sort = $this->getPlugin()->getSettings('payment_sort');
		if(!empty($sort)) {
			$ordered_payment_methods = array();

			foreach($sort as $id) {
				if(array_key_exists($id, $payment_methods)) {
					$ordered_payment_methods[$id] = $payment_methods[$id];
					unset($payment_methods[$id]);
				}
			}

			$this['payment_methods'] = $ordered_payment_methods + $payment_methods;
		} else {
			$this['payment_methods'] = $payment_methods;
		}
	}

	private function process()
	{
		shopDpLog::details(sprintf('Обработка способа доставки "%s"', $this->id), self::LOG_FILE);

		$this['available'] = true;

		$this->checkForRegionAvailability();

		shopDpLog::details(sprintf('Результат проверки на региональные ограничения "%s": %s', $this->id, $this->stringAvailable()), self::LOG_FILE);

		if($this->isAvailable()) {
			$this->processStaticData();
			shopDpLog::details(sprintf('Результат проверки на статичные данные "%s": %s', $this->id, $this->stringAvailable()), self::LOG_FILE);

			if($this->isAvailable() || !$this->params['break_on_unavailable']) {
				$availability = $this->isAvailable();
				$this['available'] = $availability;

				$this->processFields();
				$this->processData();
				shopDpLog::details(sprintf('Результат проверки на правильность расчета "%s": %s', $this->id, $this->stringAvailable()), self::LOG_FILE);

				if($this->isAvailable() || !$this->params['break_on_unavailable']) {
					$this->processIntegration();
					shopDpLog::details(sprintf('Результат проверки интеграции "%s": %s', $this->id, $this->stringAvailable()), self::LOG_FILE);

					$this->processFinal();
				}
			}
		}

		shopDpLog::details(sprintf('Окончание обработки способа доставки "%s"', $this->id), self::LOG_FILE);
	}

	private function processStaticData()
	{
		$this->prepareShipping();

		if(!$this['shipping_method']['status'])
			$this->setUnavailable(true);

		if($this['image'])
			$this['image'] = $this->getEnv()->getStaticUrl($this['image']);
		else
			$this['image'] = $this['shipping_method']['logo'];

		if($this->isAvailable() || !$this->params['break_on_unavailable']) {
			$params = $this->getStaticParams();

			foreach($params as $key => $value)
				$this[$key] = $value;
		}

		$this->processType();
	}

	private function processType()
	{
		if($this['service']) {
			$config = $this->getServiceConfig()->get($this['service']);

			if(array_key_exists('type', $config)) {
				$this['type'] = $config['type'];
			} else {
				$this['service'] = false;
				$this['type'] = 'general';
			}
		} else {
			$this['type'] = 'general';
		}
	}

	private function getStaticParams()
	{
		$config = $this->getServiceConfig()->get($this['service']);

		$params = array(
			'service_name' => ifempty($config, 'name', null)
		);

		if(!empty($config)) {
			$params['map_placemark_image'] = !empty($config['placemark']) ? $this->getEnv()->getStaticUrl($config['placemark']) : null;
			$params['map_placemark_color'] = !empty($config['color']) ? $config['color'] : null;
		}

		if(!empty($this['placemark_image'])) {
			$params['map_placemark_image'] = $this->getEnv()->getStaticUrl($this['placemark_image']);
		}

		if(!empty($this['placemark_color'])) {
			$params['map_placemark_color'] = $this['placemark_color'];
		}

		return $params;
	}

	private function getPointAgregatorParams($service)
	{
		$config = $this->getServiceConfig()->get($this['service']);

		return ifset($config, 'agregator_services_params', 'params', $service, null);
	}

	private function processIntegration()
	{
		if($this->params['integration'] && $this['service']) {
			$this->getFrontend()->assign('service', $this->getArray());

			$js = $this['js'];
			$config = $this->getServiceConfig()->get($this['service']);

			switch($config['type']) {
				case 'points':
					$points_instance = new shopDpPoints($this, $this->params['integration'], $this->getLocation());
					$points_instance->setOption('plugin', $this->getPlugin());
					$points_instance->setOption('actuality', $this['actuality']);

					$points = $points_instance->findPoints();

					$event_params = array(
						'id' => $this->getId(),
						'service' => $this['service'],
						'points' => &$points
					);
					wa('shop')->event('dp_service.points', $event_params);

					if($points === false)
						$this->setUnavailable();

					if(($this->isAvailable() || !$this->params['break_on_unavailable']) && $this->params['process_points']) {

						if(is_array($points) && !empty($points)) {
							$opened = false;
							if($this->params['open_point_if_one'] && count($points) == 1) {
								$opened = true;
							}

							foreach($points as &$point) {
								$point['id'] = $this->getId();
								$point['opened'] = $opened;
								$point['service'] = $this['service'];
								$point['filter'] = array(
									'working-sat' => shopDpPluginHelper::isWorktimeCorrect($point['worktime'][6]),
									'working-sun' => shopDpPluginHelper::isWorktimeCorrect($point['worktime'][7]),
									'pay-on-ship' => $this['pay_on_ship'],
									'cashless' => !empty($point['cashless'])
								);
								if(!empty($this->params['group'])) {
									$point['filter']['service'] = $this['service'];
								}

								$point['service_name'] = $this['service_name'];
								$point['service_image'] = $this['service_image'];
								$point['map_placemark_image'] = $this['map_placemark_image'];
								$point['map_placemark_color'] = $this['map_placemark_color'];

								$point['agregator_params'] = $this->getPointAgregatorParams(ifset($point, 'fixed_service', null));

								if(!empty($point['agregator_params']['name'])) {
									$point['service_name'] = $point['agregator_params']['name'];
								}

								$point['cost'] = $this['cost'];
								$point['cost_html'] = $this['cost_html'];
								$point['estimated_date'] = $this['estimated_date'];
								$point['estimated_date_html'] = $this['estimated_date_html'];
								$point['pay_on_ship'] = $this['pay_on_ship'];
								$point['balloon'] = $this->getFrontend()->pointBalloon(array(
									'point' => $point
								));
								$point['unavailable'] = !empty($this->params['unavailable_points']) ? true : false;
								$point['service_fields'] = $this['fields'];
								$point['payment_methods'] = $this['payment_methods'];
							}

							$js['points'] = $points;

							$this['available_points_count'] = !empty($this->params['unavailable_points']) ? 0 : count($points);

							if(!empty($this->params['sort_points'])) {
								$points = wao(new shopDpPointsSort($this->data['points'], $this->params['sort_points']))->execute();
							}

							$this['points'] = $points;
						}
					} else {
						$this['points'] = $points;
					}
					break;
				case 'courier':
					if(!empty($this['settings']['courier']['zones'])) {
						$zones = $this['settings']['courier']['zones'];
						$cost = $this->getShippingCost();

						$is_zone_dependent_cost = false;
						if(!empty($cost['format']) && $cost['format'] === 'zone' && !empty($cost['value']['zone']))
							$is_zone_dependent_cost = true;

						foreach($zones as $id => &$zone) {
							if($is_zone_dependent_cost) {
								if(isset($cost['value']['zone'][$id])) {
									$zone['cost'] = shopDpPluginHelper::parseCost($cost['value']['zone'][$id], $this->getCalculateOptions());
									$zone['cost_html'] = shopDpPluginHelper::htmlCost($zone['cost']);
								}
							}

							$zone['balloon'] = $this->getFrontend()->zoneBalloon(array(
								'zone' => $zone
							));
						}

						$this['zones'] = $zones;

						$error_placemark_status = $this->getPlugin()->getSettings('design_zones_error_placemark_status');
						$error_placemark_image = $this->getPlugin()->getSettings('design_zones_error_placemark_image');

						if($error_placemark_status && $error_placemark_image) {
							$js['error_placemark_status'] = true;
							$js['error_placemark_image'] = $this->getEnv()->getStaticUrl($error_placemark_image);
						} else {
							$js['error_placemark_status'] = false;
						}

						$js['zones'] = $zones;
					}

					break;
			}

			$this['js'] = $js;
		} else {
			$config = array(
				'type' => 'general'
			);
		}

		$this->setData('config', $config);
	}

	private function parseCost()
	{
		if(isset($this['cost_params'])) {
			$cost = shopDpPluginHelper::parseCost($this['cost_params'], $this->getCalculateOptions());

			$event_params = array(
				'id' => $this->getId(),
				'service' => $this['service'],
				'cost' => &$cost
			);
			wa('shop')->event('dp_service.cost', $event_params);

			$this['cost'] = $cost;
			$this['cost_html'] = shopDpPluginHelper::htmlCost($this['cost']);
		}
	}

	private function parseEstimatedDate()
	{
		if(isset($this['estimated_date_params'])) {
			$estimated_date = shopDpPluginHelper::parseEstimatedDate($this['estimated_date_params'], $this->getCalculateOptions(), $this['schedule']);

			$event_params = array(
				'id' => $this->getId(),
				'service' => $this['service'],
				'estimated_date' => &$estimated_date
			);
			wa('shop')->event('dp_service.estimated_date', $event_params);

			$estimated_date_params = array(
				'format' => $this->getPlugin()->getSettings('date_format'),
				'range' => $this->getPlugin()->getSettings('date_range_format')
			);

			$this['estimated_date'] = $estimated_date;
			$this['estimated_date_html'] = shopDpPluginHelper::htmlEstimatedDate($this['estimated_date'], $estimated_date_params);
		}
	}

	public function prepareShipping()
	{
		$this['shipping_method'] = $this->getShipping($this->id);
		$this['shipping'] = $this['shipping_method'];
	}

	public function calculate()
	{
		$is_async = $this->isAsync();

		$this['cost_params'] = null;
		if(!$is_async) {
			if($this->isFreeCost()) {
				$this['cost_params'] = 0;
			} else {
				$this['cost_params'] = $this->costShipping();
			}
		}

		$this['estimated_date_params'] = null;

		if(!$is_async) {
			$this['estimated_date_params'] = $this->estimatedDateShipping();
		}
	}

	public function postCalculate()
	{
		$this['free_cost_params'] = $this->getFreeCostParams();

		$this->parseCost();
		$this->parseEstimatedDate();
	}

	private function processData()
	{
		$this['is_map'] = false;

		$this->calculate();

		if($this['cost_params'] === false) {
			$this->setUnavailable();
		}

		if($this->isAvailable() || !$this->params['break_on_unavailable']) {
			$this->postCalculate();

			$this->getPaymentMethods();

			$this['js'] = array(
				'id' => $this->getId(),
				'name' => $this['name'],
				'title' => $this['title'],
				'service' => $this['service'],
				'cost' => $this['cost'],
				'cost_html' => $this['cost_html'],
				'pay_on_ship' => $this['pay_on_ship'],
				'service_image' => $this['service_image'],
				'service_name' => $this['service_name']
			);
		}
	}

	private function processFinal()
	{
		if(in_array($this['service'], shopDpPluginHelper::getPointServices()) || ($this['service'] === 'courier' && !empty($this['zones'])))
			$this['is_map'] = true;
	}

	public function processFields()
	{
		$this->data['fields'] = array();

		foreach($this->shipping_fields as $key => $field_params) {
			$field_values = ifset($field_params, 'values', null);

			$value = null;
			if(isset($field_values[$this->getId()]))
				$value = $field_values[$this->getId()];

			$this->data['fields'][intval($key) + 1] = $value ? $this->getFrontend()->serviceField($value) : null;
		}
	}

	private function checkForRegionAvailability()
	{
		if($this->isAvailable())
			$this['available'] = $this->getEnv()->isAvailableForRegion($this->getLocation(), $this->isAvailable(), $this['region_availability']);
		else
			return false;
	}

	public function getId()
	{
		return $this->id;
	}

	public function getDialogTitle()
	{
		switch($this['service']) {
			case 'courier':
				if(!empty($this['zones'])) {
					return $this->getPlugin()->getSettings('design_zones_title');
				} else {
					return $this->getTitle();
				}
				break;
			default:
				return $this->getTitle();
				break;
		}
	}

	public function getTitle()
	{
		if(!empty($this->data['title']))
			return $this->data['title'];
		else
			return $this->data['shipping_method']['name'];
	}

	public function getArray()
	{
		$data = array(
			'id' => $this->getId(),
			'status' => true,
			'params' => array(
				'id' => $this['service_id'],
				'code' => $this['service'],
				'image' => $this['service_image'],
				'name' => $this['service_name'],
				'type' => $this['type']
			),
			'type' => $this['type'],
			'available' => $this['available'],
			'title' => $this->getTitle(),
			'description' => $this['description'],
			'fields' => $this['fields'],
			'shipping_method' => $this['shipping_method'],
			'payment_methods' => $this['payment_methods'],
			'service' => $this['service'],
			'pay_on_ship' => $this['pay_on_ship'],
			'image' => $this['image'],
			'service_id' => $this['service_id'],
			'is_map' => $this['is_map'],
			'cost' => $this['cost'],
			'cost_html' => $this['cost_html'],
			'estimated_date' => $this['estimated_date'],
			'estimated_date_html' => $this['estimated_date_html'],
			'worktime' => null,
			'async' => $this->isAsync(),
			'js' => $this['js']
		);

		if($this['points'])
			$data['points'] = $this['points'];
		if($this['zones'])
			$data['zones'] = $this['zones'];
		if($this['service'] === 'store' && !empty($data['points']) && count($data['points']) === 1) {
			$points = $data['points'];
			$point = reset($points);
			$data['worktime'] = $point['worktime'];
			$data['worktime_string'] = $point['worktime_string'];
			$data['worktime_html'] = $point['worktime_html'];
		}

		return $data;
	}

	public function getData($name = null)
	{
		if($name) {
			return isset($this->data[$name]) ? $this->data[$name] : null;
		} else {
			return $this->data;
		}
	}

	public function setData($name, $value)
	{
		$this->data[$name] = $value;

		return $value;
	}

	public function __get($name)
	{
		if(isset($this->data[$name])) {
			return $this->data[$name];
		}
	}

	public function offsetGet($offset)
	{
		return $this->__get($offset);
	}

	public function __set($name, $value)
	{
		return $this->setData($name, $value);
	}

	public function offsetSet($offset, $value)
	{
		$this->__set($offset, $value);
	}

	public function offsetUnset($offset)
	{
		$this->__set($offset, null);
	}

	public function offsetExists($offset)
	{
		return isset($this->data[$offset]);
	}
}

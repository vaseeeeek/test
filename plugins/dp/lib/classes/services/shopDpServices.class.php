<?php

class shopDpServices
{
	protected static $env;

	public $shipping_methods = array();
	public $path;
	public $services;

	protected $service_options = array();

	protected $calculate_mode = array(
		'cost' => 'inline',
		'estimated_date' => 'inline',
		'mode' => 'product'
	);
	protected $calculate_params = array();
	protected $plugin;
	protected $frontend;
	protected $registered_groups;
	protected $caller;

	private $service_config;

	/**
	 * @param array $shipping_methods
	 * @param array $options
	 */
	public function __construct($shipping_methods = array(), $options = array())
	{
		$this->shipping_methods = $shipping_methods;

		$this->service_options = $options;
		extract($options);

		if(isset($env) && $env instanceof shopDpEnv) {
			self::$env = $env;
		}

		if(isset($frontend))
			$this->frontend = $frontend;

		if(isset($plugin))
			$this->plugin = $plugin;

		if(isset($calculate_params))
			$this->calculate_params = $calculate_params;

		if(isset($mode))
			$this->calculate_mode['mode'] = $mode;

		if(isset($cost))
			$this->calculate_mode['cost'] = $cost;

		if(isset($estimated_date))
			$this->calculate_mode['estimated_date'] = $estimated_date;

		if(isset($caller))
			$this->caller = $caller;
	}

	private function getServiceConfig()
	{
		if(!isset($this->service_config)) {
			$this->service_config = new shopDpServiceConfig();
		}

		return $this->service_config;
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

	protected function getCalculateMode()
	{
		return $this->calculate_mode;
	}

	protected static function getEnv()
	{
		if(!isset(self::$env))
			self::$env = new shopDpEnv();

		return self::$env;
	}

	protected function getPlugin()
	{
		if(!isset($this->plugin))
			$this->plugin = shopDpPlugin::getInstance('services');

		return $this->plugin;
	}

	public function getAll()
	{
		$config = $this->getServiceConfig()->config;

		foreach($config as $id => &$service)
			$service['id'] = $id;

		return $config;
	}

	public function get($id)
	{
		$config = $this->getAll();

		return ifset($config, $id, array());
	}

	public function getShippingMethods()
	{
		return $this->shipping_methods;
	}

	public function getSelectedShippingMethods($ids)
	{
		$shipping_methods = $this->getShippingMethods();
		$output_shipping_methods = array();

		foreach($ids as $id) {
			if($id && !empty($shipping_methods[$id])) {
				$output_shipping_methods[$id] = $shipping_methods[$id];
			}
		}

		return $output_shipping_methods;
	}

	protected function getPaymentMethods()
	{
		if(!isset($this->payment_methods)) {
			$this->payment_methods = $this->getPlugin()->getSettings('payment_methods');
		}

		return $this->payment_methods;
	}

	protected function getShippingFields()
	{
		if(!isset($this->shipping_fields)) {
			$this->shipping_fields = $this->getPlugin()->getSettings('shipping_fields');
		}

		return $this->shipping_fields;
	}

	private function workupShippingToService($id, &$shipping, $params)
	{
		$fields = $this->getShippingFields();
		$payment_methods = $this->getPaymentMethods();
		$source_shipping_methods = $this->getShippingMethods();

		if(!empty($shipping['status'])) {
			$shipping['id'] = $id;
			$service_options = array(
				'plugin' => $this->getPlugin(),
				'frontend' => $this->getFrontend(),
				'location' => $this->getFrontend()->getLocation(),
				'calculate_params' => $this->getCalculateParams(),
				'calculate_mode' => $this->getCalculateMode(),
				'payment_methods' => $payment_methods,
				'env' => $this->getEnv(),
				'shipping_methods' => $source_shipping_methods,
				'fields' => $fields,
				'service_config' => $this->getServiceConfig(),
				'caller' => $this->caller
			);

			$shipping = new shopDpService($shipping, $params, $service_options);

			if(($params['availability'] === 'available' && !$shipping->isAvailable()) || ($params['availability'] === 'not_available' && $shipping->isAvailable())) {
				return false;
			}

			if(!empty($params['array'])) {
				$shipping = $shipping->getArray();
			}
		} else {
			return false;
		}

		return true;
	}

	private function workupShippingToServices(&$shipping_methods, $params)
	{
		foreach($shipping_methods as $id => &$shipping) {
			if(!$this->workupShippingToService($id, $shipping, $params)) {
				unset($shipping_methods[$id]);
			}
		}
	}

	/**
	 * Возвращает сгруппированный список сервисов доставки
	 * @param array $params
	 * @return array $groups
	 */
	public function getServices($params = array())
	{
		$default_params = array(
			'integration' => 'minimal',
			'availability' => 'all',
			'group' => false,
			'product' => null,
			'array' => false
		);

		$show_column_headers = ifset($this->service_options, 'show_column_headers', true);

		$params = array_merge($default_params, $params);

		if ($params['group']) {
			$groups = array();
			$registered_groups = $this->getEnv()->getRegisteredGroups();
			$shipping_methods = $this->getShippingMethods();

			foreach($shipping_methods as $id => $shipping) {
				$service = $shipping['service'];
				$group_id = $shipping['group'] ? $shipping['group'] : 'other';

				if(!$service && array_key_exists($group_id, $registered_groups)) {
					$push_to = $group_id;
				} elseif($service) {
					foreach($registered_groups as $_group_id => $_group) {
						if(is_array($_group) && array_key_exists('types', $_group) && in_array($service, $_group['types'])) {
							$push_to = $_group_id;
						}
					}
				}

				if(isset($push_to)) {
					if($this->workupShippingToService($id, $shipping, $params)) {
						if(!isset($groups[$push_to])) {
							$groups[$push_to] = array(
								'params' => array(
									'id' => $push_to,
									'title' => $registered_groups[$push_to]['title'],
									'is_title_bold' => true,
									'show_column_headers' => $show_column_headers,
								),
								'services' => array()
							);
						}

						array_push($groups[$push_to]['services'], $shipping);
					}
				}
			}
		} else {
			$shipping_methods = $this->getShippingMethods();
			$this->workupShippingToServices($shipping_methods, $params);

			$groups = array(
				array(
					'params' => array(
						'id' => '',
						'title' => 'Способ доставки',
						'is_title_bold' => false,
						'show_column_headers' => $show_column_headers,
					),
					'services' => $shipping_methods
				)
			);
		}

		return $groups;
	}
}

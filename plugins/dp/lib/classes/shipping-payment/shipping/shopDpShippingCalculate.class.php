<?php

class shopDpShippingCalculate
{
	private static $plugins_assoc = [];

	protected $params;
	protected $options;
	protected $shop_currency;
	protected $frontend_currency;
	protected $location;

	protected $shop_plugin_model;

	protected $weight_dimension;
	protected $length_dimension;

	private $default_options = array(
		'cache' => true,
		'cache_actuality' => 86400,
		'cache_mode' => 'product'
	);

	public function __construct($params = array(), $options = array())
	{
		$this->options = array_merge($this->default_options, $options);
		$this->shop_plugin_model = new shopPluginModel();

		$default_params = array(
			'items' => $this->getDefaultItems()
		);

		$this->frontend_currency = shopDpPluginHelper::getCurrency('frontend');
		$this->shop_currency = shopDpPluginHelper::getCurrency('shop');

		$this->params = array_merge($default_params, $params);

		if(isset($options['location']))
			$this->location = $options['location'];

		$this->correctItemsWithCacheMode();

		$this->getAddress();
	}

	/**
	 * @return shopDpPlugin
	 */
	public function getPlugin()
	{
		if(!isset($this->options['plugin'])) {
			$this->options['plugin'] = shopDpPlugin::getInstance();
		}

		return $this->options['plugin'];
	}

	public function getLocation()
	{
		if(!isset($this->location))
			$this->location = new shopDpLocation('calculate');

		return $this->location;
	}

	public function getDefaultItems()
	{
		$weight = (float) $this->getPlugin()->getSettings('weight');
		$cost = (float) $this->getPlugin()->getSettings('cost');

		$items = array(
			array(
				'price' => $cost,
				'currency' => shopDpPluginHelper::getCurrency('shop'),
				'quantity' => 1,
				'weight' => $weight,
				'width' => 0.1,
				'height' => 0.1,
				'length' => 0.1,
				'category' => -1,
				'type' => -1
			)
		);

		return $items;
	}

	public function isCache()
	{
		return !empty($this->options['cache']);
	}

	private function getCacheIdentifyHash()
	{
		$params = $this->params;

		$this->correctParamsWithCacheMode($params);

		return md5(json_encode($params));
	}

	private function correctParamsWithCacheMode(&$params)
	{
		switch($this->options['cache_mode']) {
			case 'category':
				foreach($params['items'] as &$item)
					$item = $item['category'];
				break;
			case 'type':
				foreach($params['items'] as &$item)
					$item = $item['type'];
				break;
		}
	}

	private function correctItemsWithCacheMode()
	{
		switch($this->options['cache_mode']) {
			case 'general':
				$this->params['items'] = $this->getDefaultItems();
				break;
		}
	}

	private function setCacheMode($mode)
	{
		$this->options['cache_mode'] = $mode;

		$this->correctItemsWithCacheMode();
	}

	private function correctCacheMode($shipping_id)
	{
		if(is_array($this->options['cache_mode'])) {
			if(array_key_exists($shipping_id, $this->options['cache_mode'])) {
				$this->setCacheMode($this->options['cache_mode'][$shipping_id]);
			} else {
				$this->setCacheMode('product');
			}
		}
	}

	private function getCacheKey()
	{
		$key = 'shop_dp_calculate_' . $this->getCacheIdentifyHash();

		return $key;
	}

	private function getCacheActuality()
	{
		return $this->options['cache_actuality'];
	}

	protected function getCache()
	{
		return new waSerializeCache($this->getCacheKey(), $this->getCacheActuality());
	}

	public function get($shipping_id)
	{
		$cache = $this->getCache()->get();

		if($cache) {
			return ifset($cache, $shipping_id, null);
		} else {
			return null;
		}
	}

	public function save($shipping_id, $rates)
	{
		$cache = $this->getCache()->get();

		if($cache) {
			$cache[$shipping_id] = $rates;
		} else {
			$cache = array(
				$shipping_id => $rates
			);
		}

		return $this->getCache()->set($cache);
	}

	public function getAddress()
	{
		if(!isset($this->params['address'])) {
			$this->params['address'] = array(
				'country' => $this->getLocation()->getCountry(),
				'region' => $this->getLocation()->getRegion(),
				'city' => $this->getLocation()->getCity(),
				'zip' => $this->getLocation()->getZip()
			);
		}

		return $this->params['address'];
	}

	public function getItems()
	{
		return $this->params['items'];
	}

	public function getDimensionInstance()
	{
		if(!isset($this->dimension_instance))
			$this->dimension_instance = shopDimension::getInstance();

		return $this->dimension_instance;
	}

	public function getWeightDimension()
	{
		if(!isset($this->weight_dimension))
			$this->weight_dimension = $this->getDimensionInstance()->getDimension('weight');

		return $this->weight_dimension;
	}

	public function getLengthDimension()
	{
		if(!isset($this->length_dimension))
			$this->length_dimension = $this->getDimensionInstance()->getDimension('length');

		return $this->length_dimension;
	}

	public function getShopPluginModel()
	{
		if(!isset($this->shop_plugin_model))
			$this->shop_plugin_model = new shopPluginModel();

		return $this->shop_plugin_model;
	}

	public function getShippingPlugin($shipping_id)
	{
		return $this->getShopPluginModel()->getPlugin($shipping_id, 'shipping');
	}

	protected function parseEstimatedDate($source_date)
	{
		if(preg_match('/сегодня/iu', $source_date)) {
			return strtotime('today');
		} elseif(preg_match('/завтра/iu', $source_date)) {
			return strtotime('tomorrow');
		} elseif(preg_match('/от ([0-9]{1,2}) до ([0-9]{1,2})/iu', $source_date, $data_from_to_matches)) {
			return array("+{$data_from_to_matches[1]} day", "+{$data_from_to_matches[2]} day");
		} elseif(preg_match('/([^0-9]|^)([0-9]{1,2})-([0-9]{1,2})([^0-9]|$)/iu', $source_date, $data_from_to_matches)) {
			return array("+{$data_from_to_matches[2]} day", "+{$data_from_to_matches[3]} day");
		} elseif(preg_match('/до ([0-9]{1,2})/iu', $source_date, $data_to_matches)) {
			return "+{$data_to_matches[1]} day";
		} elseif(preg_match('/[-—–]/u', $source_date)) {
			$dates = preg_split('/[-—–]/u', $source_date);

			foreach($dates as $key => &$date) {
				$parsed = $this->parseEstimatedDate($date);

				if($parsed !== null)
					$date = $parsed;
				else
					unset($dates[$key]);
			}

			return $dates;
		} else {
			$date = trim($source_date);

			if (
				preg_match('/^([0-9]{1,2}) ([а-я]+) ([0-9]{4})$/iu', $date, $date_parts)
				|| preg_match('/^oт ([0-9]{1,2}) ([а-я]+) ([0-9]{4})$/iu', $date, $date_parts) // ВНИМАНИЕ - ТУТ "от" ОЧЕНЬ СТРАННЫЙ, "o" - латинницей (коды символов 111 1090) из-за плагина boxberry
				|| preg_match('/^от ([0-9]{1,2}) ([а-я]+) ([0-9]{4})$/iu', $date, $date_parts)
			)
			{
				$day = str_pad($date_parts[1], 2, '0', STR_PAD_LEFT);
				$month = shopDpPluginHelper::getMonthByString($date_parts[2]);
				$year = $date_parts[3];

				return strtotime("$year-$month-$day"); // getTimestamp доступен с 5.3 ¯\_(ツ)_/¯
			}

			if($d = DateTime::createFromFormat('d.m.Y', $date)) {
				return strtotime($d->format('Y-m-d'));
			}

			if($d = DateTime::createFromFormat('d/m/Y', $date)) {
				return strtotime($d->format('Y-m-d'));
			}

			if($d = DateTime::createFromFormat('d-m-Y', $date)) {
				return strtotime($d->format('Y-m-d'));
			}

			if($d = DateTime::createFromFormat('Y-m-d', $date)) {
				return strtotime($date);
			}
		}

		return null;
	}

	protected function parseDeliveryDate($custom_data)
	{
		if(isset($custom_data['deliveryPeriodMin']) && isset($custom_data['deliveryPeriodMax'])) {
			return array("+{$custom_data['deliveryPeriodMin']} day", "+{$custom_data['deliveryPeriodMax']} day");
		} elseif(isset($custom_data['deliveryPeriodMin'])) {
			return "+{$custom_data['deliveryPeriodMin']} day";
		} elseif(isset($custom_data['deliveryPeriodMax'])) {
			return "+{$custom_data['deliveryPeriodMax']} day";
		} elseif(!empty($custom_data['deliveryDateMin']) && !empty($custom_data['deliveryDateMax'])) {
			$times = array();

			if($d1 = DateTime::createFromFormat('Y-m-d', $custom_data['deliveryDateMin'])) {
				$times[] = strtotime($custom_data['deliveryDateMin']);
			}

			if($d2 = DateTime::createFromFormat('Y-m-d', $custom_data['deliveryDateMax'])) {
				$times[] = strtotime($custom_data['deliveryDateMax']);
			}

			if(!empty($times)) {
				return $times;
			}
		} elseif(!empty($custom_data['deliveryDateMin'])) {
			if($d = DateTime::createFromFormat('Y-m-d', $custom_data['deliveryDateMin'])) {
				return strtotime($custom_data['deliveryDateMin']);
			}
		} elseif(!empty($custom_data['deliveryDateMax'])) {
			if($d = DateTime::createFromFormat('Y-m-d', $custom_data['deliveryDateMax'])) {
				return strtotime($custom_data['deliveryDateMax']);
			}
		}

		return null;
	}

	public function calculate($shipping, $return)
	{
		if(!is_array($shipping)) {
			$id = $shipping;
			$shipping = $this->getShippingPlugin($id);
		} else
		{
			$id = $shipping['id'];
		}

		if (!is_array($shipping) || !$id)
		{
			return false;
		}

        $switch = false;
        if(waConfig::get('is_template') == true) {
            $switch = true;
            waConfig::set('is_template', false);
        }

		$this->correctCacheMode($id);

		$plugin_id = ifset($shipping, 'plugin', null);

		$rates = $this->getRates($id, $plugin_id);
		shopDpLog::details(sprintf('Результат расчета стоимости доставки "%s" ("%s")', $id, $plugin_id), shopDpService::LOG_FILE);
		shopDpLog::details($rates, shopDpService::LOG_FILE);

        if($switch)
            waConfig::set('is_template', true);

		if ($rates !== false && is_array($rates) && !empty($rates['calculated'])) {
			switch($return) {
				case 'cost':
					$values = array();

					foreach($rates as $rate)
					{
						if (
							!is_array($rate)
							|| !array_key_exists('rate', $rate)
							|| (is_array($rate['rate']) && count($rate['rate']) === 0)
						)
						{
							continue;
						}

						$rate_currency = ifset($rate, 'currency', $this->shop_currency);

						$rate_cost = is_array($rate['rate'])
							? max($rate['rate'])
							: $rate['rate'];

						$values[] = shop_currency($rate_cost, $rate_currency, $this->frontend_currency, false);
					}

					sort($values);
					if(!empty($values))
					{
						$values['calculated'] = true;
					}

					return $values;
				case 'estimated_date':
					$values = array();

					foreach($rates as $rate) {
						if(!is_array($rate)) {
							continue;
						}

						if (array_key_exists('est_delivery', $rate))
						{
							$parsed = $this->parseEstimatedDate($rate['est_delivery']);

							if ($parsed !== null)
							{
								$values[] = $parsed;
							}
						}
						elseif (array_key_exists('custom_data', $rate))
						{
							$rate_custom_data = $rate['custom_data'];

							if(
								array_key_exists('deliveryDateMin', $rate_custom_data) || array_key_exists('deliveryDateMax', $rate_custom_data)
								|| array_key_exists('deliveryPeriodMin', $rate_custom_data) || array_key_exists('deliveryPeriodMax', $rate_custom_data)
							)
							{
								$parsed = $this->parseDeliveryDate($rate_custom_data);

								if ($parsed !== null)
								{
									$values[] = $parsed;
								}
							}
						}
					}

					return $values;
				case 'rates':
					return $rates;
			}
		} elseif($rates !== false && is_array($rates) && array_key_exists('cost', $rates) && array_key_exists('est_delivery', $rates)) {
			switch($return) {
				case 'cost':
					return $rates['cost'];
				case 'estimated_date':
					return $rates['est_delivery'];
			}
		} elseif($rates === false) {
			return false;
		} else
			return null;
	}

	public function getRates($id, $plugin_id)
	{
		if ($this->isCache()) {
			$rates = $this->get($id);

			if($rates !== null) {
				return $rates;
			}
		}

		if (shopDpFactory::isCalculatorExists($plugin_id)) {
			$calculate_options = array(
				'address' => $this->getAddress(),
				'items' => $this->getItems(),
				'dimension' => $this->getWeightDimension(),
			);

			// не выводились сроки доставки у курьера, но... заработало, когда я поменял true на false. Почему?
			// todo понять что от этого может сломаться
			$rates = shopDpFactory::createCalculator($id, $plugin_id, true)->calculateRates($calculate_options);
		} else {
			$plugin = shopShipping::getPlugin($plugin_id, $id);
			$assembly_time = $this->getAssemblyTime($id);
			$rates = $this->calculateRates($plugin, $assembly_time);
		}

		if ($this->isCache())
		{
			$this->save($id, $rates);
		}

		if ($rates === false)
		{
			return false;
		}

		return $rates;
	}

	public function calculateRates(waShipping $plugin, $assembly_time_in_hours = 0)
	{
		$address = $this->getAddress();
		$plugin->setAddress($address);

		$is_allowed_address = $plugin->isAllowedAddress();
		if(!$is_allowed_address) {
			shopDpLog::details('По указанному адресу доставка не осуществляется', shopDpService::LOG_FILE);
			return false;
		}

		$items = $this->getItems();

		$weight_dimension = $this->getWeightDimension();
		$length_dimension = $this->getLengthDimension();
		$allowed_currency = $plugin->allowedCurrency();
		$allowed_weight_unit = $plugin->allowedWeightUnit();
		$allowed_linear_unit = 'm';
		if(method_exists($plugin, 'allowedLinearUnit')) {
			$allowed_linear_unit = $plugin->allowedLinearUnit();
		}

		foreach($items as $item) {
			if($item['currency'] != $allowed_currency)
				$item['price'] = shop_currency($item['price'], $item['currency'], $allowed_currency, false);

			if($allowed_weight_unit != $weight_dimension['base_unit']) {
				$item['weight'] = $this->getDimensionInstance()->convert($item['weight'], 'weight', $allowed_weight_unit, $weight_dimension['base_unit']);
			}

			if($allowed_linear_unit != $length_dimension['base_unit']) {
				$item['width'] = $this->getDimensionInstance()->convert($item['width'], 'length', $allowed_linear_unit, $length_dimension['base_unit']);
				$item['length'] = $this->getDimensionInstance()->convert($item['length'], 'length', $allowed_linear_unit, $length_dimension['base_unit']);
				$item['height'] = $this->getDimensionInstance()->convert($item['height'], 'length', $allowed_linear_unit, $length_dimension['base_unit']);
			}

			$plugin->addItem($item);
		}

		$domain = wa()->getRouting()->getDomain();
		$route = wa()->getRouting()->getRoute();

        // общий вес и габариты заказа
        $shipping_params = method_exists('shopShipping', 'getItemsTotal') ? shopShipping::getItemsTotal($items) : [];
        $params = method_exists('shopShipping', 'workupShippingParams') ? shopShipping::workupShippingParams($shipping_params, $plugin, []) : [];
        $params['shipping_params'] = [];
        $plugin->setParams($params);

		if ($domain && is_array($route) && class_exists('shopDepartureDateTimeFacade') && $assembly_time_in_hours > 0)
		{
			$storefront = rtrim($domain.'/'.$route['url'], '/*');

			$departure_datetime = shopDepartureDateTimeFacade::getDeparture(null, $storefront);
			$assembly_time = $assembly_time_in_hours * 3600;
			$departure_datetime->setExtraProcessingTime($assembly_time);

            $params['departure_datetime'] = $departure_datetime->__toString();

			$plugin->setParams($params);
		}
		$rates = $plugin->getRates();

		if(is_array($rates)) {
			shopDpLog::details('Полученная от плагина доставки стоимость:', shopDpService::LOG_FILE);
			shopDpLog::details($rates, shopDpService::LOG_FILE);

			foreach($rates as $key => $rate) {
				if($rate['rate'] === null || $rate['rate'] === '') {
					unset($rates[$key]);
				}
			}

			if(!count($rates)) {
				shopDpLog::details('Итоговая стоимость доставки некорректна', shopDpService::LOG_FILE);
				return false;
			}

			$rates['calculated'] = true;
		} elseif(is_string($rates)) {
			shopDpLog::details('Не удалось расчитать стоимость доставки: ' . $rates, shopDpService::LOG_FILE);
			return false;
		}

		return $rates;
	}

	protected function getShippingAssoc($shipping_id)
	{
		if (!array_key_exists($shipping_id, self::$plugins_assoc))
		{
			self::$plugins_assoc[$shipping_id] = $this->shop_plugin_model->getPlugin($shipping_id, shopPluginModel::TYPE_SHIPPING);
		}

		return self::$plugins_assoc[$shipping_id];
	}

	protected function getAssemblyTime($shipping_id)
	{
		$plugin_assoc = $this->getShippingAssoc($shipping_id);

		$assembly_time = ifset($plugin_assoc, 'options', 'assembly_time', null);

		return wa_is_int($assembly_time)
			? intval($assembly_time)
			: 0;
	}
}

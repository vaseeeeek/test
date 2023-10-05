<?php


class shopBuy1clickWaShippingRateStorage implements shopBuy1clickShippingRateStorage
{
	/**
	 * @param shopBuy1clickShippingRateCondition $condition
	 * @param $error
	 * @return shopBuy1clickShippingRate[]
	 */
	public function getByCondition(shopBuy1clickShippingRateCondition $condition, &$error)
	{
		$shipping = $condition->getShipping();
		
		if ($shipping == null)
		{
			return array();
		}
		
		$error = null;
		
		try
		{
			$wa_plugin = shopShipping::getPlugin(null, $shipping->getId());
		}
		catch (waException $e)
		{
			return array();
		}

		$options = array(
			'currency' => $wa_plugin->allowedCurrency(),
			'weight' => $wa_plugin->allowedWeightUnit(),
		);

		if (method_exists($wa_plugin, 'allowedLinearUnit'))
		{
			$options['dimensions'] = $wa_plugin->allowedLinearUnit();
		}
		
		$total = $condition->getTotal();
		
		try
		{
			$total_price = shopBuy1clickWaShopHelper::workupValue($total, 'price', $options['currency']);
		}
		catch (waException $e)
		{
			$total_price = 0;
		}
		
		$items = $condition->getItems();
		
		if (class_exists('shopShipping') && method_exists('shopShipping', 'extendItems'))
		{
			$units = array(
				'weight' => ifset($options, 'weight', true),
				'dimensions' => ifset($options, 'dimensions', true),
			);
			shopShipping::extendItems($items, $units);
		}
		
		$shipping_items = shopBuy1clickWaShopHelper::workupOrderItems($items, $options);
		$shipping_address = $condition->getShippingAddress()->toArray();

        $shipping_params = method_exists('shopShipping', 'getItemsTotal') ? shopShipping::getItemsTotal($items) : [];

        $params = method_exists('shopShipping', 'workupShippingParams') ? shopShipping::workupShippingParams($shipping_params, $wa_plugin, []) : [];
        $params['shipping_params'] = [];
        $params['total_price'] = $total_price;

		$entities_rates = $wa_plugin->getRates($shipping_items, $shipping_address, $params);
		
		if (!is_array($entities_rates))
		{
			if (is_string($entities_rates))
			{
				$error = $entities_rates;
				
				$entities_rates = array();
			}
			else
			{
				$entities_rates = array();
			}
		}
		
		$rates = array();
		
		foreach ($entities_rates as $id => $entity)
		{
			$entity['id'] = $id;
			$entity['shipping_id'] = $shipping->getID();
			
			$rates[$id] = $this->toRate($entity);
		}
		
		return $rates;
	}

	private function toRate($arr_rate)
	{
		$default = array(
			'id' => null,
			'shipping_id' => null,
			'rate' => 0,
			'currency' => '',
			'name' => '',
			'comment' => '',
			'est_delivery' => '',
		);
		$arr_rate = array_merge($default, $arr_rate);

		$rate = new shopBuy1clickShippingRate();
		$rate->setID($arr_rate['id']);

		$rate_cost = null;
		if (isset($arr_rate['rate']))
		{
			$rate_cost = is_array($arr_rate['rate'])
				? max($arr_rate['rate'])
				: (double)$arr_rate['rate'];
		}
		$rate->setRate($rate_cost);

		$rate->setCurrency($arr_rate['currency']);
		$rate->setName($arr_rate['name']);
		$rate->setComment($arr_rate['comment']);
		$rate->setEstDelivery($arr_rate['est_delivery']);

		return $rate;
	}
}
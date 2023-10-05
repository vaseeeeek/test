<?php


class shopBuy1clickShippingService
{
	private $shipping_storage;
	private $shipping_plugin_storage;
	private $shipping_rate_storage;
	private $env;
	
	public function __construct(
		shopBuy1clickShippingStorage $shipping_storage,
		shopBuy1clickShippingPluginStorage $shipping_plugin_storage,
		shopBuy1clickShippingRateStorage $shipping_rate_storage,
		shopBuy1clickEnv $env
	)
	{
		$this->shipping_storage = $shipping_storage;
		$this->shipping_plugin_storage = $shipping_plugin_storage;
		$this->shipping_rate_storage = $shipping_rate_storage;
		$this->env = $env;
	}
	
	/**
	 * @return shopBuy1clickShipping[]
	 */
	public function getAll()
	{
		$shipping = $this->shipping_storage->getAll();
		
		foreach ($shipping as $shipping_one)
		{
			$this->loadPlugin($shipping_one);
		}
		
		return $shipping;
	}
	
	/**
	 * @param shopBuy1clickShippingFilterCondition $condition
	 * @return shopBuy1clickShipping[]
	 */
	public function getByCondition(shopBuy1clickShippingFilterCondition $condition)
	{
		$shipping = array();
		foreach ($this->getAll() as $id => $shipping_method)
		{
			if ($shipping_method && $shipping_method->getPlugin())
			{
				$shipping[$id] = $shipping_method;
			}
		}
		
		if ($condition->isFilterByShippingIDs())
		{
			$shipping = $this->filterByShippingIDs($shipping, $condition->getShippingIDs());
		}
		
		if ($condition->isFilterCurrentStorefront())
		{
			$shipping = $this->filterCurrentStorefrontShipping($shipping);
		}
		
		if ($condition->isFilterByShippingAddress())
		{
			$shipping = $this->filterByShippingAddress($shipping, $condition->getShippingAddress());
		}
		
		if ($condition->isFilterDelpayfilter())
		{
			$shipping = $this->filterDelpayfilter($shipping, $condition->getDelpayfilterCart());
		}
		
		if ($condition->isFilterCheckcustomer())
		{
			$shipping = $this->filterCheckcustomer($shipping);
		}
		
		return $shipping;
	}
	
	/**
	 * @param $shipping_id
	 * @return shopBuy1clickShipping
	 */
	public function getByID($shipping_id)
	{
		$shipping = $this->shipping_storage->getByID($shipping_id);
		$this->loadPlugin($shipping);
		
		return $shipping;
	}
	
	public function loadPlugin(shopBuy1clickShipping $shipping)
	{
		$plugin = $this->shipping_plugin_storage->getByShippingID($shipping->getId());
		$shipping->setPlugin($plugin);
	}
	
	public function loadRates(shopBuy1clickShipping $shipping, shopBuy1clickShippingRateCondition $condition)
	{
		$condition->setShipping($shipping);
		$rates = $this->shipping_rate_storage->getByCondition($condition, $error);
		$shipping->setRates($rates);
		$shipping->setError($error);
	}
	
	/**
	 * @param $shipping shopBuy1clickShipping[]
	 * @return shopBuy1clickShipping[]
	 */
	private function filterCurrentStorefrontShipping($shipping)
	{
		$available_shipping_ids = waRequest::param('shipping_id');
		
		if (!is_array($available_shipping_ids))
		{
			return $shipping;
		}
		
		foreach ($shipping as $i => $shipping_one)
		{
			$is_available = in_array($shipping_one->getID(), $available_shipping_ids);
			
			if (!$is_available)
			{
				unset($shipping[$i]);
			}
		}
		
		return $shipping;
	}
	
	/**
	 * @param $shipping shopBuy1clickShipping[]
	 * @param $shipping_address shopBuy1clickContactInfoShippingAddress
	 * @return shopBuy1clickShipping[]
	 */
	private function filterByShippingAddress($shipping, shopBuy1clickContactInfoShippingAddress $shipping_address)
	{
		foreach ($shipping as $i => $shipping_one)
		{
			try
			{
				$wa_plugin = shopShipping::getPlugin(null, $shipping_one->getId());
				
				if (!$wa_plugin->isAllowedAddress($shipping_address->toArray()))
				{
					unset($shipping[$i]);
				}
			}
			catch (waException $e)
			{
				unset($shipping[$i]);
			}
		}
		
		return $shipping;
	}
	
	/**
	 * @param $shipping shopBuy1clickShipping[]
	 * @param $ids
	 * @return shopBuy1clickShipping[]
	 */
	private function filterByShippingIDs($shipping, $ids)
	{
		foreach ($shipping as $i => $shipping_one)
		{
			if (!in_array($shipping_one->getID(), $ids))
			{
				unset($shipping[$i]);
			}
		}
		
		return $shipping;
	}
	
	/**
	 * @param $shipping shopBuy1clickShipping[]
	 * @param shopBuy1clickCart $cart
	 * @return shopBuy1clickShipping[]
	 */
	private function filterDelpayfilter($shipping, shopBuy1clickCart $cart)
	{
		if (!$this->env->isEnabledDelpayfilterPlugin() || !$this->env->isAvailableDelpayFilterGetFailedMethods())
		{
			return $shipping;
		}

		$cart_memento = new shopBuy1clickCartMemento();
		$cart_memento->replaceTo($cart->getCode());
		
		$filters = shopDelpayfilterPlugin::getFailedMethods();
		
		$cart_memento->rollback();
		
		foreach (array_keys($shipping) as $i)
		{
			$shipping_one = $shipping[$i];
			if (isset($filters['delivery'][$shipping_one->getId()]))
			{
				unset($shipping[$i]);

				$error = $filters['delivery'][$shipping_one->getId()];

				if (!empty($error))
				{
					$shipping_one->setError($error);
				}
			}
		}

		return $shipping;
	}
	
	/**
	 * @param $shipping shopBuy1clickShipping[]
	 * @return shopBuy1clickShipping[]
	 */
	private function filterCheckcustomer($shipping)
	{
		if (!$this->env->isEnabledCheckcustomerPlugin() || !$this->env->isAvailableCheckcustomerFilterMethod())
		{
			return $shipping;
		}
		
		$method_list = array();
		
		foreach ($shipping as $i => $shipping_one)
		{
			$method_list[$i] = $shipping_one->toArray();
		}
		
		$method_list = shopCheckcustomerPlugin::filter('shipping', $method_list);
		
		foreach ($shipping as $i => $shipping_one)
		{
			if (!isset($method_list[$i]))
			{
				unset($shipping[$i]);
			}
		}
		
		return $shipping;
	}
}

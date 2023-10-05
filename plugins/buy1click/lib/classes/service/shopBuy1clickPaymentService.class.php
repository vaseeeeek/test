<?php


class shopBuy1clickPaymentService
{
	private $payment_storage;
	private $payment_plugin_storage;
	private $env;
	
	public function __construct(
		shopBuy1clickPaymentStorage $payment_storage, shopBuy1clickPaymentPluginStorage $payment_plugin_storage, shopBuy1clickEnv $env
	) {
		$this->payment_storage = $payment_storage;
		$this->payment_plugin_storage = $payment_plugin_storage;
		$this->env = $env;
	}
	
	/**
	 * @return shopBuy1clickPayment[]
	 */
	public function getAll()
	{
		$payments = $this->payment_storage->getAll();
		
		foreach ($payments as $payment)
		{
			$this->loadPlugin($payment);
		}
		
		return $payments;
	}
	
	/**
	 * @param shopBuy1clickPaymentFilterCondition $condition
	 * @return shopBuy1clickPayment[]
	 */
	public function getByCondition(shopBuy1clickPaymentFilterCondition $condition)
	{
		$payments = $this->getAll();
		
		foreach ($payments as $payment)
		{
			$this->loadPlugin($payment);
		}
		
		if ($condition->isFilterAllow())
		{
			$payments = $this->filterAllow($payments);
		}
		
		if ($condition->isFilterCurrentStorefront())
		{
			$payments = $this->filterCurrentStorefrontPayment($payments);
		}
		
		if ($condition->isFilterByShipping())
		{
			$payments = $this->filterByShipping($payments, $condition->getShippingID());
		}
		
		if ($condition->isFilterByPaymentIDs())
		{
			$payments = $this->filterByPaymentIds($payments, $condition->getPaymentIDs());
		}
		
		if ($condition->isFilterDelpayfilter())
		{
			$payments = $this->filterDelpayfilter($payments, $condition->getDelpayfilterCart());
		}
		
		if ($condition->isFilterCheckcustomer())
		{
			$payments = $this->filterCheckcustomer($payments);
		}
		
		return $payments;
	}
	
	public function loadPlugin(shopBuy1clickPayment $payment)
	{
		$plugin = $this->payment_plugin_storage->getByPaymentId($payment->getId());
		$payment->setPlugin($plugin);
	}
	
	/**
	 * @param $payments shopBuy1clickPayment[]
	 * @return shopBuy1clickPayment[]
	 */
	private function filterAllow($payments)
	{
		/** @var shopConfig $config */
		$config = wa(shopBuy1clickPlugin::SHOP_ID)->getConfig();
		$currencies = $config->getCurrencies();
		
		foreach ($payments as $i => $payment)
		{
			$payment_plugin = $payment->getPlugin();

			if (!$payment_plugin) {
				unset($payments[$i]);
			} elseif (!$payment_plugin->isAllowAnyCurrency()) {
				$allowed_currencies = $payment_plugin->getAllowedCurrency();

				if (!array_intersect($allowed_currencies, array_keys($currencies))) {
					unset($payments[$i]);
				}
			}
		}
		
		return $payments;
	}
	
	/**
	 * @param $payments shopBuy1clickPayment[]
	 * @return shopBuy1clickPayment[]
	 */
	private function filterCurrentStorefrontPayment($payments)
	{
		$available_payment_ids = waRequest::param('payment_id');
		
		if (!is_array($available_payment_ids))
		{
			return $payments;
		}
		
		foreach ($payments as $i => $payment)
		{
			$is_available = in_array($payment->getID(), $available_payment_ids);
			
			if (!$is_available)
			{
				unset($payments[$i]);
			}
		}
		
		return $payments;
	}
	
	/**
	 * @param $payments shopBuy1clickPayment[]
	 * @param $shipping_id
	 * @return shopBuy1clickPayment[]
	 */
	private function filterByShipping($payments, $shipping_id)
	{
		$disabled = shopHelper::getDisabledMethods('payment', $shipping_id);
		
		foreach ($payments as $i => $payment)
		{
			$is_disabled = in_array($payment->getID(), $disabled);
			
			if ($is_disabled)
			{
				unset($payments[$i]);
			}
		}
		
		return $payments;
	}
	
	/**
	 * @param $payments shopBuy1clickPayment[]
	 * @param $ids
	 * @return shopBuy1clickPayment[]
	 */
	private function filterByPaymentIds($payments, $ids)
	{
		foreach ($payments as $i => $payment)
		{
			if (!in_array($payment->getId(), $ids))
			{
				unset($payments[$i]);
			}
		}
		
		return $payments;
	}
	
	/**
	 * @param $payments shopBuy1clickPayment[]
	 * @param shopBuy1clickCart $cart
	 * @return shopBuy1clickPayment[]
	 */
	private function filterDelpayfilter($payments, shopBuy1clickCart $cart)
	{
		if (!$this->env->isEnabledDelpayfilterPlugin() || !$this->env->isAvailableDelpayFilterGetFailedMethods())
		{
			return $payments;
		}
		
		$cart_memento = new shopBuy1clickCartMemento();
		$cart_memento->replaceTo($cart->getCode());
		
		$filters = shopDelpayfilterPlugin::getFailedMethods();
		
		$cart_memento->rollback();
		
		foreach ($payments as $i => $payment)
		{
			if (isset($filters['payment'][$payment->getId()]))
			{
				$error = $filters['payment'][$payment->getId()];
				
				if (empty($error))
				{
					unset($payments[$i]);
				}
				else
				{
					$payment->setError($error);
				}
			}
		}
		
		return $payments;
	}
	
	/**
	 * @param $payments shopBuy1clickPayment[]
	 * @return shopBuy1clickPayment[]
	 */
	private function filterCheckcustomer($payments)
	{
		if (!$this->env->isEnabledCheckcustomerPlugin() || !$this->env->isAvailableCheckcustomerFilterMethod())
		{
			return $payments;
		}
		
		$method_list = array();
		
		foreach ($payments as $i => $payment)
		{
			$method_list[$i] = $payment->toArray();
		}
		
		$method_list = shopCheckcustomerPlugin::filter('payment', $method_list);
		
		foreach ($payments as $i => $payment)
		{
			if (!isset($method_list[$i]))
			{
				unset($payments[$i]);
			}
		}
		
		return $payments;
	}
}

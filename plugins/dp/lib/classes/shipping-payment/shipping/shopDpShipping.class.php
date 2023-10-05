<?php

class shopDpShipping extends shopDpShippingPaymentPlugin
{
	protected function getShippingCalculateInstance()
	{
		if(!isset($this->shipping_calculate_instance))
			$this->shipping_calculate_instance = new shopDpShippingCalculate($this->params, $this->options);

		return $this->shipping_calculate_instance;
	}

	public function get($shipping_id)
	{
		return $this->getShopPluginModel()->getPlugin($shipping_id, 'shipping');
	}

	public function getAll()
	{
		return $this->getShopPluginModel()->getByField('type', 'shipping', true);
	}

	public function costShipping($shipping)
	{
		return $this->getShippingCalculateInstance()->calculate($shipping, 'cost');
	}

	public function estimatedDateShipping($shipping)
	{
		return $this->getShippingCalculateInstance()->calculate($shipping, 'estimated_date');
	}

	public function getPayment($shipping_id)
	{

	}
}
<?php

class shopBuy1clickWaShopCheckoutConfig
{
	private $shop_checkout_config;

	public function __construct($shop_checkout_config)
	{
		$this->shop_checkout_config = $shop_checkout_config;
	}

	public function getOrderWithoutAuth()
	{
		return $this->shop_checkout_config
			? ifset($this->shop_checkout_config, 'confirmation', 'order_without_auth', null)
			: null;
	}

	public function isAvailable()
	{
		return !!$this->shop_checkout_config;
	}
}

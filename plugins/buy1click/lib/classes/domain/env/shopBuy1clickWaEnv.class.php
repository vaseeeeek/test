<?php


class shopBuy1clickWaEnv implements shopBuy1clickEnv
{
	private $shop_system;
	private $routing;

	private $checkout_config = null;
	
	public function __construct(waSystem $shop_system)
	{
		$this->shop_system = $shop_system;
		$this->routing = $this->shop_system->getRouting();
	}
	
	public function getCurrentStorefront()
	{
		$route = $this->routing->getRoute();
		
		if ($route['app'] === shopBuy1clickPlugin::SHOP_ID)
		{
			$domain = $this->routing->getDomain();
			$url = $domain . '/' . $route['url'];
			
			return $url;
		}
		
		return null;
	}
	
	public function isEnabledFlexdiscountPlugin()
	{
		return $this->isShopPluginInstalled('flexdiscount');
	}
	
	public function isAvailableFlexdiscountSetShopProducts()
	{
		return class_exists('shopFlexdiscountData')
			&& method_exists('shopFlexdiscountData', 'setShopProducts');
	}
	
	public function isEnabledDelpayfilterPlugin()
	{
		return $this->isShopPluginInstalled('delpayfilter');
	}
	
	public function isAvailableDelpayFilterGetFailedMethods()
	{
		return class_exists('shopDelpayfilterPlugin')
			&& method_exists('shopDelpayfilterPlugin', 'getFailedMethods');
	}
	
	public function isEnabledCheckcustomerPlugin()
	{
		return $this->isShopPluginInstalled('checkcustomer');
	}
	
	public function isAvailableCheckcustomerFilterMethod()
	{
		return class_exists('shopCheckcustomerPlugin')
			&& method_exists('shopCheckcustomerPlugin', 'filter');
	}
	
	public function isEnabledIpPlugin()
	{
		return $this->isShopPluginInstalled('ip');
	}
	
	public function isEnabledFreedeliveryPlugin()
	{
		return $this->isShopPluginInstalled('freedelivery');
	}

	public function isIncreasePluginEnabled()
	{
		return $this->isShopPluginInstalled('increase');
	}
	
	public function isAvailableFreedeliveryIsFreeShippingMethod()
	{
		return class_exists('shopFreedeliveryPlugin')
			&& method_exists('shopFreedeliveryPlugin', 'isFreeShipping');
	}

	public function getCheckoutConfig()
	{
		if ($this->checkout_config === null)
		{
			$shop_checkout_config = null;

			$route = wa()->getRouting()->getRoute();
			$checkout_storefront_id = ifset($route, 'checkout_storefront_id', null);
			if (class_exists('shopCheckoutConfig') && $checkout_storefront_id) {
				try {
					$shop_checkout_config = new shopCheckoutConfig($checkout_storefront_id);
				} catch (waException $e) {
				}
			}

			$this->checkout_config = new shopBuy1clickWaShopCheckoutConfig($shop_checkout_config);
		}

		return $this->checkout_config;
	}

	public function getConfirmationChannel($options = [])
	{
		$shop_version = $this->shop_system->getVersion('shop');

		if (version_compare($shop_version, '8.4', '<') || !class_exists('shopConfirmationChannel'))
		{
			return null;
		}

		if (!$this->getCheckoutConfig()->isAvailable())
		{
			return null;
		}

		try
		{
			return new shopConfirmationChannel($options);
		}
		catch (Exception $e)
		{
			return null;
		}
	}

	public function transformPhone($phone)
	{
		$shop_version = $this->shop_system->getVersion('shop');

		if (version_compare($shop_version, '8.4', '>=') && class_exists('shopConfirmationChannel'))
		{
			$result = waDomainAuthConfig::factory()->transformPhone($phone);

			return $result['phone'];
		}
		else
		{
			return $phone;
		}
	}

	private function isShopPluginInstalled($plugin)
	{
		$plugin_info = $this->shop_system->getConfig()->getPluginInfo($plugin);
		
		return !empty($plugin_info);
	}
}

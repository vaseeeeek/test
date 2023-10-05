<?php


interface shopBuy1clickEnv
{
	public function getCurrentStorefront();

	public function isEnabledFlexdiscountPlugin();

	public function isAvailableFlexdiscountSetShopProducts();

	public function isEnabledDelpayfilterPlugin();

	public function isAvailableDelpayFilterGetFailedMethods();

	public function isEnabledCheckcustomerPlugin();

	public function isAvailableCheckcustomerFilterMethod();

	public function isEnabledIpPlugin();

	public function isEnabledFreedeliveryPlugin();

	public function isIncreasePluginEnabled();

	public function isAvailableFreedeliveryIsFreeShippingMethod();

	/**
	 * @return shopBuy1clickWaShopCheckoutConfig
	 */
	public function getCheckoutConfig();

	/**
	 * @param array $options
	 * @return shopConfirmationChannel|null
	 */
	public function getConfirmationChannel($options = []);

	public function transformPhone($source);
}

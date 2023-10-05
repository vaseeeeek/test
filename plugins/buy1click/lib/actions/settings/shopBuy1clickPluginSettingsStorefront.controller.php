<?php


class shopBuy1clickPluginSettingsStorefrontController extends waJsonController
{
	private $settings_storage;
	
	public function __construct()
	{
		$this->settings_storage = shopBuy1clickPlugin::getContext()->getSettingsStorage();
	}
	
	public function execute()
	{
		$storefront = waRequest::get('storefront');
		
		$storefront_settings = $this->settings_storage->getStorefrontSettings($storefront);
		$product_form_settings = $this->settings_storage->getFormSettings($storefront, 'product');
		$cart_form_settings = $this->settings_storage->getFormSettings($storefront, 'cart');

		$this->response = array(
			'storefront' => $storefront_settings->toArray(),
			'product_form' => $product_form_settings->toArray(),
			'cart_form' => $cart_form_settings->toArray(),
		);
	}
}
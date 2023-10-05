<?php


class shopBuy1clickPluginSettingsAction extends waViewAction
{
	/** @var shopBuy1clickPlugin plugin */
	private $plugin;
	private $wa_customer_form;
	private $routing;
	private $settings_storage;
	private $shipping_service;
	private $payment_service;
	private $env;
	
	public function __construct($params = null)
	{
		parent::__construct($params);
		
		$this->plugin = wa(shopBuy1clickPlugin::SHOP_ID)->getPlugin('buy1click');
		$this->wa_customer_form = shopBuy1clickPlugin::getContext()->getWaCustomerForm();
		$this->routing = shopBuy1clickPlugin::getContext()->getRouting();
		$this->settings_storage = shopBuy1clickPlugin::getContext()->getSettingsStorage();
		$this->shipping_service = shopBuy1clickPlugin::getContext()->getShippingService();
		$this->payment_service = shopBuy1clickPlugin::getContext()->getPaymentService();
		$this->env = shopBuy1clickPlugin::getContext()->getEnv();
	}
	
	/**
	 * @throws waException
	 */
	public function execute()
	{
		$customer_fields = $this->wa_customer_form->getFields();
		$basic_settings = $this->settings_storage->getBasicSettings();
		$storefront_settings = $this->settings_storage->getStorefrontSettings('*');
		$product_form_settings = $this->settings_storage->getFormSettings('*', 'product');
		$cart_form_settings = $this->settings_storage->getFormSettings('*', 'cart');
		
		$fill_storefronts = $this->settings_storage->getFillStorefronts();

		$this->view->assign('to_js', array(
			'storefronts' => $this->getStorefronts(),
			'fill_storefronts' => $fill_storefronts,
			'env' => array(
				'is_enabled_ip_plugin' => $this->env->isEnabledIpPlugin(),
			),
			'shipping' => $this->getShipping(),
			'payments' => $this->getPayments(),
			'settings' => array(
				'basic' => $basic_settings->toArray(),
				'storefronts' => array(
					'*' => array(
						'storefront' => $storefront_settings->toArray(),
						'product_form' => $product_form_settings->toArray(),
						'cart_form' => $cart_form_settings->toArray(),
					)
				),
			),
			'customer_fields' => $customer_fields,
			'resource_base_url' => wa()->getAppStaticUrl(shopBuy1clickPlugin::SHOP_ID) . 'plugins/buy1click',
		));
		$this->view->assign('version', $this->plugin->getVersion());
	}

	private function getShipping()
	{
		$shipping = $this->shipping_service->getAll();
		$entities = array();

		foreach ($shipping as $shipping_one)
		{
			$entities[] = array(
				'id' => $shipping_one->getID(),
				'name' => $shipping_one->getName(),
			);
		}

		return $entities;
	}

	private function getPayments()
	{
		$payments = $this->payment_service->getAll();
		$entities = array();

		foreach ($payments as $payment)
		{
			$entities[] = array(
				'id' => $payment->getID(),
				'name' => $payment->getName(),
			);
		}

		return $entities;
	}

	private function getStorefronts()
	{
		return $this->routing->getAllStorefronts();
	}
}

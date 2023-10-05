<?php


class shopBuy1clickPluginContext
{
	private $env;
	private $wa_customer_form;
	private $routing;
	private $settings_storage;
	private $settings_service;
	private $contact_info_service;
	private $shipping_service;
	private $payment_service;
	private $form_service;
	private $temp_cart_storage;
	private $temp_cart_service;
	private $contact_info_storage;
	private $session_storage;
	private $session_service;
	private $shipping_storage;
	private $shipping_plugin_storage;
	private $shipping_rate_storage;
	private $payment_storage;
	private $payment_plugin_storage;
	private $order_service;
	private $confirmation_channel_service;
	private $freedelivery_plugin;
	
	public function getEnv()
	{
		if (!isset($this->env))
		{
			$this->env = new shopBuy1clickWaEnv(wa(shopBuy1clickPlugin::SHOP_ID));
		}
		
		return $this->env;
	}
	
	public function getWaCustomerForm()
	{
		if (!isset($this->wa_customer_form))
		{
			return new shopBuy1clickWaCustomerForm(
				shopHelper::getCustomerForm(null, true, true)
			);
		}
		
		return $this->wa_customer_form;
	}
	
	/**
	 * @return shopBuy1clickRouting
	 */
	public function getRouting()
	{
		if (!isset($this->routing))
		{
			return new shopBuy1clickWaRouting(wa()->getRouting());
		}
		
		return $this->routing;
	}
	
	public function getSettingsStorage()
	{
		if (!isset($this->settings_storage))
		{
			$this->settings_storage = new shopBuy1clickWaSettingsStorage(
				new shopBuy1clickSettingsModel(),
				new shopBuy1clickStorefrontSettingsModel(),
				$this->getWaCustomerForm(),
				$this->getEnv()
			);
		}
		
		return $this->settings_storage;
	}
	
	public function getSettingsService()
	{
		if (!isset($this->settings_service))
		{
			$this->settings_service = new shopBuy1clickSettingsService(
				$this->getSettingsStorage(), $this->getEnv()
			);
		}
		
		return $this->settings_service;
	}
	
	public function getContactInfoService()
	{
		if (!isset($this->contact_info_service))
		{
			$this->contact_info_service = new shopBuy1clickContactInfoService($this->getContactInfoStorage());
		}
		
		return $this->contact_info_service;
	}
	
	public function getShippingService()
	{
		if (!isset($this->shipping_service))
		{
			$this->shipping_service = new shopBuy1clickShippingService(
				$this->getShippingStorage(),
				$this->getShippingPluginStorage(),
				$this->getShippingRateStorage(),
				$this->getEnv()
			);
		}
		
		return $this->shipping_service;
	}
	
	public function getPaymentService()
	{
		if (!isset($this->payment_storage))
		{
			$this->payment_service = new shopBuy1clickPaymentService(
				$this->getPaymentStorage(), $this->getPaymentPluginStorage(), $this->getEnv()
			);
		}
		
		return $this->payment_service;
	}
	
	public function getFormService()
	{
		if (!isset($this->form_service))
		{
			$this->form_service = new shopBuy1clickFormService(
				$this->getContactInfoService(),
				$this->getSessionService(),
				$this->getTempCartService(),
				$this->getShippingService(),
				$this->getPaymentService(),
				$this->getOrderService(),
				$this->getSettingsService(),
				$this->getFreedeliveryPlugin(),
				$this->getEnv(),
				$this->getConfirmationChannelService()
			);
		}
		
		return $this->form_service;
	}
	
	public function getTempCartStorage()
	{
		if (!isset($this->temp_cart_storage))
		{
			$this->temp_cart_storage = new shopBuy1clickWaTempCartStorage(new shopBuy1clickTempCartModel());
		}
		
		return $this->temp_cart_storage;
	}
	
	public function getTempCartService()
	{
		if (!isset($this->temp_cart_service))
		{
			$this->temp_cart_service = new shopBuy1clickTempCartService($this->getTempCartStorage());
		}
		
		return $this->temp_cart_service;
	}
	
	public function getContactInfoStorage()
	{
		if (!isset($this->contact_info_storage))
		{
			$this->contact_info_storage = new shopBuy1clickWaContactInfoStorage($this->getWaCustomerForm());
		}
		
		return $this->contact_info_storage;
	}
	
	public function getSessionStorage()
	{
		if (!isset($this->session_storage))
		{
			$this->session_storage = new shopBuy1clickWaSessionStorage(wa()->getStorage());
		}
		
		return $this->session_storage;
	}
	
	public function getSessionService()
	{
		if (!isset($this->session_service))
		{
			$this->session_service = new shopBuy1clickSessionService($this->getSessionStorage());
		}
		
		return $this->session_service;
	}
	
	public function getShippingStorage()
	{
		if (!isset($this->shipping_storage))
		{
			$this->shipping_storage = new shopBuy1clickWaShippingStorage(new shopPluginModel());
		}
		
		return $this->shipping_storage;
	}
	
	public function getShippingPluginStorage()
	{
		if (!isset($this->shipping_plugin_storage))
		{
			$this->shipping_plugin_storage = new shopBuy1clickWaShippingPluginStorage();
		}
		
		return $this->shipping_plugin_storage;
	}
	
	public function getShippingRateStorage()
	{
		if (!isset($this->shipping_rate_storage))
		{
			$this->shipping_rate_storage = new shopBuy1clickWaShippingRateStorage();
		}
		
		return $this->shipping_rate_storage;
	}
	
	public function getPaymentStorage()
	{
		if (!isset($this->payment_storage))
		{
			$this->payment_storage = new shopBuy1clickWaPaymentStorage(new shopPluginModel());
		}
		
		return $this->payment_storage;
	}
	
	public function getPaymentPluginStorage()
	{
		if (!isset($this->payment_plugin_storage))
		{
			$this->payment_plugin_storage = new shopBuy1clickWaPaymentPluginStorage();
		}
		
		return $this->payment_plugin_storage;
	}
	
	public function getOrderService()
	{
		if (!isset($this->order_service))
		{
			$this->order_service = new shopBuy1clickOrderService($this->getEnv());
		}
		
		return $this->order_service;
	}

	public function getConfirmationChannelService()
	{
		if (!isset($this->confirmation_channel_service))
		{
			$this->confirmation_channel_service = new shopBuy1clickConfirmationChannelService($this->env);
		}

		return $this->confirmation_channel_service;
	}

	public function getFreedeliveryPlugin()
	{
		if (!isset($this->freedelivery_plugin))
		{
			if ($this->getEnv()->isEnabledFreedeliveryPlugin())
			{
				try
				{
					/** @var shopFreedeliveryPlugin freedelivery_plugin */
					$this->freedelivery_plugin = wa(shopBuy1clickPlugin::SHOP_ID)->getPlugin('freedelivery');
				}
				catch (waException $ignored)
				{

				}
			}
		}

		return $this->freedelivery_plugin;
	}
}

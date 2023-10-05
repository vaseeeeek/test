<?php


class shopBuy1clickSettingsService
{
	private $settings_storage;
	private $env;
	
	public function __construct(shopBuy1clickSettingsStorage $settings_storage, shopBuy1clickEnv $env)
	{
		$this->settings_storage = $settings_storage;
		$this->env = $env;
	}
	
	/**
	 * @param $storefront
	 * @param $type
	 * @return shopBuy1clickSettings
	 */
	public function getSettings($storefront, $type)
	{
		$basic_settings = $this->getBasicSettings();
		$storefront_settings = $this->getStorefrontSettings($storefront);
		$form_settings = $this->getFormSettings($storefront, $type);
		
		return new shopBuy1clickSettings($basic_settings, $storefront_settings, $form_settings);
	}
	
	public function getBasicSettings()
	{
		return $this->settings_storage->getBasicSettings();
	}
	
	public function storeBasicSettings(shopBuy1clickBasicSettings $basic_settings)
	{
		$this->settings_storage->storeBasicSettings($basic_settings);
	}
	
	/**
	 * @param $storefront
	 * @return shopBuy1clickStorefrontSettings
	 */
	public function getStorefrontSettings($storefront)
	{
		$storefront_settings = $this->settings_storage->getStorefrontSettings($storefront);
		
		if ($storefront !== '*')
		{
			$general_storefront_settings = $this->settings_storage->getStorefrontSettings('*');
			$storefront_settings->getData()->merge($general_storefront_settings->getData());
		}
		
		return $storefront_settings;
	}
	
	public function storeStorefrontSettings($storefront, shopBuy1clickStorefrontSettings $storefront_settings)
	{
		if ($storefront !== '*')
		{
			$general_storefront_settings = $this->settings_storage->getStorefrontSettings('*');
			$storefront_settings->getData()->diff($general_storefront_settings->getData());
		}
		
		$this->settings_storage->storeStorefrontSettings($storefront, $storefront_settings);
	}
	
	/**
	 * @param $storefront
	 * @param $type
	 * @return shopBuy1clickFormSettings
	 */
	public function getFormSettings($storefront, $type)
	{
		$storefront_settings = $this->getStorefrontSettings($storefront);
		$form_settings = $this->settings_storage->getFormSettings($storefront, $type);
		
		if ($type !== 'product')
		{
			if ($storefront_settings->isEqualFormSettings())
			{
				return $this->getFormSettings($storefront, 'product');
			}
			
			$product_form_settings = $this->settings_storage->getFormSettings($storefront, 'product');
			$form_settings->getData()->merge($product_form_settings->getData());
		}
		
		if ($storefront !== '*')
		{
			$general_form_settings = $this->getFormSettings('*', $type);
			$form_settings->getData()->merge($general_form_settings->getData());
		}
		
		return $form_settings;
	}
	
	public function storeFormSettings($storefront, $type, shopBuy1clickFormSettings $form_settings)
	{
		if ($type !== 'product')
		{
			$product_form_settings = $this->getFormSettings($storefront, 'product');
			$form_settings->getData()->diff($product_form_settings->getData());
		}
		
		if ($storefront !== '*')
		{
			$general_form_settings = $this->getFormSettings('*', $type);
			$form_settings->getData()->diff($general_form_settings->getData());
		}
		
		$this->settings_storage->storeFormSettings($storefront, $type, $form_settings);
	}
}
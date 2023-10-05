<?php


interface shopBuy1clickSettingsStorage
{
	public function getFillStorefronts();
	
	/**
	 * @return shopBuy1clickBasicSettings
	 */
	public function getBasicSettings();
	
	public function storeBasicSettings(shopBuy1clickBasicSettings $basic_settings);
	
	/**
	 * @param $storefront
	 * @return shopBuy1clickStorefrontSettings
	 */
	public function getStorefrontSettings($storefront);
	
	public function storeStorefrontSettings($storefront, shopBuy1clickStorefrontSettings $storefront_settings);
	
	/**
	 * @param $storefront
	 * @param $type
	 * @return shopBuy1clickFormSettings
	 */
	public function getFormSettings($storefront, $type);
	
	public function storeFormSettings($storefront, $type, shopBuy1clickFormSettings $form_settings);
}
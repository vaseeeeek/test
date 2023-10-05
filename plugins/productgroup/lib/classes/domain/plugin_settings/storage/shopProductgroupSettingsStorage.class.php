<?php

interface shopProductgroupSettingsStorage
{
	/**
	 * @param string $storefront
	 * @return shopProductgroupSettings
	 */
	public function getSettings($storefront);

	public function haveSettingsForStorefront($storefront);

	public function saveSettings($storefront, shopProductgroupSettings $settings);

	public function deleteSettings($storefront);

	public function getStorefrontsWithPersonalSettings();
}
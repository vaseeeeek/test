<?php

class shopProductgroupPluginConfigStorage
{
	private $settings_storage;

	public function __construct()
	{
		$context = shopProductgroupPluginContext::getInstance();

		$this->settings_storage = $context->getStorefrontSettingsStorage();
	}

	public function getConfig($storefront)
	{
		$settings_storefront = $this->settings_storage->haveSettingsForStorefront($storefront)
			? $storefront
			: shopProductgroupGeneralStorefront::NAME;

		$settings = $this->settings_storage->getSettings($settings_storefront);

		return new shopProductgroupPluginConfig(
			$settings->is_enabled,
			$settings->output_wa_hook
		);
	}
}
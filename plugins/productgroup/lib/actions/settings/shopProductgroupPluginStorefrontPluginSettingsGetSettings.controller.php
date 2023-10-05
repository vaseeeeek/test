<?php

class shopProductgroupPluginStorefrontPluginSettingsGetSettingsController extends waJsonController
{
	public function execute()
	{
		$this->response['success'] = false;

		$storefront = waRequest::get('storefront');


		$context = shopProductgroupPluginContext::getInstance();
		$storefront_settings_storage = $context->getStorefrontSettingsStorage();
		$settings_mapper = new shopProductgroupSettingsMapper();

		if ($storefront_settings_storage->haveSettingsForStorefront($storefront))
		{
			$settings = $storefront_settings_storage->getSettings($storefront);

			$this->response['state'] = [
				'have_settings' => true,
				'settings' => $settings_mapper->toAssoc($settings),
			];
		}
		else
		{
			$this->response['state'] = [
				'have_settings' => false,
			];
		}

		$this->response['success'] = true;
	}
}
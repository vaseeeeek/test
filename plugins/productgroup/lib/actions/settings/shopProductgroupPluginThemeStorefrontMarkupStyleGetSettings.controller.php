<?php

class shopProductgroupPluginThemeStorefrontMarkupStyleGetSettingsController extends waJsonController
{
	public function execute()
	{
		$this->response['success'] = false;

		$theme_id = waRequest::get('theme_id');
		$storefront = waRequest::get('storefront');

		$context = shopProductgroupPluginContext::getInstance();
		$storefront_settings_storage = $context->getMarkupStyleSettingsStorage();
		$settings_mapper = $context->getMarkupStyleSettingsAssocMapper();

		$have_settings = $storefront_settings_storage->haveThemeStorefrontSettings($theme_id, $storefront);

		$settings = $storefront_settings_storage->getThemeStorefrontSettings($theme_id, $storefront);

		$this->response['state'] = [
			'have_settings' => $have_settings,
			'settings' => $settings_mapper->toAssoc($settings),
		];

		$this->response['success'] = true;
	}
}
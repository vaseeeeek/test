<?php


class shopBuy1clickPluginSettingsSaveController extends waJsonController
{
	private $settings_service;
	private $settings_storage;
	private $env;
	
	public function __construct()
	{
		$this->settings_service = shopBuy1clickPlugin::getContext()->getSettingsService();
		$this->settings_storage = shopBuy1clickPlugin::getContext()->getSettingsStorage();
		$this->env = shopBuy1clickPlugin::getContext()->getEnv();
	}
	
	public function execute()
	{
		$settings_json = waRequest::post('settings');
		$settings = json_decode($settings_json, true);
		if (!$settings)
		{
			return;
		}

		$this->saveSettings($settings);

		$filledStorefronts = $this->settings_storage->getFillStorefronts();
		$this->generateFiles($filledStorefronts);

        $this->response['fill_storefronts'] = $filledStorefronts;
	}

	private function saveSettings($settings) {
	    $this->saveBasicSettings($settings);
        unset($settings['storefronts']['*']);
        $this->saveSettingsForStorefronts($settings);
    }

    private function saveBasicSettings($settings) {
        $basic_settings = new shopBuy1clickBasicSettings(new shopBuy1clickSettingsData(
            $settings['basic'], false
        ));
        $this->settings_service->storeBasicSettings($basic_settings);

        $this->saveSettingsForOneStorefront($settings['storefronts']['*'], '*', true);

    }

    private function saveSettingsForStorefronts ($settings) {
        foreach ($settings['storefronts'] as $storefront => $_settings)
        {
            $this->saveSettingsForOneStorefront($settings['storefronts'][$storefront], $storefront);
        }
    }

    private function saveSettingsForOneStorefront($storefrontSettings, $storefront, $is_general = false) {
        $storefront_settings = new shopBuy1clickStorefrontSettings(new shopBuy1clickSettingsData(
            $storefrontSettings['storefront'], !$is_general
        ));
        $this->settings_service->storeStorefrontSettings($storefront, $storefront_settings);

        $product_form_settings = new shopBuy1clickFormSettings(new shopBuy1clickSettingsData(
            $storefrontSettings['product_form'], !$is_general
        ), $this->env);
        $this->settings_service->storeFormSettings($storefront, 'product', $product_form_settings);

        $cart_form_settings = new shopBuy1clickFormSettings(new shopBuy1clickSettingsData(
            $storefrontSettings['cart_form'], true
        ), $this->env);
        $this->settings_service->storeFormSettings($storefront, 'cart', $cart_form_settings);
    }

    private function generateFiles ($filledStorefronts) {
        $filledStorefronts[] = '*';
        foreach ($filledStorefronts as $storefront) {
            $file = new shopBuy1clickGenerateCSSFile($storefront);
            $file->compileAndSave($this->settings_service);
        }
    }
}
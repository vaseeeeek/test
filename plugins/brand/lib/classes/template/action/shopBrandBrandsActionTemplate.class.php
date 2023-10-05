<?php

class shopBrandBrandsActionTemplate extends shopBrandActionTemplate
{
	protected function getPluginTemplateFileName()
	{
		return 'FrontendBrands.html';
	}

	protected function getThemeTemplateFileName()
	{
		return 'brand_plugin_frontend_brands.html';
	}

	protected function getPluginCssFileName()
	{
		return 'brands_page.css';
	}

	protected function getPluginJsFileName()
	{
//        $settings_storage = new shopBrandSettingsStorage();
//        $settings = $settings_storage->getSettings();
//
//        if($settings['use_brands_alpha'] || $settings['use_brands_search']) {
//            return 'brands_search.js';
//        }

		return false;
	}
}
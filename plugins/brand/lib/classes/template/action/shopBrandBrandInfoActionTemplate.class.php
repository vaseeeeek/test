<?php

class shopBrandBrandInfoActionTemplate extends shopBrandActionTemplate
{
	protected function getPluginTemplateFileName()
	{
		return 'BrandInfoPage.html';
	}

	protected function getThemeTemplateFileName()
	{
		return 'brand_plugin_brand_page_info.html';
	}

	protected function getPluginCssFileName()
	{
		return false;
	}

	protected function getPluginJsFileName()
	{
		return false;
	}
}
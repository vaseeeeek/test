<?php

class shopBrandBrandCatalogActionThemeTemplate extends shopBrandActionThemeTemplate
{
	protected function getThemeDefaultTemplateFileName()
	{
		return 'search.html';
	}

	/** @return string */
	protected function getThemeTemplateFileName()
	{
		return 'brand_plugin_brand_page_catalog.html';
	}

	/** @return string|false */
	protected function getPluginCssFileName()
	{
		return false;
	}

	/** @return string|false */
	protected function getPluginJsFileName()
	{
		return false;
	}
}
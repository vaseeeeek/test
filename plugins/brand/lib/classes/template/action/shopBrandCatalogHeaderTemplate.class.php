<?php

class shopBrandCatalogHeaderTemplate extends shopBrandActionTemplate
{
	protected function getPluginTemplateFileName()
	{
		return 'CatalogHeader.html';
	}

	protected function getThemeTemplateFileName()
	{
		return 'brand_plugin_catalog_header.html';
	}

	protected function getPluginCssFileName()
	{
		return 'catalog_header.css';
	}

	protected function getPluginJsFileName()
	{
		return false;
	}
}
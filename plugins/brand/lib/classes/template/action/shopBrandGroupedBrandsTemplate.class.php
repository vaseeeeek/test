<?php

class shopBrandGroupedBrandsTemplate extends shopBrandActionTemplate
{
	protected function getPluginTemplateFileName()
	{
		return 'GroupedBrands.html';
	}

	protected function getThemeTemplateFileName()
	{
		return 'brand_plugin_grouped_brands.html';
	}

	protected function getPluginCssFileName()
	{
		return 'grouped_brands.css';
	}

	protected function getPluginJsFileName()
	{
		return false;
	}

	protected function getPluginTemplateRoot()
	{
		return 'templates/handlers/';
	}

}
<?php

class shopBrandSearchBrandsTemplate extends shopBrandActionTemplate
{
	protected function getPluginTemplateFileName()
	{
		return 'SearchBrands.html';
	}

	protected function getThemeTemplateFileName()
	{
		return 'brand_plugin_search_brands.html';
	}

	protected function getPluginCssFileName()
	{
		return false;
	}

	protected function getPluginJsFileName()
	{
		return 'brands_search.js';
	}

//	protected function getPluginTemplateRoot()
//	{
//		return 'templates/handlers/';
//	}

}
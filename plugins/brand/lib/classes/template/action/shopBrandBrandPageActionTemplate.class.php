<?php

class shopBrandBrandPageActionTemplate extends shopBrandActionTemplate
{
	/** @return string */
	protected function getPluginTemplateFileName()
	{
		return 'FrontendBrandPage.html';
	}

	/** @return string */
	protected function getThemeTemplateFileName()
	{
		return 'brand_plugin_brand_page.html';
	}

	/** @return string|false */
	protected function getPluginCssFileName()
	{
		return 'brand_page.css';
	}

	/** @return string|false */
	protected function getPluginJsFileName()
	{
		return false;
	}
}
<?php

class shopBrandBrandPagesTabsTemplate extends shopBrandActionTemplate
{
	/** @return string|false */
	protected function getPluginTemplateFileName()
	{
		return 'PagesTabs.html';
	}

	/** @return string */
	protected function getThemeTemplateFileName()
	{
		return 'brand_plugin_pages_tabs.html';
	}

	/** @return string|false */
	protected function getPluginCssFileName()
	{
		return 'pages_tabs.css';
	}

	/** @return string|false */
	protected function getPluginJsFileName()
	{
		return false;
	}
}
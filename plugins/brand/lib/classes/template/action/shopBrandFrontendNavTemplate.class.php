<?php

class shopBrandFrontendNavTemplate extends shopBrandActionTemplate
{
	protected function getPluginTemplateFileName()
	{
		return 'FrontendNav.html';
	}

	protected function getThemeTemplateFileName()
	{
		return 'brand_plugin_frontend_nav.html';
	}

	protected function getPluginCssFileName()
	{
		return 'frontend_nav.css';
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
<?php

abstract class shopBrandActionTemplate
{
	protected $theme;

	public function __construct(waTheme $theme)
	{
		$this->theme = $theme;
	}

	public function isThemeTemplate()
	{
		$theme_template_path = $this->getThemeTemplatePath();

		return $this->fileExists($theme_template_path);
	}

	public function getActionTemplate()
	{
		return $this->getPluginTemplatePath();
	}

	public function getActionThemeTemplate()
	{
		return $this->getThemeTemplateFileName();
	}

	/**
	 * @return string|false
	 */
	public function getActionCssUrl()
	{
		$theme_path = $this->getThemeCssPath();

		$plugin_css_file_name = $this->getPluginCssFileName();

		return $this->fileExists($theme_path)
			? rtrim(wa()->getInstance()->getUrl(true), '/') . $this->theme->getUrl() . $this->getThemeCssFileName()
			: ($plugin_css_file_name ? shopBrandHelper::getStaticUrl('css/' . $plugin_css_file_name, true) : false);
	}

	/**
	 * @return string|false
	 */
	public function getActionJsUrl()
	{
		$theme_path = $this->getThemeJsPath();

		$plugin_js_file_name = $this->getPluginJsFileName();

		return $this->fileExists($theme_path)
			? rtrim(wa()->getInstance()->getUrl(true), '/') . $this->theme->getUrl() . $this->getThemeJsFileName()
			: ($plugin_js_file_name ? shopBrandHelper::getStaticUrl('js/' . $plugin_js_file_name, true) : false);
	}


	/** @return string|false */
	abstract protected function getPluginTemplateFileName();

	/** @return string */
	abstract protected function getThemeTemplateFileName();

	/** @return string|false */
	abstract protected function getPluginCssFileName();

	/** @return string|false */
	abstract protected function getPluginJsFileName();

	protected function templateIsThemeOnly()
	{
		return false;
	}

	protected function getThemeCssFileName()
	{
		$name = $this->getPluginCssFileName();

		return $name ? 'brand_plugin_' . $name : false;
	}

	protected function getThemeJsFileName()
	{
		$name = $this->getPluginJsFileName();

		return $name ? 'brand_plugin_' . $name : false;
	}

	protected function getPluginTemplatePath()
	{
		return shopBrandHelper::getPath($this->getPluginTemplateRoot() . $this->getPluginTemplateFileName());
	}

	protected function getPluginTemplateRoot()
	{
		return 'templates/actions/frontend/';
	}

	protected function getPluginCssPath()
	{
		$file = $this->getPluginCssFileName();

		return $file ? shopBrandHelper::getPath('css/' . $file) : false;
	}

	protected function getPluginJsPath()
	{
		$file = $this->getPluginJsFileName();

		return $file ? shopBrandHelper::getPath('js/' . $file) : false;
	}


	protected function getThemeTemplatePath()
	{
		return $this->theme->getPath() . '/' . $this->getThemeTemplateFileName();
	}

	protected function getThemeCssPath()
	{
		$file_name = $this->getThemeCssFileName();

		return $file_name
			? $this->theme->getPath() . '/' . $file_name
			: false;
	}

	protected function getThemeJsPath()
	{
		$file_name = $this->getThemeJsFileName();

		return $file_name
			? $this->theme->getPath() . '/' . $file_name
			: false;
	}


	protected function fileExists($path)
	{
		if ($path === false)
		{
			return false;
		}

		if (!file_exists($path))
		{
			return false;
		}

		$content = file_get_contents($path);
		return $content !== false && strlen(trim($content));
	}
}
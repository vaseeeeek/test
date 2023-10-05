<?php

class shopProductgroupWaMarkupTemplateFileStorage implements shopProductgroupMarkupTemplateFileStorage
{
	private static $themes = null;

	public function __wakeup()
	{
		self::$themes = null;
	}

	public function getPluginContent(shopProductgroupMarkupTemplate $template)
	{
		return file_get_contents($this->getPluginPath($template));
	}

	public function getThemeDefaultContent(shopProductgroupMarkupTemplate $template)
	{
		$theme = $this->getTheme($template);

		$path_original = $theme->path_original;
		$file_path = $path_original . '/' . $template->theme_file_name;

		return is_string($path_original) && file_exists($file_path)
			? file_get_contents($file_path)
			: null;
	}

	public function getThemeContent(shopProductgroupMarkupTemplate $template)
	{
		if (!$this->isThemeFileExist($template))
		{
			return null;
		}

		$path = $this->getThemePath($template);

		return file_get_contents($path);
	}

	public function storeThemeContent(shopProductgroupMarkupTemplate $template, $content)
	{
		$theme = $this->getTheme($template);
		$path = $this->getThemePath($template);

		$file_description = '';

		$path_info = pathinfo($path);
		$file_name = $path_info['basename'];

		$theme_file_info = $theme->getFile($file_name);

		if (count($theme_file_info) === 0)
		{
			$theme->addFile($file_name, $file_description);
		}
		else
		{
			$theme->changeFile($file_name, $file_description);
		}

		$path = $this->getThemePath($template); // гарантируем, что файл будет уже из wa-data

		waFiles::write($path, $content);
	}

	public function isThemeFileExist(shopProductgroupMarkupTemplate $template)
	{
		return file_exists($this->getThemePath($template));
	}

	public function getThemePath(shopProductgroupMarkupTemplate $template)
	{
		$theme = $this->getTheme($template);

		return $theme->getPath() . '/' . $template->theme_file_name;
	}

	public function getPluginPath(shopProductgroupMarkupTemplate $template)
	{
		return shopProductgroupWaHelper::getPath("templates/templates/{$template->plugin_file_name}");
	}

	public function deleteThemeFile(shopProductgroupMarkupTemplate $template)
	{
		$theme = $this->getTheme($template);

		$theme_path = $this->getThemePath($template);

		if (file_exists($theme_path))
		{
			$theme->removeFile($template->theme_file_name);
		}
	}

	/**
	 * @param shopProductgroupMarkupTemplate $template
	 * @return waTheme
	 * @throws waException
	 */
	private function getTheme(shopProductgroupMarkupTemplate $template)
	{
		$theme_id = $template->theme_id;

		if (!is_array(self::$themes))
		{
			self::$themes = wa('shop')->getThemes('shop');
		}

		if (isset(self::$themes[$theme_id]))
		{
			return self::$themes[$theme_id];
		}

		throw new waException("Нет темы с id [{$theme_id}]");
	}
}
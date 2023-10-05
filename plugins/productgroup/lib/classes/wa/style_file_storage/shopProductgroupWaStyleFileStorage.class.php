<?php

class shopProductgroupWaStyleFileStorage implements shopProductgroupStyleFileStorage
{
	const THEME_BASE_STYLE_FILE_NAME = 'productgroup_plugin.groups_block.css';

	private static $themes = null;

	public function __wakeup()
	{
		self::$themes = null;
	}

	public function getPluginBaseStyleContent()
	{
		return file_get_contents($this->getPluginBaseStylePath());
	}

	public function getPluginCustomStyleTemplateContent()
	{
		return file_get_contents($this->getPluginCustomStyleTemplatePath());
	}

	public function getThemeBaseStyleContent($theme_id)
	{
		$theme_path = $this->getThemeBaseStylePath($theme_id);

		return file_exists($theme_path)
			? file_get_contents($theme_path)
			: null;
	}

	public function isThemeBaseStyleExist($theme_id)
	{
		return file_exists($this->getThemeBaseStylePath($theme_id));
	}

	public function storeThemeBaseStyleContent($theme_id, $content)
	{
		$theme = $this->getTheme($theme_id);
		$path = $this->getThemeBaseStylePath($theme_id);

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

		$path_updated = $this->getThemeBaseStylePath($theme_id); // гарантируем, что файл будет уже из wa-data

		waFiles::write($path_updated, $content);
	}

	public function getPluginBaseStylePath()
	{
		return shopProductgroupWaHelper::getPath('resources/css/groups_block_base.css');
	}

	public function getPluginCustomStyleTemplatePath()
	{
		return shopProductgroupWaHelper::getPath('templates/templates/groups_block_custom.css_template');
	}

	public function getThemeBaseStylePath($theme_id)
	{
		$theme = $this->getTheme($theme_id);

		return $theme->getPath() . '/' . $this->getThemeBaseStyleFileName();
	}

	public function deleteThemeBaseStyle($theme_id)
	{
		$theme = $this->getTheme($theme_id);

		$theme_path = $this->getThemeBaseStylePath($theme_id);

		if (file_exists($theme_path))
		{
			$theme->removeFile($this->getThemeBaseStyleFileName());
		}
	}

	public function getThemeBaseStyleFileName()
	{
		return self::THEME_BASE_STYLE_FILE_NAME;
	}

	/**
	 * @param string $theme_id
	 * @return waTheme
	 * @throws waException
	 */
	private function getTheme($theme_id)
	{
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
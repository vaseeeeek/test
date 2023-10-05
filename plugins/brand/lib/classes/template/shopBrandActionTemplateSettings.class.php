<?php

// todo сохранять в базу??
class shopBrandActionTemplateSettings extends shopBrandActionTemplate
{
	const FILE_TYPE_SMARTY = 'smarty';
	const FILE_TYPE_JS = 'js';
	const FILE_TYPE_CSS = 'css';

	/** @var shopBrandActionTemplate|shopBrandActionThemeTemplate  */
	protected $action_template;

	public function __construct($action_template)
	{
		parent::__construct($action_template->theme);

		$this->action_template = $action_template;
	}


	/**
	 * @param string $new_content
	 * @param string $type
	 * @throws waException
	 */
	public function saveContent($new_content, $type)
	{
		try
		{
			$theme_file_path = $this->getThemeFilePath($type);
		}
		catch (waException $e)
		{
			return;
		}

		if (!$theme_file_path)
		{
			return;
		}

		$default_content = $this->getDefaultContent($type);
		if ($default_content == $new_content || $new_content === '')
		{
			$this->removeFromTheme($theme_file_path);

			return;
		}

		$themeFileName = $this->getThemeFileName($type);
		if ($type == self::FILE_TYPE_SMARTY || $themeFileName)
		{
			$this->addToTheme($theme_file_path, $new_content, $this->getActionFileDescription($type));
		}
	}

	public function getTemplateContent()
	{}
	public function getJsContent()
	{}
	public function getCssContent()
	{}

	public function restoreDefault()
	{}


	public function isUsed($type)
	{
		return $this->fileExists($this->getThemeFilePath($type));
	}

	public function isCustom($type)
	{
		return $this->getDefaultContent($type) != $this->getContent($type);
	}

	public function getContent($type)
	{
		return $this->isUsed($type)
			? file_get_contents($this->getThemeFilePath($type))
			: $this->getDefaultContent($type);
	}

	public function getDefaultContent($type)
	{
		$default_file_path = $this->getDefaultFilePath($type);

		return $default_file_path ? file_get_contents($default_file_path) : '';
	}

	public function isThemeOnly($type)
	{
		if ($type == self::FILE_TYPE_SMARTY && ($this->action_template instanceof shopBrandActionThemeTemplate))
		{
			return true;
		}

		$defaultFilePath = $this->getDefaultFilePath($type);

		return !($defaultFilePath);
	}

	public function getThemeDefaultFileName($type)
	{
		$action_template = $this->action_template;

		if ($type == self::FILE_TYPE_SMARTY && ($action_template instanceof shopBrandActionThemeTemplate))
		{
			return shopBrandActionTemplateSettingsUtil::_getThemeDefaultTemplateFileName($action_template);
		}
		elseif ($type == self::FILE_TYPE_SMARTY)
		{
			return $this->action_template->getThemeTemplateFileName();
		}
		elseif ($type == self::FILE_TYPE_JS)
		{
			return $this->action_template->getThemeJsFileName();
		}
		elseif ($type == self::FILE_TYPE_CSS)
		{
			return $this->action_template->getThemeCssFileName();
		}

		throw new waException();
	}

	public function getThemeFileName($type)
	{
		$action_template = $this->action_template;

		if ($type == self::FILE_TYPE_SMARTY && ($action_template instanceof shopBrandActionThemeTemplate))
		{
			return $this->action_template->getThemeTemplateFileName();
		}
		elseif ($type == self::FILE_TYPE_SMARTY)
		{
			return $this->action_template->getThemeTemplateFileName();
		}
		elseif ($type == self::FILE_TYPE_JS)
		{
			return $this->action_template->getThemeJsFileName();
		}
		elseif ($type == self::FILE_TYPE_CSS)
		{
			return $this->action_template->getThemeCssFileName();
		}

		throw new waException();
	}



	private function getThemeFilePath($type)
	{
		if ($type == self::FILE_TYPE_SMARTY)
		{
			return $this->action_template->getThemeTemplatePath();
		}
		elseif ($type == self::FILE_TYPE_JS)
		{
			return $this->action_template->getThemeJsPath();
		}
		elseif ($type == self::FILE_TYPE_CSS)
		{
			return $this->action_template->getThemeCssPath();
		}

		throw new waException();
	}

	private function getDefaultFilePath($type)
	{
		$action_template = $this->action_template;

		if ($type == self::FILE_TYPE_SMARTY && ($action_template instanceof shopBrandActionThemeTemplate))
		{
			return $action_template->getThemeDefaultTemplatePath();
		}
		elseif ($type == self::FILE_TYPE_SMARTY)
		{
			return $action_template->getPluginTemplatePath();
		}
		elseif ($type == self::FILE_TYPE_JS)
		{
			return $action_template->getPluginJsPath();
		}
		elseif ($type == self::FILE_TYPE_CSS)
		{
			return $action_template->getPluginCssPath();
		}

		throw new waException();
	}

	private function removeFromTheme($path)
	{
		$path_info = pathinfo($path);
		$file_name = $path_info['basename'];

		$this->theme->removeFile($file_name);
		waFiles::delete($path);
	}

	private function addToTheme($path, $content, $file_description)
	{
		$path_info = pathinfo($path);
		$file_name = $path_info['basename'];

		$theme_file_info = $this->theme->getFile($file_name);

		if (count($theme_file_info))
		{
			$this->theme->changeFile($file_name, $file_description);
		}
		else
		{
			$this->theme->addFile($file_name, $file_description);
		}

		waFiles::write($path, $content);
	}

	// todo
	private function getActionFileDescription($type)
	{
		//if ($this->action_template instanceof )


		return 'Файл плагина "Бренды PRO"';
	}








	/** @return string|false */
	protected function getPluginTemplateFileName()
	{
		return $this->action_template->getPluginTemplateFileName();
	}

	/** @return string */
	protected function getThemeTemplateFileName()
	{
		return $this->action_template->getThemeTemplateFileName();
	}

	/** @return string|false */
	protected function getPluginCssFileName()
	{
		return $this->action_template->getPluginCssFileName();
	}

	/** @return string|false */
	protected function getPluginJsFileName()
	{
		return $this->action_template->getPluginJsFileName();
	}
}
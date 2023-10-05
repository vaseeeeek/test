<?php

abstract class shopBrandActionThemeTemplate extends shopBrandActionTemplate
{
	public function isThemeTemplate()
	{
		return true;
	}

	public function getActionThemeTemplate()
	{
		$path = $this->getThemeTemplatePath();

		return $this->fileExists($path)
			? $this->getThemeTemplateFileName()
			: $this->getThemeDefaultTemplateFileName();
	}

	abstract protected function getThemeDefaultTemplateFileName();

	protected function getPluginTemplateFileName()
	{
		return false;
	}

	public function getThemeDefaultTemplatePath()
	{
		return $this->theme->getPath() . '/' . $this->getThemeDefaultTemplateFileName();
	}

	protected function templateIsThemeOnly()
	{
		return true;
	}
}
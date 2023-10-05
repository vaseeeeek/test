<?php

class shopProductgroupStyleRegistry
{
	private $style_file_storage;

	public function __construct()
	{
		$this->style_file_storage = shopProductgroupPluginContext::getInstance()->getStyleFileStorage();
	}

	public function getBaseStyleContent($theme_id)
	{
		return $this->style_file_storage->isThemeBaseStyleExist($theme_id)
			? $this->style_file_storage->getThemeBaseStyleContent($theme_id)
			: $this->style_file_storage->getPluginBaseStyleContent();
	}

	public function getCustomStyleTemplateContent()
	{
		return $this->style_file_storage->getPluginCustomStyleTemplateContent();
	}
}
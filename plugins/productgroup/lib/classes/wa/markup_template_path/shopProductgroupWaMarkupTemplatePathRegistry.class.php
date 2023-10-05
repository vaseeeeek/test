<?php

class shopProductgroupWaMarkupTemplatePathRegistry implements shopProductgroupMarkupTemplatePathRegistry
{
	private $template_file_storage;

	public function __construct(shopProductgroupMarkupTemplateFileStorage $template_file_storage)
	{
		$this->template_file_storage = $template_file_storage;
	}

	public function getTemplatePath(shopProductgroupMarkupTemplate $template)
	{
		return $this->template_file_storage->isThemeFileExist($template)
			? $this->template_file_storage->getThemePath($template)
			: $this->template_file_storage->getPluginPath($template);
	}
}
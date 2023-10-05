<?php

interface shopProductgroupStyleFileStorage
{
	public function getPluginBaseStyleContent();

	public function getPluginCustomStyleTemplateContent();

	public function getThemeBaseStyleContent($theme_id);

	public function isThemeBaseStyleExist($theme_id);

	public function storeThemeBaseStyleContent($theme_id, $content);

	public function getPluginBaseStylePath();

	public function getPluginCustomStyleTemplatePath();

	public function getThemeBaseStylePath($theme_id);

	public function deleteThemeBaseStyle($theme_id);

	public function getThemeBaseStyleFileName();
}
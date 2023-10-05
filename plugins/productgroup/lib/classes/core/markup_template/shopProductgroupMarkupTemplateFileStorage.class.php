<?php

interface shopProductgroupMarkupTemplateFileStorage
{
	public function getPluginContent(shopProductgroupMarkupTemplate $template);

	public function getThemeDefaultContent(shopProductgroupMarkupTemplate $template);

	public function getThemeContent(shopProductgroupMarkupTemplate $template);

	public function storeThemeContent(shopProductgroupMarkupTemplate $template, $content);

	public function isThemeFileExist(shopProductgroupMarkupTemplate $template);

	public function getThemePath(shopProductgroupMarkupTemplate $template);

	public function getPluginPath(shopProductgroupMarkupTemplate $template);

	public function deleteThemeFile(shopProductgroupMarkupTemplate $page_template);
}
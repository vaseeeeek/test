<?php

class shopProductgroupPluginSettingsGetThemeSettingsController extends waJsonController
{
	public function execute()
	{
		$this->response['success'] = false;

		$theme_id = waRequest::get('theme_id');


		$context = shopProductgroupPluginContext::getInstance();
		$markup_template_settings_storage = $context->getMarkupTemplateSettingsStorage();
		$markup_template_settings_assoc_mapper = $context->getMarkupTemplateSettingsAssocMapper();

		$settings = $markup_template_settings_storage->getSettingsForTheme($theme_id);
		if (!$settings)
		{
			return;
		}

		$style_file_storage = $context->getStyleFileStorage();

		$custom_style_exist = $style_file_storage->isThemeBaseStyleExist($theme_id);
		$base_styles_settings = [
			'is_default' => !$custom_style_exist,
			'content' => $custom_style_exist
				? $style_file_storage->getThemeBaseStyleContent($theme_id)
				: $style_file_storage->getPluginBaseStyleContent(),
		];

		$this->response['state'] = [
			'markup_template_settings' => $markup_template_settings_assoc_mapper->settingsToAssoc($settings),
			'base_styles_settings' => $base_styles_settings,
		];
		$this->response['success'] = true;
	}
}
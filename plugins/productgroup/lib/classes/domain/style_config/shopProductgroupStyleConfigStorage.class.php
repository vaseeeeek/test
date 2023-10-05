<?php

class shopProductgroupStyleConfigStorage
{
	private $style_settings_storage;
	private $style_settings_assoc_mapper;

	public function __construct()
	{
		$this->style_settings_storage = shopProductgroupPluginContext::getInstance()->getMarkupStyleSettingsStorage();
		$this->style_settings_assoc_mapper = shopProductgroupPluginContext::getInstance()->getMarkupStyleSettingsAssocMapper();
	}

	public function getConfig($theme_id, $storefront)
	{
		$general_settings = $this->style_settings_storage->getThemeStorefrontSettings($theme_id, shopProductgroupGeneralStorefront::NAME);
		$personal_settings = null;

		if ($this->style_settings_storage->haveThemeStorefrontSettings($theme_id, $storefront))
		{
			$personal_settings = $this->style_settings_storage->getThemeStorefrontSettings($theme_id, $storefront);
		}

		return $this->buildConfig($general_settings, $personal_settings);
	}

	/**
	 * @param shopProductgroupMarkupStyleSettings $general_settings
	 * @param shopProductgroupMarkupStyleSettings|null $personal_settings
	 * @return shopProductgroupStyleConfig
	 */
	private function buildConfig($general_settings, $personal_settings)
	{
		$personal_settings_assoc = $personal_settings
			? $this->style_settings_assoc_mapper->toAssoc($personal_settings)
			: null;

		$result_settings_assoc = [];
		foreach ($this->style_settings_assoc_mapper->toAssoc($general_settings) as $field => $general_value)
		{
			$result_settings_assoc[$field] = $personal_settings_assoc && trim($personal_settings_assoc[$field]) !== ''
				? trim($personal_settings_assoc[$field])
				: trim($general_value);
		}

		$result_settings = new shopProductgroupMarkupStyleSettings();
		$this->style_settings_assoc_mapper->mapToObject($result_settings, $result_settings_assoc);

		return new shopProductgroupStyleConfig(
			$result_settings->is_plugin_css_used,

			$this->tryAddPx($result_settings->groups_header_font_size),

			$result_settings->simple_group_font_color,
			$result_settings->simple_group_background_color,
			$result_settings->simple_group_border_color,
			$this->tryAddPx($result_settings->simple_group_border_width),
			$result_settings->simple_group_active_border_color,
			$result_settings->simple_group_border_hover_color,

			$this->tryAddPx($result_settings->photo_group_item_height),
			$this->tryAddPx($result_settings->photo_group_item_width),
			$this->tryAddPx($result_settings->photo_group_border_radius),
			$this->tryAddPx($this->trySubtract($result_settings->photo_group_border_radius, 2)),
			$result_settings->photo_group_border_color,
			$result_settings->photo_group_active_border_color,

			$result_settings->color_group_border_color,
			$result_settings->color_group_border_hover_color,
			$this->tryAddPx($result_settings->color_group_border_width),
			$result_settings->color_group_active_border_color,
			$this->tryAddPx($result_settings->color_group_border_radius)
		);
	}

	private function tryAddPx($value)
	{
		return wa_is_int($value)
			? "{$value}px"
			: $value;
	}

	private function trySubtract($value, $subtract)
	{
		return wa_is_int($value) || preg_match('/px$/', $value)
			? intval(str_replace('px', '', $value)) - $subtract
			: $value;
	}
}
<?php

class shopProductgroupMarkupStyleSettingsStorage extends shopProductgroupKeyValueStorage
{
	private $settings_data_source;

	public function __construct(shopProductgroupMarkupStyleSettingsDataSource $settings_data_source)
	{
		$this->settings_data_source = $settings_data_source;
	}

	/**
	 * @param string $theme_id
	 * @param string $storefront
	 * @return shopProductgroupMarkupStyleSettings
	 */
	public function getThemeStorefrontSettings($theme_id, $storefront)
	{
		$settings_raw = $this->settings_data_source->fetchThemeStorefrontSettings($theme_id, $storefront);

		return $this->buildSettingsByRaw($settings_raw);
	}

	public function haveThemeStorefrontSettings($theme_id, $storefront)
	{
		return $this->settings_data_source->haveThemeStorefrontSettings($theme_id, $storefront);
	}

	public function saveThemeStorefrontSettings($theme_id, $storefront, shopProductgroupMarkupStyleSettings $settings)
	{
		$settings_raw = [];

		foreach (get_class_vars(get_class($settings)) as $field => $_)
		{
			$settings_raw[$field] = $this->valueToRaw($field, $settings->$field);
		}

		return $this->storeRawSettings($storefront, $theme_id, $settings_raw);
	}

	public function deleteThemeStorefrontSettings($theme_id, $storefront)
	{
		return $this->settings_data_source->deleteThemeStorefrontSettings($theme_id, $storefront);
	}

	/**
	 * @return shopProductgroupStoredValueSpecification[]
	 */
	protected function getFieldSpecifications()
	{
		return [
			'is_plugin_css_used' => new shopProductgroupStoredValueSpecification(
				shopProductgroupStoredValueType::BOOL,
				true
			),

			'groups_header_font_size' => new shopProductgroupStoredValueSpecification(
				shopProductgroupStoredValueType::STRING_TRIM,
				''
			),

			'simple_group_font_color' => new shopProductgroupStoredValueSpecification(
				shopProductgroupStoredValueType::STRING_TRIM,
				''
			),
			'simple_group_background_color' => new shopProductgroupStoredValueSpecification(
				shopProductgroupStoredValueType::STRING_TRIM,
				''
			),
			'simple_group_border_color' => new shopProductgroupStoredValueSpecification(
				shopProductgroupStoredValueType::STRING_TRIM,
				''
			),
			'simple_group_border_width' => new shopProductgroupStoredValueSpecification(
				shopProductgroupStoredValueType::STRING_TRIM,
				''
			),
			'simple_group_active_border_color' => new shopProductgroupStoredValueSpecification(
				shopProductgroupStoredValueType::STRING_TRIM,
				''
			),
			'simple_group_border_hover_color' => new shopProductgroupStoredValueSpecification(
				shopProductgroupStoredValueType::STRING_TRIM,
				''
			),

			'photo_group_item_height' => new shopProductgroupStoredValueSpecification(
				shopProductgroupStoredValueType::STRING_TRIM,
				''
			),
			'photo_group_item_width' => new shopProductgroupStoredValueSpecification(
				shopProductgroupStoredValueType::STRING_TRIM,
				''
			),
			'photo_group_border_radius' => new shopProductgroupStoredValueSpecification(
				shopProductgroupStoredValueType::STRING_TRIM,
				''
			),
			'photo_group_border_color' => new shopProductgroupStoredValueSpecification(
				shopProductgroupStoredValueType::STRING_TRIM,
				''
			),
			'photo_group_active_border_color' => new shopProductgroupStoredValueSpecification(
				shopProductgroupStoredValueType::STRING_TRIM,
				''
			),

			'color_group_border_color' => new shopProductgroupStoredValueSpecification(
				shopProductgroupStoredValueType::STRING_TRIM,
				''
			),
			'color_group_border_hover_color' => new shopProductgroupStoredValueSpecification(
				shopProductgroupStoredValueType::STRING_TRIM,
				''
			),
			'color_group_border_width' => new shopProductgroupStoredValueSpecification(
				shopProductgroupStoredValueType::STRING_TRIM,
				''
			),
			'color_group_active_border_color' => new shopProductgroupStoredValueSpecification(
				shopProductgroupStoredValueType::STRING_TRIM,
				''
			),
			'color_group_border_radius' => new shopProductgroupStoredValueSpecification(
				shopProductgroupStoredValueType::STRING_TRIM,
				''
			),
		];
	}

	/**
	 * @param $settings_raw
	 * @return shopProductgroupMarkupStyleSettings
	 * @throws shopProductgroupIncompleteStorageSpecification
	 */
	private function buildSettingsByRaw($settings_raw)
	{
		$settings = new shopProductgroupMarkupStyleSettings();

		foreach (get_class_vars(get_class($settings)) as $field => $_)
		{
			$settings->$field = $this->getValueFromRaw($settings_raw, $field);
		}

		return $settings;
	}

	private function storeRawSettings($storefront, $theme_id, array $settings_raw)
	{
		$success = true;

		$success = $this->settings_data_source->storeThemeStorefrontSettings($theme_id, $storefront, $settings_raw) && $success;

		return $success;
	}
}
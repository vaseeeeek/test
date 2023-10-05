<?php

class shopProductgroupStorefrontThemeMarkupStyleSettingsModel extends waModel implements shopProductgroupMarkupStyleSettingsDataSource
{
	protected $table = 'shop_productgroup_storefront_theme_markup_style_settings';

	public function fetchThemeStorefrontSettings($theme_id, $storefront)
	{
		return $this->select('name,value')
			->where('storefront = :storefront', ['storefront' => $storefront])
			->where('theme_id = :theme_id', ['theme_id' => $theme_id])
			->fetchAll('name', true);
	}

	public function haveThemeStorefrontSettings($theme_id, $storefront)
	{
		$count = $this->select('COUNT(*)')
			->where('storefront = :storefront', ['storefront' => $storefront])
			->where('theme_id = :theme_id', ['theme_id' => $theme_id])
			->fetchField();

		$count = intval($count);

		return $count > 0;
	}

	public function storeThemeStorefrontSettings($theme_id, $storefront, $settings_raw)
	{
		$success = true;

		foreach ($settings_raw as $field => $value)
		{
			$success = $this->replace([
				'storefront' => $storefront,
				'theme_id' => $theme_id,
				'name' => $field,
				'value' => $value,
			]) && $success;
		}

		return $success;
	}

	public function deleteThemeStorefrontSettings($theme_id, $storefront)
	{
		return $this->deleteByField([
			'storefront' => $storefront,
			'theme_id' => $theme_id,
		]);
	}
}
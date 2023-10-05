<?php

interface shopProductgroupMarkupStyleSettingsDataSource
{
	public function fetchThemeStorefrontSettings($theme_id, $storefront);

	public function haveThemeStorefrontSettings($theme_id, $storefront);

	public function storeThemeStorefrontSettings($theme_id, $storefront, $settings_raw);

	public function deleteThemeStorefrontSettings($theme_id, $storefront);
}
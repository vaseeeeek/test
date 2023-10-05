<?php

class shopBrandSeofilterHelper
{
	private static $is_seofilter_installed = null;
	private static $is_seofilter_enabled = null;

	public function isSeofilterPluginInstalled()
	{
		if (self::$is_seofilter_installed === null)
		{
			$info = wa('shop')->getConfig()->getPluginInfo('seofilter');

			self::$is_seofilter_installed =
				$info !== array()
				&& class_exists('shopSeofilterPluginSettings')
				&& class_exists('shopSeofilterFiltersStorage');
		}

		return self::$is_seofilter_installed;
	}

	public function isSeofilterPluginEnabled()
	{
		if (self::$is_seofilter_enabled === null)
		{
			if ($this->isSeofilterPluginInstalled())
			{
				$settings = shopSeofilterBasicSettingsModel::getSettings();

				$is_seofilter_enabled = $settings->is_enabled;
			}
			else
			{
				$is_seofilter_enabled = false;
			}

			self::$is_seofilter_enabled = $is_seofilter_enabled;
		}

		return self::$is_seofilter_enabled;
	}
}
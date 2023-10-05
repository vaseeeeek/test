<?php

class shopBuy1clickWaInstallerHelper
{
	private static $app_plugins_info = array();

	public static function isBuy1clickInstalled()
	{
		return self::isPluginInstalled(shopBuy1clickPlugin::SHOP_ID, 'buy1click');
	}

	public static function isShopPluginInstalled($plugin_id)
	{
		return self::isPluginInstalled(shopBuy1clickPlugin::SHOP_ID, $plugin_id);
	}

	public static function isPluginInstalled($app_id, $plugin_id)
	{
		if (!array_key_exists($app_id, self::$app_plugins_info))
		{
			self::$app_plugins_info[$app_id] = array();
		}

		if (!array_key_exists($plugin_id, self::$app_plugins_info[$app_id]))
		{
			self::$app_plugins_info[$app_id][$plugin_id] = wa($app_id)->getConfig()->getPluginInfo($plugin_id);
		}

		return self::$app_plugins_info[$app_id][$plugin_id] === array();
	}
}

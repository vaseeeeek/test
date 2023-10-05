<?php

abstract class shopEditAbstractPluginHelper
{
	private static $_plugins_info = array();
	private static $_plugins_instance = array();

	abstract public function isPluginInstalled();

	abstract public function isPluginEnabled();

	abstract public function getPluginId();

	public function getAppId()
	{
		return 'shop';
	}

	public function getPluginInfoExtended()
	{
		return $this->getPluginInfoRaw();
	}

	/**
	 * @return waPlugin
	 * @throws waException
	 */
	public function getPluginInstance()
	{
		$app_id = $this->getAppId();
		$plugin_id = $this->getPluginId();

		$key = "{$app_id}/{$plugin_id}";

		if (!array_key_exists($key, self::$_plugins_instance))
		{
			self::$_plugins_instance[$key] = wa($app_id)->getPlugin($plugin_id);
		}

		return self::$_plugins_instance[$key];
	}

	protected function getPluginInfoRaw()
	{
		$app_id = $this->getAppId();
		$plugin_id = $this->getPluginId();

		$key = "{$app_id}/{$plugin_id}";

		if (!array_key_exists($key, self::$_plugins_info))
		{
			self::$_plugins_info[$key] = wa($app_id)->getConfig()->getPluginInfo($plugin_id);
		}

		return self::$_plugins_info[$key];
	}
}
<?php

class shopDpSettingsStorage
{
	protected static $env;
	protected static $settings_model;
	protected static $settings;
	protected static $settings_config;

	protected $settings_filler;

	public function __construct($env = null)
	{
		if($env !== null && $env instanceof shopDpEnv) {
			self::$env = $env;
		}
	}

	protected static function getSettingsModel()
	{
		if(!self::$settings_model) {
			self::$settings_model = new shopDpSettingsModel();
		}

		return self::$settings_model;
	}

	protected static function getEnv()
	{
		if(!isset(self::$env))
			self::$env = new shopDpEnv();

		return self::$env;
	}

	protected function getSettingsFiller()
	{
		if(!isset($this->settings_filler))
			$this->settings_filler = new shopDpSettingsFiller();

		return $this->settings_filler;
	}

	protected static function loadSettingsConfig()
	{
		$path = wa()->getAppPath('plugins/dp/lib/config/data/settings/settings.php', 'shop');

		if(file_exists($path)) {
			$settings_config = include($path);

			if(!is_array($settings_config)) {
				$settings_config = array();
			}
		} else {
			$settings_config = array();
		}

		return $settings_config;
	}

	public static function getSettingsConfig()
	{
		if(self::$settings_config === null) {
			$settings_config = self::loadSettingsConfig();

			self::$settings_config = $settings_config;
		}

		return self::$settings_config;
	}

	public function fillSettings($save = true)
	{
		$settings = $this->getSettings();

		$shipping_methods = $this->getEnv()->getShippingPlugins(true);
		$payment_methods = $this->getEnv()->getPaymentPlugins();

		$fill = $this->doFillSettings($settings, compact('shipping_methods', 'payment_methods'));

		if($save && $fill) {
			self::getSettingsModel()->set($settings);
		}

		return $settings;
	}

	public static function getBasicSettings($name = null)
	{
		if(self::$settings === null) {
			$settings_config = self::getSettingsConfig();
			self::$settings = self::getSettingsModel()->get($settings_config);
		}

		if($name === null) {
			return self::$settings['basic'];
		} else {
			return ifset(self::$settings, 'basic', $name, null);
		}
	}

	public function getSettings($name = null, $storefront_id = null, $is_null_if_wo_diff = false, $is_all_variants = false)
	{
		if(self::$settings === null) {
			self::$settings = self::getSettingsModel()->get($this->getSettingsConfig());
		}

		return $this->getEnv()->getActiveSettings(self::$settings, $name, $storefront_id, $this->getSettingsConfig(), $is_null_if_wo_diff, $is_all_variants);
	}

	private function doFillSettings(&$settings, $params)
	{
		return $this->getSettingsFiller()->fillSettings($settings, $params);
	}
}

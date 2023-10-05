<?php

class shopDpSettingsModel extends waModel
{
	protected $table = 'shop_dp_settings';
	protected $storefront_model;
	protected $theme_model;

	public function __construct($type = null, $writable = false)
	{
		parent::__construct($type, $writable);

		$this->storefront_model = new shopDpStorefrontSettingsModel();
		$this->theme_model = new shopDpThemeSettingsModel();
	}

	protected function getCache()
	{
		return new waVarExportCache('app_settings/shop.dp', SystemConfig::isDebug() ? 600 : 86400, 'webasyst');
	}

	public function get($settings_config = array())
	{
		if(!isset($this->settings)) {
			$cache = $this->getCache();
			$settings = $cache->get();

			if($settings === null || !is_array($settings)) {
				$basic = $this->getSettings();
				$storefronts = $this->storefront_model->getSettings();
				$themes = $this->theme_model->getSettings();

				$settings = array(
					'basic' => $basic,
					'storefronts' => $storefronts,
					'themes' => $themes
				);

				$cache->set($settings);
			}

			$this->settings = $settings;
		}

		if(!empty($settings_config))
			$this->settings = self::merge($settings_config, $this->settings);

		return $this->settings;
	}

	public function set($settings)
	{
		$this->getCache()->delete();

		$this->update(ifset($settings, 'basic', null));

		$this->storefront_model->update('*', ifset($settings, 'storefronts', '*', null));

		if(isset($settings['storefronts']['*']))
			unset($settings['storefronts']['*']);

		foreach($settings['storefronts'] as $storefront => $storefront_settings) {
			$this->storefront_model->update($storefront, $storefront_settings, true);
		}

		foreach($settings['themes'] as $theme => $theme_settings) {
			$this->theme_model->update($theme, $theme_settings, true);
		}
	}

	protected function update($settings)
	{
		if($settings === null)
			return;

		foreach($settings as $name => $value) {
			if(is_array($value))
				$value = json_encode($value);

			if($this->getSetting($name)) {
				$this->updateSetting($name, $value);
			} else {
				$this->addSetting($name, $value);
			}
		}
	}

	protected function getSettings()
	{
		$settings = $this->getAll('name');

		foreach($settings as &$row) {
			$data = $row['value'];

			if(!is_numeric($data)) {
				$json = json_decode($data, true);
				if(is_array($json)) {
					$data = $json;
				}
			}

			$row = $data;
		}

		return $settings;
	}

	protected function getSetting($name)
	{
		return $this->getByField('name', $name);
	}

	protected function updateSetting($name, $value)
	{
		return $this->updateByField(array(
			'name' => $name
		), array(
			'value' => $value
		));
	}

	protected function addSetting($name, $value)
	{
		return $this->insert(array(
			'name' => $name,
			'value' => $value
		));
	}

	private function merge($settings_config, $settings)
	{
		if(!function_exists('array_replace_recursive')) {
			function array_replace_recursive($base = array(), $replacements = array()) {
				foreach(array_slice(func_get_args(), 1) as $replacements) {
					$bref_stack = array(&$base);
					$head_stack = array($replacements);

					do {
						end($bref_stack);

						$bref = &$bref_stack[key($bref_stack)];
						$head = array_pop($head_stack);

						unset($bref_stack[key($bref_stack)]);

						foreach(array_keys($head) as $key) {
							if(isset($key, $bref) &&
								isset($bref[$key]) && is_array($bref[$key]) &&
								isset($head[$key]) && is_array($head[$key])
							) {
								$bref_stack[] = &$bref[$key];
								$head_stack[] = $head[$key];
							} else {
								$bref[$key] = $head[$key];
							}
						}
					} while(count($head_stack));
				}

				return $base;
			}
		}

		return array_replace_recursive($settings_config, $settings);
	}
}
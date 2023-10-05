<?php

class shopRegionsSettingsLegacy
{
	private $settings;

	public function __construct()
	{
		$model = new waAppSettingsModel();
		$this->settings = $model->get(array('shop', 'regions'));
	}


	public function get($name = null)
	{
		return $name === null
			? $this->settings
			: (isset($this->settings[$name]) ? $this->settings[$name] : null);
	}

	public function update($settings)
	{
		$model = new waAppSettingsModel();

		foreach ($settings as $name => $value)
		{
			$model->set(array('shop', 'regions'), $name, is_array($value) ? json_encode($value) : $value);
		}
	}
}
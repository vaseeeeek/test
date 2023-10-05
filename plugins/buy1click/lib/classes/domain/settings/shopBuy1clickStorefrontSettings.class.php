<?php


class shopBuy1clickStorefrontSettings
{
	private $data;
	
	public function __construct(shopBuy1clickSettingsData $settings_data)
	{
		$this->data = $settings_data;
	}
	
	public function toArray()
	{
		return array(
			'equal_form_settings' => $this->isEqualFormSettings(),
		);
	}
	
	public function getData()
	{
		return $this->data;
	}

	public function isEqualFormSettings()
	{
		return $this->data->getBool('equal_form_settings', true);
	}
}
<?php


class shopBuy1clickBasicSettings
{
	private $data;
	
	public function __construct(shopBuy1clickSettingsData $data)
	{
		$this->data = $data;
	}
	
	public function toArray()
	{
		return array(
			'is_enabled' => $this->isEnabled(),
		);
	}
	
	public function getData()
	{
		return $this->data;
	}
	
	public function isEnabled()
	{
		return $this->data->getBool('is_enabled', false);
	}
}
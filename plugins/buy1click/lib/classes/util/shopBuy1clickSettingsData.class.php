<?php


class shopBuy1clickSettingsData
{
	private $data;
	private $is_allow_null;
	
	public function __construct($data, $is_allow_null)
	{
		$this->data = $data;
		$this->is_allow_null = $is_allow_null;
	}
	
	public function fill($keys, $value)
	{
		$this->data = shopBuy1clickSettingsUtil::fill($keys, $value, $this->data);
	}
	
	public function merge(shopBuy1clickSettingsData $parent_data)
	{
		$this->is_allow_null = $parent_data->is_allow_null;
		$this->data = shopBuy1clickSettingsUtil::merge($this->data, $parent_data->data);
	}
	
	public function diff(shopBuy1clickSettingsData $parent_data)
	{
		$this->data = shopBuy1clickSettingsUtil::diff($this->data, $parent_data->data);
	}
	
	public function get($name, $default)
	{
		return shopBuy1clickSettingsUtil::get($this->data, $name, $default, $this->is_allow_null);
	}
	
	public function getBool($name, $default)
	{
		return shopBuy1clickSettingsUtil::getBool($this->data, $name, $default, $this->is_allow_null);
	}
	
	public function getFromVariants($name, $variants, $default)
	{
		return shopBuy1clickSettingsUtil::getFromVariants($this->data, $name, $variants, $default, $this->is_allow_null);
	}
	
	public function getArray($name, $default)
	{
		return shopBuy1clickSettingsUtil::getArray($this->data, $name, $default, $this->is_allow_null);
	}
}
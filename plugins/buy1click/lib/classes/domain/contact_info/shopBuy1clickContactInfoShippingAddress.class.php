<?php


class shopBuy1clickContactInfoShippingAddress
{
	private $country;
	private $region;
	private $city;
	private $street;
	private $zip;
	private $custom_fields = array();
	
	public function getCountry()
	{
		return $this->country;
	}
	
	public function setCountry($country)
	{
		$this->country = $country;
	}
	
	public function getRegion()
	{
		return $this->region;
	}
	
	public function setRegion($region)
	{
		$this->region = $region;
	}
	
	public function getCity()
	{
		return $this->city;
	}
	
	public function setCity($city)
	{
		$this->city = $city;
	}
	
	public function getStreet()
	{
		return $this->street;
	}
	
	public function setStreet($street)
	{
		$this->street = $street;
	}
	
	public function getZip()
	{
		return $this->zip;
	}
	
	public function setZip($zip)
	{
		$this->zip = $zip;
	}
	
	public function getCustomFields()
	{
		return $this->custom_fields;
	}
	
	public function setCustomFields($custom_fields)
	{
		$this->custom_fields = $custom_fields;
	}
	
	public function toArray()
	{
		$array = array(
			'country' => $this->getCountry(),
			'region' => $this->getRegion(),
			'city' => $this->getCity(),
			'street' => $this->getStreet(),
			'zip' => $this->getZip(),
		);
		
		foreach ($this->getCustomFields() as $code => $value)
		{
			$array[$code] = $value;
		}
		
		return $array;
	}
}
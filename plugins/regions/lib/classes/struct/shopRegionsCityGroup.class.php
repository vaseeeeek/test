<?php

class shopRegionsCityGroup
{
	private $name;
	private $cities_assoc;
	private $custom_attributes;

	private $_sort_field;

	public function __construct($name, $custom_attributes = array(), $cities_assoc = array())
	{
		$this->name = $name;
		$this->cities_assoc = $cities_assoc;
		$this->custom_attributes = $custom_attributes;
	}

	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return array
	 */
	public function getCitiesAssoc()
	{
		return $this->cities_assoc;
	}

	public function getCustomAttributes()
	{
		return $this->custom_attributes;
	}

	public function getCustomAttribute($name, $default = null)
	{
		return ifset($this->custom_attributes[$name], $default);
	}

	/**
	 * @param array $city
	 */
	public function addCity($city)
	{
		$this->cities_assoc[$city['id']] = $city;
	}

	public function sortGroupByField($filed)
	{
		$this->_sort_field = $filed;
		uasort($this->cities_assoc, array($this, 'compareByField'));
	}

	public function addAttribute($name, $value)
	{
		$this->custom_attributes[$name] = $value;
	}

	private function compareByField($c1, $c2)
	{
		$sort_field = isset($this->_sort_field)
			? $this->_sort_field
			: 'id';

		if ($c1[$sort_field] == $c2[$sort_field])
		{
			return 0;
		}

		return wa_is_int($c1[$sort_field]) && wa_is_int($c2[$sort_field])
			? ($c1[$sort_field] < $c2[$sort_field] ? -1 : 1)
			: strnatcasecmp($c1[$sort_field], $c2[$sort_field]);
	}
}
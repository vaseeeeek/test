<?php

class shopDpPoints
{
	protected $search_params = array();
	protected $location;

	private $country_code;
	private $region_code;
	private $city_name;

	/** @var shopDpPointsIntegration */
	private $instance;
	private $id;
	private $service;

	/**
	 * @param array|shopDpService|int|string $id
	 * @param array $service
	 * @param string $return
	 * @param shopDpLocation|null $location
	 */
	public function __construct($id, $service, $return = 'actual', $location = null)
	{
		if ($id instanceof shopDpService || is_array($id))
		{
			$this->id = $id['id'];
			$this->service = $id['service'];

			$location = $return !== 'actual' ? $return : array();
			$return = $service;
		}
		else
		{
			$this->id = $id;
			$this->service = $service;
		}

		if ($location instanceof shopDpLocation)
		{
			$this->location = $location;
		}

		$this->setDefaultSearchParams();

		$this->initPoints($return);
	}

	public function findPoints($key = 'hash')
	{
		return isset($this->instance)
			? $this->instance->findPoints($key)
			: array();
	}

	public function getLocation()
	{
		if (!isset($this->location))
		{
			$this->location = new shopDpLocation('points');
		}

		return $this->location;
	}

	public function setOption($name, $value)
	{
		if (!empty($this->instance))
		{
			$this->instance->setOption($name, $value);
		}
	}

	private function initPoints($return = 'actual')
	{
		$service = ucfirst($this->service);
		$class = "shopDpPoints{$service}Integration";

		if (class_exists($class))
		{
			$this->instance = new $class($this->id, $this->service, $this->search_params, $return);
		}
		else
		{
			return false;
		}
	}

	private function setDefaultSearchParams()
	{
		$this->country_code = $this->getLocation()->getCountry();
		$this->region_code = $this->getLocation()->getRegion();
		$this->city_name = $this->getLocation()->getCity();

		if (!empty($this->country_code))
		{
			$this->search_params['country_code'] = $this->country_code;
		}

		if (!empty($this->region_code))
		{
			$this->search_params['region_code'] = $this->region_code;
		}

		if (!empty($this->city_name))
		{
			$this->search_params['city_name'] = $this->city_name;
		}
	}
}
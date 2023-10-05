<?php

abstract class shopDpMap
{
	protected $params;
	protected $net;

	final public function __construct($params = array())
	{
		$this->params = $params;
	}

	final protected function getParam($name)
	{
		if(array_key_exists($name, $this->params)) {
			return $this->params[$name];
		}

		return null;
	}

	final public function getNet($options = array(), $reload = false)
	{
		if(!isset($this->net) || $reload) {
			$options = array_merge(array('format' => ifset($options, 'format', waNet::FORMAT_XML), 'request_format' => ifset($options, 'request_format', waNet::FORMAT_RAW)), $options);
			$net = new waNet($options);

			if($reload)
				return $net;
			else
				$this->net = $net;
		}

		return $this->net;
	}

	final protected function getSearchCoordsString($params)
	{
		if(empty($params['country_name']) && !empty($params['country_code'])) {
			$params['country_name'] = shopDpPluginHelper::getCountryName($params['country_code']);
		}

		if(empty($params['region_name']) && !empty($params['region_code']) && !empty($params['country_code'])) {
			$params['region_name'] = shopDpPluginHelper::getRegionName($params['country_code'], $params['region_code']);
		}

		$string = ifset($params, 'country_name', '');
		$string .= ', ' . ifset($params, 'region_name', '');
		$string .= ', ' . ifset($params, 'city_name', '');
		$string .= ', ' . ifset($params, 'address', '');

		return $string;
	}

	abstract function getCoords($params);
}

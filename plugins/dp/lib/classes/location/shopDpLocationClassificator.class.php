<?php

class shopDpLocationClassificator
{
	const CACHE_KEY = 'shop_dp_location_classificator';
	const CACHE_ACTUALITY = 86400; // todo 7 days

	public $country;

	protected $storage_path;
	protected $region_fields = array(
		'aoid', 'regioncode', 'ifnsfl', 'terrifnsfl', 'ifnsul', 'terrifnsul', 'okato', 'oktmo', 'aoguid', 'code', 'plaincode'
	);
	protected $cache;

	public function __construct($country)
	{
		$this->country = $country;
	}

	protected function getCacheKey()
	{
		return self::CACHE_KEY . '_' . $this->country;
	}

	protected function getCacheActuality()
	{
		return self::CACHE_ACTUALITY;
	}

	protected function getCache()
	{
		if(!isset($this->cache)) {
			$this->cache = new waVarExportCache($this->getCacheKey(), $this->getCacheActuality());
		}

		return $this->cache;
	}

	protected function getStoragePath()
	{
		if(!isset($this->storage_path)) {
			$this->storage_path = wa('shop')->getAppPath('plugins/dp/lib/config/data/location/');
		}

		return $this->storage_path;
	}

	protected function getZipStoragePath()
	{
		$path = $this->getStoragePath() . "zip.{$this->country}.txt";

		return $path;
	}

	protected function getRegionsStoragePath()
	{
		$path = $this->getStoragePath() . "regions.{$this->country}.xml";

		return $path;
	}

	public function getCityZip($city)
	{
		$path = $this->getZipStoragePath();

		if(!file_exists($path)) {
			return null;
		}

		$handle = fopen($path, 'r');

		if($handle) {
			while(($line = fgets($handle)) !== false) {
				if(preg_match('/([0-9]+)\t' . preg_quote($city, '/') . '( ([0-9]+))?$/iu', $line, $matches)) {
					return $matches[1];

					break;
				}
			}

			fclose($handle);
		}

		return null;
	}

	protected function regionResult($data, $key = null)
	{
		if($key !== null) {
			$cache = $this->getCache()->get();

			if(isset($cache['region'][$key])) {
				$xml_data = $cache['region'][$key];
				$xml = new SimpleXMLElement($xml_data);

				return new shopDpLocationRegionClassificatorResult($xml);
			} else {
				if($data instanceof SimpleXMLElement) {
					if(!$cache) {
						$cache = array();
					}

					$cache['region'][$key] = $data->asXML();
					$this->getCache()->set($cache);
				}
			}
		}

		return new shopDpLocationRegionClassificatorResult($data);
	}

	protected function emptyRegionResult()
	{
		return $this->regionResult(null);
	}

	protected function getRegionByXPath($xpath)
	{
		$path = $this->getRegionsStoragePath();

		if(!file_exists($path)) {
			return $this->emptyRegionResult();
		}

		$resource = new SimpleXMLElement($path, 0, true);
		$element = $resource->xpath($xpath);

		if(is_array($element) && count($element) > 0) {
			return $this->regionResult($element[0], $xpath);
		}

		return $this->regionResult(null, $xpath);
	}

	public function getRegionData($region_code)
	{
		return $this->getRegionByXPath("region[node()='{$region_code}']");
	}

	public function getRegionByField($name, $value)
	{
		if(!in_array($name, $this->region_fields)) {
			return $this->emptyRegionResult();
		}

		return $this->getRegionByXPath("region[@{$name}='{$value}']");
	}

	public function getRegionByFias($value)
	{
		return $this->getRegionByField('aoguid', $value);
	}

	public function getRegionByOkato($value)
	{
		return $this->getRegionByField('okato', $value);
	}
}
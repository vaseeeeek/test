<?php

class shopDpClassificatorCdek
{
	protected $path;
	protected $regions_classification;
	protected $regions_classification_inverted;
	protected $countries_classification;
	protected $countries_classification_inverted;

	public function __construct()
	{
		$this->path = wa()->getAppPath('plugins/dp', 'shop') . '/lib/config/data/services/cdek/';

		$this->getRegionsClassification();
		$this->getCountriesClassification();
	}

	public function getCode($key, $value, $inverted = false)
	{
		if(wa_is_int($value)) {
			$value = intval($value);
		}

		switch($key) {
			case 'country_code':
				return $this->getCountryCode($value, $inverted);
				break;
			case 'region_code':
				return $this->getRegionCode($value, $inverted);
				break;
		}
	}

	public function getRegionsClassification()
	{
		if(!isset($this->regions_classification)) {
			$this->regions_classification = json_decode(file_get_contents($this->path . 'regions.json'), true);
			$this->regions_classification_inverted = array_flip($this->regions_classification);
		}
	}

	public function getRegionCode($value, $inverted = false)
	{
		if($inverted) {
			return ifset($this->regions_classification_inverted, $value, null);
		} else {
			return ifset($this->regions_classification, $value, null);
		}
	}

	public function getCountriesClassification()
	{
		if(!isset($this->countries_classification)) {
			$this->countries_classification = json_decode(file_get_contents($this->path . 'countries.json'), true);
			$this->countries_classification_inverted = array_flip($this->countries_classification);
		}
	}

	public function getCountryCode($value, $inverted = false)
	{
		if($inverted) {
			return ifset($this->countries_classification_inverted, $value, null);
		} else {
			return ifset($this->countries_classification, $value, null);
		}
	}
}
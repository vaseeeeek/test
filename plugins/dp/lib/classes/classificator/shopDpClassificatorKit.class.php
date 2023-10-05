<?php

class shopDpClassificatorKit
{
	protected $path;
	protected $countries_classification;

	public function __construct()
	{
		$this->path = wa()->getAppPath('plugins/dp', 'shop') . '/lib/config/data/services/kit/';

		$this->getCountriesClassification();
	}

	public function getCountriesClassification()
	{
		if(!isset($this->countries_classification)) {
			$this->countries_classification = json_decode(file_get_contents($this->path . 'countries.json'), true);
		}
	}

	public function getCountryCode($value)
	{
		return ifset($this->countries_classification, $value, null);
	}
}
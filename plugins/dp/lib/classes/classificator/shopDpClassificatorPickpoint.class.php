<?php

class shopDpClassificatorPickpoint
{
	protected $path;
	protected $regions_classification;

	public function __construct()
	{
		$this->path = wa()->getAppPath('plugins/dp', 'shop') . '/lib/config/data/services/pickpoint/';

		$this->getRegionsClassification();
	}

	public function getRegionsClassification()
	{
		if(!isset($this->regions_classification)) {
			$this->regions_classification = json_decode(file_get_contents($this->path . 'regions.json'), true);
		}
	}

	public function getRegionCode($region, $city = null)
	{
		$city = mb_strtolower($city);

		return ifset($this->regions_classification, "$region:$city", ifset($this->regions_classification, $region, $region));
	}
}
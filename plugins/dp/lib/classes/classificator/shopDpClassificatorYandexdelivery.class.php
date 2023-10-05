<?php

class shopDpClassificatorYandexdelivery
{
	protected $path;
	protected $services_classification;

	public function __construct()
	{
		$this->path = wa()->getAppPath('plugins/dp', 'shop') . '/lib/config/data/services/yandexdelivery/';

		$this->getServicesClassification();
	}

	public function getServicesClassification()
	{
		if(!isset($this->services_classification)) {
			$this->services_classification = json_decode(file_get_contents($this->path . 'services.json'), true);
		}
	}

	public function getServiceId($value)
	{
		return ifset($this->services_classification, $value, null);
	}
}
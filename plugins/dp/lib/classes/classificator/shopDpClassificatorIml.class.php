<?php

class shopDpClassificatorIml
{
	protected $path;
	protected $regions_classification_v1;
	protected $regions_classification_v2;

	public function __construct()
	{
		$this->path = wa()->getAppPath('plugins/dp', 'shop') . '/lib/config/data/services/iml/';

		$this->getRegionsClassificationV1();
		$this->getRegionsClassificationV2();
	}

	public function getRegionsClassificationV1()
	{
		if(!isset($this->regions_classification_v1)) {
			$this->regions_classification_v1 = json_decode(file_get_contents($this->path . 'regions.v1.json'), true);
		}
	}

	public function getRegionsClassificationV2()
	{
		if(!isset($this->regions_classification_v2)) {
			$this->regions_classification_v2 = json_decode(file_get_contents($this->path . 'regions.v2.json'), true);
		}
	}

	public function getRegionCode($delivery)
	{
		if(property_exists($delivery, 'FormKLADRCode') && (string) $delivery->FormKLADRCode) {
			$kladr_code = (string) $delivery->FormKLADRCode;
			return substr($kladr_code, 0, 2);
		} elseif(property_exists($delivery, 'FormRegion') && (string) $delivery->FormRegion) { // V1
			$form_region = (string) $delivery->FormRegion;
			return ifset($this->regions_classification_v1, trim($form_region), null);
		} elseif(property_exists($delivery, 'RegionCode')) { // V2
			$region_code = (string) $delivery->RegionCode;
			return ifset($this->regions_classification_v2, trim($region_code), null);
		} else {
			return false;
		}
	}

	public function getCityName($delivery)
	{
		if(property_exists($delivery, 'FormCity') && (string) $delivery->FormCity) {
			$city_name = (string) $delivery->FormCity;
			$city_name = trim(preg_replace('/г\./', '', $city_name));
			return $city_name;
		} elseif(property_exists($delivery, 'RegionCode') && (string) $delivery->RegionCode) {
			$city_name = (string) $delivery->RegionCode;

			switch($city_name) {
				case 'ЖЕЛЕЗНОГ-К КУРСК ОБЛ':
					$city_name = 'Железногорск';
					break;
			}

			$split_space = explode(' ', $city_name);
			foreach($split_space as &$part) {
				$part = mb_strtoupper(mb_substr($part, 0, 1)) . mb_strtolower(mb_substr($part, 1));
				$split_dash_parts = explode('-', $part);
				foreach($split_dash_parts as &$split_dash_part) {
					$split_dash_part = mb_strtoupper(mb_substr($split_dash_part, 0, 1)) . mb_strtolower(mb_substr($split_dash_part, 1));
				}
				$part = implode('-', $split_dash_parts);
			}
			$city_name = implode(' ', $split_space);

			return $city_name;
		} else {
			return false;
		}
	}

	public function getAddress($delivery)
	{
		$address = (string) $delivery->Address;
		$address = trim($address);

		$address = preg_replace('/^([а-я]+ (область|обл\.?|край),?\s?)?г\.\s*([а-я-\s]+)\s*,\s*/iu', '', $address);
		$address = preg_replace('/^г\.\s*([а-я-\s]+)\s*ул\.?\s*/iu', 'ул. ', $address);

		$address = trim($address);

		return $address;
	}
}
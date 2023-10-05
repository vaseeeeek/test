<?php

class shopDpClassificatorPek
{
	protected $path;
	protected $cities_starting_with = array('Москва', 'Санкт-Петербург', 'Казань', 'Иваново', 'Екатеринбург', 'Нижний Новгород', 'Новосибирск', 'Пермь', 'Ульяновск', 'Ростов-на-Дону');
	protected $regions_classification;

	public function __construct()
	{
		$this->path = wa()->getAppPath('plugins/dp', 'shop') . '/lib/config/data/services/pek/';

		$this->getRegionsClassification();
	}

	public function getRegionsClassification()
	{
		if(!isset($this->regions_classification)) {
			$this->regions_classification = json_decode(file_get_contents($this->path . 'regions.json'), true);
		}
	}

	public function getRegionCode($country, $warehouse)
	{
		$value = preg_replace('/^Фактический адрес\s*[0-9]+,/', '', $warehouse['addressDivision']);
		$value = preg_replace('/^[0-9]+,/', '', trim($value));
		$value = trim(mb_substr($value, 0, mb_strpos($value, ',')));

		return ifset($this->regions_classification[$country][$value]);
	}

	public function getCityName($warehouse)
	{
		$value = trim($warehouse['name']);
		$city_name = null;

		foreach($this->cities_starting_with as $city) {
			if(mb_substr($value, 0, mb_strlen($city)) === $city) {
				$city_name = $city;
				break;
			}
		}

		if(!isset($city_name)) {
			$city_name = preg_replace('/([0-9]+)/', '', $value);
			$city_name = preg_replace('/(Север|Юг|Восток|Запад|РИО|ТекстильПрофи|)/', '', $city_name);
			$city_name = trim($city_name);
		}

		return $city_name;
	}

	public function getAddress($warehouse)
	{
		$address = null;
		$value = trim($warehouse['address']);

		if(mb_strpos($value, 'ул.') !== false) {
			if(preg_match('/ул\. ?([0-9а-я\-]+)/iu', $value)) {
				$address = mb_substr($value, mb_strpos($value, 'ул.'));
			} else {
				$address = $value;
			}
		} elseif(preg_match('/ул ([а-я]+)/iu', $value)) {
			$address = preg_replace('/(.*)ул ([а-я]+)/iu', 'ул. $2', $value);
		} else {
			$value = preg_replace('/^([0-9]+),/', '', $value);
			$value = trim($value);

			while(preg_match('/^([а-я\-\s]+) (обл|область|край|район),/iu', $value)) {
				$value = preg_replace('/^([а-я\-\s]+) (обл|область|край|район),/iu', '', $value);
				$value = trim($value);
			}

			if(preg_match('/^г( |\. ?)([а-я\-\s]+),/iu', $value, $matches)) {
				$address = preg_replace('/^г( |\. ?)([а-я\-\s]+),/iu', '', $value);
				$address = trim($address);
			} else {
				$address = $value;
			}

			$address = preg_replace('/^(Екатеринбург|Владивосток),/', '', $address);
			$address = trim($address);
		}

		$address = preg_replace('/ул\.([^ ])/iu', 'ул. $1', $address);
		if(preg_match('/^(ул\. )?([а-я]+) ([0-9]+)$/iu', $address)) {
			$address = preg_replace('/^(ул\. )?([а-я]+) ([0-9]+)$/iu', 'ул. $2, д. $3', $address);
		}
		$address = preg_replace_callback('/ул\. ([а-я])/u', wa_lambda('$matches', 'return "ул. " . mb_strtoupper($matches[1]);'), $address);

		$address = trim($address);

		return $address;
	}
}
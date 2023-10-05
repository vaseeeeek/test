<?php

class shopDpClassificatorEasyway
{
	const DISTRICT_REGEXP = '/[^,]*(область|Россия|город|район|микрорайон)[^,]*,/';

	protected $location_classificator;

	protected function getLocationClassificator()
	{
		if(!isset($this->location_classificator)) {
			$this->location_classificator = new shopDpLocationClassificator('rus');
		}

		return $this->location_classificator;
	}

	public function getRegionCode($element)
	{
		if(!array_key_exists('fiasRegionId', $element)) {
			throw new waException('Не найдено поле fiasRegionId');
		}

		$fias = $element['fiasRegionId'];

		$result = $this->getLocationClassificator()->getRegionByFias($fias);

		if(!$result->isCorrect()) {
			throw new waException("Регион по fias \"{$fias}\" не найден");
		}

		return $result->getCode();
	}

	public function getAddress($element)
	{
		if(!array_key_exists('address', $element)) {
			throw new waException('Не найдено поле address');
		}

		if(!array_key_exists('city', $element)) {
			throw new waException('Не найдено поле city');
		}

		$city = $element['city'];
		$source_address = $element['address'];

		$address_city = "{$city}, ";
		$address_pos = mb_strpos($source_address, $address_city);

		$address = $source_address;
		if($address_pos !== false) {
			$address = mb_substr($source_address, $address_pos + mb_strlen($address_city));
		}

		while(preg_match(self::DISTRICT_REGEXP, $address)) {
			$address = preg_replace(self::DISTRICT_REGEXP, '', $address);
		}

		$address = trim($address);

		return $address;
	}
}
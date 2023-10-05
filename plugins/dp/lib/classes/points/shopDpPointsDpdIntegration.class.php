<?php

/**
 * Список пунктов выдачи службы DPD
 */

class shopDpPointsDpdIntegration extends shopDpPointsIntegration implements shopDpPointsIntegrationInterface
{
	protected $params = array(
		'style' => 2,
		'actuality' => 604800
	);

	public $days = array(
		null, 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'
	);
	public $period_regexp = '/^([0-9]{2}):([0-9]{2}) - ([0-9]{2}):([0-9]{2})$/';

	public $url = 'http://ws.dpd.ru/services/geography2?wsdl';
	private $client;
	private $client_number;
	private $client_code;

	private function getClient()
	{
		if(!isset($this->client))
			$this->client = new SoapClient($this->url);

		return $this->client;
	}

	private function prepareModule()
	{
		if(!extension_loaded('soap') || !class_exists('SoapClient')) {
			throw new waException('Для работы интеграции с DPD необходим клиент SOAP');
		}
	}

	protected function prepareQuery()
	{
		$search_params = $this->getSearchParams();

		$region_code = $search_params['region_code'];
		$city_name = $search_params['city_name'];

		$query_params = array(
			'request' => array(
				'auth' => array(
					'clientNumber' => $this->getClientNumber(),
					'clientKey' => $this->getClientKey()
				),
				'countryCode' => 'RU',
				'regionCode' => $region_code,
				'cityName' => $city_name
			)
		);

		$this->setQueryParams($query_params);
	}

	protected function queryAll($client)
	{
		$parcel_result = $this->query($client, 'parcel');
		$terminals_result = $this->query($client, 'terminals');

		return array('parcel' => $parcel_result, 'terminals' => $terminals_result);
	}

	protected function query($client, $type = 'parcel')
	{
		if($type === 'parcel') {
			$method = 'getParcelShops';
		} elseif($type === 'terminals') {
			$method = 'getTerminalsSelfDelivery2';
		} else {
			throw new waException('Неизвестный метод ' . $type);
		}

		try {
			if($type === 'terminals') {
				$result = $client->$method(array(
					'auth' => array(
						'clientNumber' => $this->getClientNumber(),
						'clientKey' => $this->getClientKey()
					)
				)); // todo переделать придется, потому что эта штука получает терминалы по всей стране, а нам такого не надо
			} else {
				$result = $client->$method($this->getQueryParams());
			}
		} catch(SoapFault $e) {
			throw new waException($e);
		}

		if(!$result || !$result instanceof stdClass) {
			throw new waException('Возникла ошибка при попытке запроса доступных пунктов выдачи DPD');
		}

		return $result;
	}

	private function getClientNumber()
	{
		return $this->client_number;
	}

	private function setClientNumber($client_number)
	{
		$this->client_number = $client_number;
	}

	private function getClientKey()
	{
		return $this->client_code;
	}

	private function setClientKey($client_code)
	{
		$this->client_code = $client_code;
	}

	public function processPointData($type, &$point, $data)
	{
		$point['country_code'] = 'rus';

		if($type === 'parcel') {
			$point['code'] = $data->code;
		} else {
			$point['code'] = "t{$data->terminalCode}";
		}

		$address = $data->address;
		$point['address'] = '';

		$point['region_code'] .= $address->regionCode;
		$point['city_name'] .= $address->cityName;
		if(property_exists($address, 'street'))
			$point['address'] .= $address->street;
		if(property_exists($address, 'streetAbbr'))
			$point['address'] .= " $address->streetAbbr";
		if(property_exists($address, 'houseNo'))
			$point['address'] .= ", д. $address->houseNo";
		if(property_exists($address, 'building'))
			$point['address'] .= ", корп. $address->building";
		if(property_exists($address, 'structure'))
			$point['address'] .= ", стр. $address->structure";

		if(!$point['address'])
			return false;

		if(property_exists($address, 'descript'))
			$point['address_comment'] = trim($address->descript);

		$point['coord_y'] = $data->geoCoordinates->latitude;
		$point['coord_x'] = $data->geoCoordinates->longitude;

		if($type === 'parcel') {
			if(property_exists($data, 'parcelShopType')) {
				$point['type'] = $data->parcelShopType === 'П' ? 'postamat' : 'point';
			}
		} else {
			$point['type'] = 'terminal';
		}

		$timetable = null;
		$cashless = false;

		$schedules = $data->schedule;
		if($schedules instanceof stdClass)
			$schedules = array($schedules);

		foreach($schedules as $schedule) {
			if(property_exists($schedule, 'operation'))
				switch($schedule->operation) {
					case 'SelfDelivery':
						$timetable = $schedule->timetable instanceof stdClass ? array($schedule->timetable) : $schedule->timetable;
						break;
					case 'PaymentByBankCard':
						$cashless = true;
						break;
				}
		}

		$point['cashless'] = $cashless;

		if($timetable) {
			$worktime = array();

			foreach($timetable as $row) {
				if(property_exists($row, 'weekDays') && property_exists($row, 'workTime')) {
					$days = explode(',', $row->weekDays);

					if(preg_match($this->period_regexp, trim($row->workTime))) {
						$period = preg_replace($this->period_regexp, '$1:$2/$3:$4', trim($row->workTime));

						foreach($days as $day) {
							$day_index = array_search($day, $this->days);

							if($day_index !== false) {
								$worktime[$day_index] = array(
									'day' => $day_index,
									'period' => $period
								);
							}
						}
					}
				}
			}

			$point['worktime'] = $worktime;
		}

		return true;
	}

	public function takePoints($key = null)
	{
		try {
			$this->prepareModule();
		} catch(waException $e) {
			$this->log($e);
			return false;
		}

		if($this->getSearchParams('country_code') !== 'rus')
			return false;

		$settings = $this->getPlugin()->getSettings('shipping_methods');

		$client_number = ifset($settings, $this->id, 'settings', 'dpd', 'number', null);
		$client_key = ifset($settings, $this->id, 'settings', 'dpd', 'key', null);

		if(!$client_number || !$client_key)
			return false;

		$this->setClientNumber($client_number);
		$this->setClientKey($client_key);

		try {
			$client = $this->getClient();
		} catch(SoapFault $e) {
			$this->log($e->getMessage());

			return false;
		}

		$this->prepareQuery();

		try {
			$all_result = $this->queryAll($client);
		} catch(waException $e) {
			$this->log($e->getMessage());

			return false;
		}

		$points = array();

		foreach($all_result as $type => $result) {
			if($type === 'parcel') {
				$property = 'parcelShop';
			} elseif($type === 'terminals') {
				$property = 'terminal';
			}

			if(property_exists($result, 'return') && property_exists($result->return, $property)) {
				$source_points = $result->return->$property;

				foreach($source_points as $source_point) {
					$point = array();

					if($this->processPointData($type, $point, $source_point))
						$points[] = $point;
				}
			}
		}

		return $points;
	}
}
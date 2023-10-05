<?php

/**
 * Список пунктов выдачи службы ПЭК
 */

class shopDpPointsPekIntegration extends shopDpPointsIntegration implements shopDpPointsIntegrationInterface
{
	protected $params = array(
		'style' => 0,
		'actuality' => 604800
	);

	public $url = 'https://kabinet.pecom.ru/api/v1/branches/all';
	private $login;
	private $key;
	protected $classificator;

	protected function getClassificator()
	{
		if(!isset($this->classificator)) {
			$this->classificator = new shopDpClassificatorPek();
		}

		return $this->classificator;
	}

	protected function query()
	{
		$result = $this->getNet(array(
			'timeout' => 25,
			'format' => waNet::FORMAT_JSON,
			'request_format' => waNet::FORMAT_JSON,
			'authorization' => true,
			'login' => $this->getLogin(),
			'password' => $this->getKey()
		), array(
			'Content-Type' => 'application/json;charset=utf-8'
		))->query($this->url);

		return $result;
	}

	private function getLogin()
	{
		return $this->login;
	}

	private function setLogin($login)
	{
		$this->login = $login;
	}

	private function getKey()
	{
		return $this->key;
	}

	private function setKey($key)
	{
		$this->key = $key;
	}

	public function getCountry($branch)
	{
		if(mb_substr($branch['address'], 0, mb_strlen('Республика Казахстан')) === 'Республика Казахстан') {
			return 'kaz';
		} else {
			return 'rus';
		}
	}

	public function fixCountryByWarehouse($warehouse)
	{
		if(!empty($warehouse['addressDivision'])) {
			if(mb_strpos($warehouse['addressDivision'], 'Республика Казахстан') !== false) {
				return 'kaz';
			} else {
				return 'rus';
			}
		} else {
			return 'rus';
		}
	}

	public function parseWorktime($source_worktime)
	{
		$worktime = array();

		foreach($source_worktime as $day_worktime) {
			if(!empty($day_worktime['dayOfWeek']) && isset($day_worktime['workFrom']) && isset($day_worktime['workTo'])) {
				if(empty($day_worktime['workFrom']))
					$day_worktime['workFrom'] = '00:00';
				if($day_worktime['workTo'] == '23:59')
					$day_worktime['workTo'] = '00:00';

				$worktime[$day_worktime['dayOfWeek']] = array(
					'day' => $day_worktime['dayOfWeek'],
					'period' => "{$day_worktime['workFrom']}/{$day_worktime['workTo']}"
				);
			}
		}

		return $worktime;
	}

	public function processPointData(&$point, $data)
	{
		foreach($data as $source_key => $source_value) {
			$key = null;
			$value = null;

			switch($source_key) {
				case 'telephone':
					$key = 'phone';
					break;
				case 'email':
					$key = 'email';
					break;
				case 'id':
					$key = 'code';
					break;
				case 'coordinates':
					$key = 'geo';
					$value = explode(',', $source_value);
					break;
				case 'timeOfWork':
					$key = 'worktime';
					$value = $this->parseWorktime($source_value);
					break;
			}

			if(!empty($key)) {
				switch($key) {
					case 'geo':
						$point['coord_y'] = $value[0];
						$point['coord_x'] = $value[1];
						break;
					case 'worktime':
						$point[$key] = $value;
						break;
					default:
						$point[$key] = (string) ifset($value, $source_value);
						break;
				}
			}
		}
	}

	public function takePoints($key = null)
	{
		if(!in_array($this->getSearchParams('country_code'), array('rus', 'kaz')))
			return false;

		$settings = $this->getPlugin()->getSettings('shipping_methods');

		$login = ifset($settings, $this->id, 'settings', 'pek', 'login', null);
		$key = ifset($settings, $this->id, 'settings', 'pek', 'key', null);

		if(!$login || !$key)
			return false;

		$this->setLogin($login);
		$this->setKey($key);

		try {
			$result = $this->query();
		} catch(waException $e) {
			$this->log($e->getMessage());

			return false;
		}

		if(array_key_exists('branches', $result)) {
			$points = array();

			$branches = $result['branches'];

			foreach($branches as $branch) {
				$country_code = $this->getCountry($branch);

				if(!empty($branch['divisions']))  {
					foreach($branch['divisions'] as $division) {
						if(!empty($division['warehouses'])) {
							foreach($division['warehouses'] as $warehouse) {
								/*if(!empty($warehouse['isAcceptanceOnly']) || empty($warehouse['isWarehouseGivesFreights'])) {
									continue;
								}*/

								if($country_code === 'rus') {
									$country_code = $this->fixCountryByWarehouse($warehouse);
								}

								$region_code = $this->getClassificator()->getRegionCode($country_code, $warehouse);
								$city_name = $this->getClassificator()->getCityName($warehouse);
								$address = $this->getClassificator()->getAddress($warehouse);

								$point = array(
									'country_code' => $country_code,
									'region_code' => $region_code,
									'city_name' => $city_name,
									'address' => $address,
									'cashless' => true
								);

								$this->processPointData($point, $warehouse);

								$points[] = $point;
							}
						}
					}
				}
			}

			return $points;
		}

		return false;
	}
}
<?php

class shopDpPointsBoxberryIntegration extends shopDpPointsIntegration implements shopDpPointsIntegrationInterface
{
	protected $classificator;
	protected $city_code;
	protected $params = array(
		'style' => 2,
		'actuality' => 604800
	);

	public $days = array(
		null, 'пн', 'вт', 'ср', 'чт', 'пт', 'сб', 'вс'
	);

	public $url = 'https://api.boxberry.ru/json.php';
	private $token;

	private function getToken()
	{
		return $this->token;
	}

	private function setToken($token)
	{
		$this->token = $token;
	}

	protected function prepareQuery()
	{
		if($this->city_code) {
			$query_params = array(
				'token' => $this->getToken(),
				'method' => 'ListPoints',
				'prepaid' => 1,
				'CityCode' => $this->city_code
			);

			$this->setQueryParams($query_params);
		}
	}

	protected function getClassificator()
	{
		if(!isset($this->classificator)) {
			$token = $this->getToken();
			$this->classificator = new shopDpClassificatorBoxberry($token, $this->url);
		}

		return $this->classificator;
	}

	protected function getCityCode()
	{
		if(!isset($this->city_code)) {
			$search_params = $this->getSearchParams();

			$this->city_code = $this->getClassificator()->getCityCode($search_params);
		}

		return $this->city_code;
	}

	protected function query()
	{
		$result = $this->getNet(array(
			'format' => waNet::FORMAT_JSON,
			'request_format' => waNet::FORMAT_JSON
		))->query($this->url, $this->getQueryParams(), waNet::METHOD_GET);

		return $result;
	}

	public function parseWorktime($source_worktime)
	{
		$periods = explode(',', $source_worktime);
		$worktime = array();

		foreach($periods as $period) {
			$period = trim($period);
			if(!empty($period)) {
				if(preg_match('/^(пн|вт|ср|чт|пт|сб|вс)(-(пн|вт|ср|чт|пт|сб|вс))?: ?(([01]?[0-9]|2[0-3]).([0-5][0-9]))-(([01]?[0-9]|2[0-3]).([0-5][0-9]))$/iu', $period, $matches)) {
					$day = array_search(mb_strtolower($matches[1]), $this->days);
					$time = "{$matches[5]}:{$matches[6]}/{$matches[8]}:{$matches[9]}";
					if($day) {
						if($matches[3]) { // Интервал
							$first_day = $day;
							$second_day = array_search(mb_strtolower($matches[3]), $this->days);

							for($i = $first_day; $i <= $second_day; $i++)
								$worktime[$i] = array(
									'day' => $i,
									'period' => $time
								);
						} else {
							$worktime[$day] = array(
								'day' => $day,
								'period' => $time
							);
						}
					}
				};
			}
		}

		return $worktime;
	}

	public function processPointData(&$point, $data)
	{
		$point['country_code'] = ifempty($this->search_params, 'country_code', 'rus');
		$point['region_code'] = ifempty($this->search_params, 'region_code', null);
		$point['city_name'] = ifempty($this->search_params, 'city_name', null);

		foreach($data as $source_key => $source_value) {
			$key = null;
			$value = null;

			switch($source_key) {
				case 'Code':
					$key = 'code';
					break;
				case 'AddressReduce':
					$key = 'address';
					break;
				case 'CityCode':
					$key = 'city_code';
					break;
				case 'TripDescription':
					$key = 'address_comment';
					break;
				case 'Metro':
					$key = 'metro_station';
					break;
				case 'Acquiring':
					$key = 'cashless';
					$value = !empty($source_value) && mb_strtolower($value) === 'yes';
					break;
				case 'Phone':
					$key = 'phone';
					break;
				case 'WorkSchedule':
					$key = 'worktime';
					$value = $this->parseWorktime($source_value);
					break;
				case 'GPS':
					$key = 'geo';
					$value = explode(',', $source_value);
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
		$settings = $this->getPlugin()->getSettings('shipping_methods');

		$token = ifset($settings, $this->id, 'settings', 'boxberry', 'token', null);
		$url = ifset($settings, $this->id, 'settings', 'boxberry', 'url', null);

		if (!$token)
		{
			return false;
		}

		if ($url)
		{
			$this->url = $url;
		}

		$this->setToken($token);

		if (!$this->getCityCode())
		{
			return false;
		}

		$this->prepareQuery();


		$query_result = $this->getQueryResult();
		if (!$query_result)
		{
			return false;
		}


		$points = array();
		$source_points = $query_result;

		foreach ($source_points as $source_point)
		{
			$point = array();

			$this->processPointData($point, $source_point);

			if (!empty($point['coord_y']) && !empty($point['coord_x']))
			{
				$points[] = $point;
			}
		}

		return $points;
	}

	private function getQueryResult()
	{
		try
		{
			$query_result = $this->query();
		}
		catch (Exception $e)
		{
			$this->log($e->getMessage());

			return false;
		}

		if (!is_array($query_result) || count($query_result) === 0 || ifset($query_result, 0, 'data', null))
		{
			return false;
		}

		return $query_result;
	}
}
<?php

/**
 * Список пунктов выдачи службы IML
 */

class shopDpPointsImlIntegration extends shopDpPointsIntegration implements shopDpPointsIntegrationInterface
{
	protected $params = array(
		'style' => 0,
		'actuality' => 604800
	); // todo check for double instances

	public $period_regexp = array(
		'/(пн|вт|ср|чт|пт|сб|вс)\s*(-\s*(пн|вт|ср|чт|пт|сб|вс))?:?\s+(?:с\s+)?([0-9]{1,2}):([0-9]{2})\s*(?:до|-)\s*([0-9]{1,2}):([0-9]{2})/',
		'/(пн|вт|ср|чт|пт|сб|вс)\s*(-\s*(пн|вт|ср|чт|пт|сб|вс))?:?\s+(?:с\s+)?([0-9]{1,2})\s*(?:до|-)\s*([0-9]{1,2})/',
		'/^(пн|вт|ср|чт|пт|сб|вс)\s*(-\s*(пн|вт|ср|чт|пт|сб|вс))?$/',
	);
	public $days = array(
		null, 'пн', 'вт', 'ср', 'чт', 'пт', 'сб', 'вс'
	);

	protected $classificator;
	public $url = 'http://list.iml.ru/sd';

	protected function getClassificator()
	{
		if(!isset($this->classificator)) {
			$this->classificator = new shopDpClassificatorIml();
		}

		return $this->classificator;
	}

	protected function query()
	{
		$result = $this->getNet(array(
			'format' => 'xml',
			'request_format' => 'xml'
		), array('accept' => 'application/xml'))->query($this->url);

		return $result;
	}

	public function parseWorktime($source_worktime)
	{
		$worktime = array();

		if(mb_strpos($source_worktime, ';'))
			$periods = explode(';', $source_worktime);
		else
			$periods = explode(',', $source_worktime);

		$last_not_filled_days = null;

		foreach($periods as $period) {
			$period = mb_strtolower(trim($period));

			$matches = array();

			foreach($this->period_regexp as $i => $regexp) {
				if(preg_match($this->period_regexp[$i], $period, $matches[$i])) {
					switch($i) {
						case 0:
						case 1:
							if($i == 1) {
								$from_h = str_pad($matches[$i][4], 2, '0', STR_PAD_LEFT);
								$to_h = str_pad($matches[$i][5], 2, '0', STR_PAD_LEFT);
								$time = "{$from_h}:00/{$to_h}:00";
							} elseif($i == 0) {
								$from_h = str_pad($matches[$i][4], 2, '0', STR_PAD_LEFT);
								$to_h = str_pad($matches[$i][6], 2, '0', STR_PAD_LEFT);
								$time = "{$from_h}:{$matches[$i][5]}/{$to_h}:{$matches[$i][7]}";
							}

							$first_day = array_search($matches[$i][1], $this->days);

							if(!empty($last_not_filled_days)) {
								foreach($last_not_filled_days as $not_filled_day)
									$worktime[$not_filled_day] = array(
										'day' => $not_filled_day,
										'period' => $time
									);
							}

							if(empty($matches[$i][3]) && $first_day) {
								$worktime[$first_day] = array(
									'day' => $first_day,
									'period' => $time
								);
							} elseif($first_day && $matches[$i][3]) {
								$second_day = array_search($matches[$i][3], $this->days);

								if($second_day) {
									for($n = $first_day; $n <= $second_day; $n++)
										$worktime[$n] = array(
											'day' => $n,
											'period' => $time
										);
								}
							}
							break;
						case 2:
							$first_day = array_search($matches[$i][1], $this->days);

							if(empty($matches[$i][3]) && $first_day)
								$last_not_filled_days[] = $first_day;
							elseif($first_day && $matches[$i][3]) {
								$second_day = array_search($matches[$i][3], $this->days);

								for($n = $first_day; $n <= $second_day; $n++)
									$last_not_filled_days[] = $n;
							}
							break;
					}

					break;
				}
			}
		}

		return $worktime;
	}

	public function processNodes(&$point, $nodes)
	{
		foreach($nodes as $source_key => $source_value) {
			$key = null;
			$value = null;

			switch($source_key) {
				case 'Code':
					$key = 'code';
					break;
				case 'EMail':
					$key = 'email';
					break;
				case 'Phone':
					$key = 'phone';
					break;
				case 'HowToGet':
					$key = 'address_comment';
					break;
				case 'FittingRoom':
					$key = 'dressing_room';
					$value = $source_value != '0' ? 1 : 0;
					break;
				case 'Latitude':
					$key = 'coord_y';
					break;
				case 'Longitude':
					$key = 'coord_x';
					break;
				case 'PaymentCard':
					$key = 'cashless';
					$value = 1;
					break;
				case 'WorkMode':
					$key = 'worktime';
					$value = $this->parseWorktime($source_value);
					break;
			}

			if(!empty($key)) {
				switch($key) {
					case 'worktime':
						$point[$key] = $value;
						break;
					default:
						$point[$key] = (string) ifset($value, $source_value);
						break;
				}
			}
		}

		return true;
	}

	public function takePoints($key = null)
	{
		if(!in_array($this->getSearchParams('country_code'), array('rus')))
			return false;

		try {
			$result = $this->query();
		} catch(waException $e) {
			$this->log($e->getMessage());

			return false;
		}

		$points = array();

		if($result instanceof SimpleXMLElement) {
			$result->registerXPathNamespace('models', 'http://schemas.datacontract.org/2004/07/ReferenceBook.Models');

			$deliveries = $result->xpath('/models:ArrayOfSelfDelivery/models:SelfDelivery');

			foreach($deliveries as $delivery) {
				$region_code = $this->getClassificator()->getRegionCode($delivery);
				$city_name = $this->getClassificator()->getCityName($delivery);
				$address = $this->getClassificator()->getAddress($delivery);

				$point = array(
					'country_code' => 'rus',
					'region_code' => $region_code,
					'city_name' => $city_name,
					'address' => $address
				);

				if($this->processNodes($point, $delivery->children()))
					$points[] = $point;
			}
		} else {
			return false;
		}

		return $points;
	}
}
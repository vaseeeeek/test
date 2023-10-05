<?php

class shopDpPointsYandexdeliveryIntegration extends shopDpPointsIntegration implements shopDpPointsIntegrationInterface
{
	protected $params = array(
		'style' => 2,
		'actuality' => 604800
	);

	public $days = array(
		null, 'ПН', 'ВТ', 'СР', 'ЧТ', 'ПТ', 'СБ', 'ВС'
	);

	protected $calculate_instance;
	protected $rates;
	protected $classificator;
	protected $service_cods = array();

	protected function getClassificator()
	{
		if(!isset($this->classificator)) {
			$this->classificator = new shopDpClassificatorYandexdelivery();
		}

		return $this->classificator;
	}

	private function calculate()
	{
		$this->calculate_instance = new shopDpShippingCalculate();
		$this->rates = $this->calculate_instance->calculate($this->id, 'rates');
	}

	public function parseWorktimeArrayWeekdays($weekdays)
	{
		$worktime = array();

		foreach($weekdays as $n => $weekday) {
			$day = $n + 1;

			$is_workday = $weekday['type'] === 'workday';
			if(!$is_workday) {
				continue;
			}

			$start = DateTime::createFromFormat('Y-m-d H:i:s', $weekday['start_work']);
			$end = DateTime::createFromFormat('Y-m-d H:i:s', $weekday['end_work']);
			if(!$start || !$end) {
				continue;
			}

			$start_str = $start->format('H:i');
			$end_str = $end->format('H:i');

			$worktime[$day] = array(
				'day' => $day,
				'period' => "{$start_str}/{$end_str}"
			);
		}

		return $worktime;
	}

	public function parseWorktimeString($source_worktime)
	{
		$periods = explode('_', $source_worktime);
		$worktime = array();

		foreach($periods as $period) {
			if(!empty($period)) {
				if(preg_match('/^(Пн|Вт|Ср|Чт|Пт|Сб|Вс)( - (Пн|Вт|Ср|Чт|Пт|Сб|Вс))?: (([01]?[0-9]|2[0-3]):[0-5][0-9]) - (([01]?[0-9]|2[0-3]):[0-5][0-9])$/', $period, $matches)) {
					$day = array_search(mb_strtoupper($matches[1]), $this->days);
					$time = "{$matches[4]}/{$matches[6]}";

					if($day) {
						if($matches[3]) { // Интервал
							$first_day = $day;
							$second_day = array_search(mb_strtoupper($matches[3]), $this->days);

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

	public function processPointData(&$point, $rate)
	{
		if(!empty($rate['custom_data']['pickup']) && $rate['custom_data']['type'] == 'pickup') {
			$id = $rate['id'];
			$service_code = substr($id, 0, strpos($id, ':'));
			if(empty($this->service_cods[$service_code]))
				$this->service_cods[$service_code] = $rate['name'];

			$point['country_code'] = ifempty($this->search_params, 'country_code', 'rus');
			$point['region_code'] = ifempty($this->search_params, 'region_code', null);
			$point['city_name'] = ifempty($this->search_params, 'city_name', null);
			$point['fixed_service'] = $this->getClassificator()->getServiceId($service_code);

			if(!$point['fixed_service'])
				return false;

			$point['hash'] = ifset($rate, 'custom_data', 'pickup', 'id', null);
			$point['name'] = ifset($rate, 'name', null);

			if(!empty($rate['custom_data']['description']))
				$point['full_address'] = $rate['custom_data']['description'];

			foreach($rate['custom_data']['pickup'] as $source_key => $source_value) {
				$key = null;
				$value = null;

				switch($source_key) {
					case 'id':
						$key = 'code';
						break;
					case 'lat':
						$key = 'coord_y';
						break;
					case 'lng':
						$key = 'coord_x';
						break;
					case 'description':
						preg_match('/^(([0-9]{6}, ?)?(([а-яё -]+), ?))(.*)$/iu', $source_value, $matches);
						if($matches && !empty($matches[5])) {
							$key = 'address';

							if($matches[4] !== $point['city_name']) {
								$point['city_name'] = $matches[4];
							}

							$value = $matches[5];
						} else {
							throw new Exception('unknown address type');
						}
						break;
					case 'comment':
						$key = 'address_comment';
						break;
					case 'payment':
						if(is_string($source_value)) {
							$payment = explode(', ', $source_value);
						} else {
							$payment = $source_value;
						}

						if(is_array($payment) && in_array('Оплата картой', $payment)) {
							$key = 'cashless';
							$value = true;
						}
						break;
					case 'schedule':
						if(is_string($source_value)) {
							$schedule = preg_replace('#<[^>]+>#', '_', $source_value);

							if($schedule) {
								$key = 'worktime';
								$value = $this->parseWorktimeString($schedule);
							}
						} elseif(array_key_exists('weekdays', $source_value)) {
							$key = 'worktime';
							$value = $this->parseWorktimeArrayWeekdays($source_value['weekdays']);
						}
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

		return false;
	}

	public function takePoints($key = null)
	{
		$this->service_cods = array();
		$this->service_schedules = array();
		$this->calculate();

		$points = array();
		foreach($this->rates as $rate) {
			$point = array();

			try {
				if($this->processPointData($point, $rate)) {
					if($point)
						$points[] = $point;
				}
			} catch(Exception $e) { }
		}

		return $points;
	}
}

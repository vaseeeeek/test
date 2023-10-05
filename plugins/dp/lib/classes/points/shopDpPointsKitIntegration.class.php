<?php

/**
 * Список пунктов выдачи службы КИТ
 */

class shopDpPointsKitIntegration extends shopDpPointsIntegration implements shopDpPointsIntegrationInterface
{
	protected $params = array(
		'style' => 0,
		'actuality' => 604800
	);

	public $days = array(
		null, 'ПН', 'ВТ', 'СР', 'ЧТ', 'ПТ', 'СБ', 'ВС'
	);

	public $url = 'https://tk-kit.com/API.1?f=get_rp';
	protected $classificator;

	protected function getClassificator()
	{
		if(!isset($this->classificator)) {
			$this->classificator = new shopDpClassificatorKit();
		}

		return $this->classificator;
	}

	protected function query()
	{
		$result = $this->getNet(array(
			'format' => waNet::FORMAT_JSON,
			'request_format' => waNet::FORMAT_JSON
		))->query($this->url, array(), waNet::METHOD_GET);

		if(!$result) {
			throw new waException('Возникла ошибка при попытке запроса доступных пунктов выдачи КИТ');
		}

		return $result;
	}

	public function parseTime($time)
	{
		if($time === 'КРУГЛОСУТОЧНО') {
			return '00:00/00:00';
		} else {
			$source_time_parts = explode('-', $time);
			$time_parts = array();

			foreach($source_time_parts as $time_part) {
				$time_hm_parts = explode(':', $time_part);

				foreach($time_hm_parts as &$time_hm_part) {
					$time_hm_part = str_pad($time_hm_part, 2, '0', STR_PAD_LEFT);
				}

				array_push($time_parts, implode(':', $time_hm_parts));
			}

			return implode('/', $time_parts);
		}
	}

	public function parseWorktime($source_worktime)
	{
		if(mb_strpos($source_worktime, ' - ') !== false) { // Когда все пункты выдачи в одном формате, но один из них не такой как все
			$source_worktime = str_replace(': ', ' ', $source_worktime);
			$source_worktime = str_replace(' - ', '-', $source_worktime);
			$source_worktime = preg_replace('/:([0-9]{2}) /', ':$1,', $source_worktime);
			$source_worktime = mb_strtoupper($source_worktime);
		}

		$days = explode(',', $source_worktime);
		$worktime = array();

		foreach($days as $day) {
			$day = trim($day);
			$day = mb_strtoupper($day);

			$day_elements = explode(' ', $day);
			if(!$day || !$day_elements) {
				continue;
			}

			$day_elements = array_map('trim', $day_elements);

			$one_day = array_search($day_elements[0], $this->days);

			if($one_day !== false) {
				$worktime[$one_day] = array(
					'day' => $one_day,
					'period' => $this->parseTime($day_elements[1])
				);
			} else {
				if(preg_match('/^(ПН|ВТ|СР|ЧТ|ПТ|СБ|ВС)-(ПН|ВТ|СР|ЧТ|ПТ|СБ|ВС)$/iu', $day_elements[0], $days_elements)) {
					$first_day = array_search($days_elements[1], $this->days);
					$second_day = array_search($days_elements[2], $this->days);

					for($i = $first_day; $i <= $second_day; $i++)
						$worktime[$i] = array(
							'day' => $i,
							'period' => $this->parseTime($day_elements[1])
						);
				}
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
				case 'LAND1':
					$key = 'country_code';
					$value = $this->getClassificator()->getCountryCode($source_value);
					break;
				case 'REGIO':
					$key = 'region_code';
					break;
				case 'ORT01':
					$key = 'city_name';
					break;
				case 'STRAS':
					$key = 'address';
					break;
				case 'ZSCHWORK':
					$key = 'worktime';
					$value = $this->parseWorktime($source_value);
					break;
				case 'ZALTAD':
					$key = 'address_comment';
					break;
				case 'REMARK':
					$key = 'note';
					break;
				case 'EKIT':
					$key = 'cashless';
					$value = !empty($value) ? 1 : 0;
					break;
				case 'TEL_NUMBER':
					$key = 'phone';
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
	}

	public function takePoints($key = null)
	{
		try {
			$result = $this->query();
		} catch(waException $e) {
			$this->log($e->getMessage());

			return false;
		}

		$points = array();

		if(is_array($result)) {
			$source_cities = $result;

			foreach($source_cities as $source_city => $source_points) {
				foreach($source_points as $source_point) {
					if(!empty($source_point['STRAS'])) {
						$code = "{$source_city}_{$source_point['WERKS']}";

						$point = array(
							'code' => $code
						);

						$this->processPointData($point, $source_point);

						$points[] = $point;
					}
				}
			}
		}

		return $points;
	}
}
<?php

/**
 * Список пунктов выдачи службы PickPoint
 */

class shopDpPointsPickpointIntegration extends shopDpPointsIntegration implements shopDpPointsIntegrationInterface
{
	protected $params = array(
		'style' => 0,
		'actuality' => 604800
	);

	public $days = array(
		null, 'ПН', 'ВТ', 'СР', 'ЧТ', 'ПТ', 'СБ', 'ВС'
	);

	public $url = 'http://e-solution.pickpoint.ru/api/postamatlist';
	protected $classificator;

	protected function getClassificator()
	{
		if(!isset($this->classificator)) {
			$this->classificator = new shopDpClassificatorPickpoint();
		}

		return $this->classificator;
	}

	protected function query()
	{
		$result = $this->getNet(array(
			'format' => waNet::FORMAT_JSON,
			'request_format' => waNet::FORMAT_JSON,
			'timeout' => 30
		))->query($this->url, array(), waNet::METHOD_GET);

		if(!$result) {
			throw new waException('Возникла ошибка при попытке запроса доступных пунктов выдачи PickPoint');
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
		$timetable = explode(',', $source_worktime);
		$worktime = array();

		foreach($timetable as $day => $period) {
			$day = $day + 1;

			if($period !== 'NODAY') {
				if(strpos($period, '/') !== false)
					$period = substr($period, 0, strpos($period, '/'));

				$worktime[$day] = array(
					'day' => $day,
					'period' => $period
				);
			}
		}

		return $worktime;
	}

	public function processPointData(&$point, $data)
	{
		$point['country_code'] = 'rus';
		$point['region_code'] = $this->getClassificator()->getRegionCode(substr($data['Number'], 0, 2), $data['CitiName']);
		$point['city_name'] = $data['CitiName'];

		foreach($data as $source_key => $source_value) {
			$key = null;
			$value = null;

			switch($source_key) {
				case 'Id':
					$key = 'code';
					break;
				case 'Address':
					$key = 'address';
					break;
				case 'Card':
					$key = 'cashless';
					$value = $source_value == '1';
					break;
				case 'Fitting':
					$key = 'dressing_room';
					$value = $source_value == '1';
					break;
				case 'Latitude':
					$key = 'coord_y';
					break;
				case 'Longitude':
					$key = 'coord_x';
					break;
				case 'Metro':
					$key = 'metro_station';
					break;
				case 'OutDescription':
					$key = 'address_comment';
					break;
				case 'InDescription':
					$key = 'note';
					break;
				case 'WorkTime':
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
			$source_points = $result;

			foreach($source_points as $source_point) {
				$point = array();

				$this->processPointData($point, $source_point);

				$points[] = $point;
			}
		}

		return $points;
	}
}
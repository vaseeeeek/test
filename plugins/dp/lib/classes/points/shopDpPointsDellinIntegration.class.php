<?php

class shopDpPointsDellinIntegration extends shopDpPointsIntegration implements shopDpPointsIntegrationInterface
{
	const WORKTIME_PERIOD_REGEXP = '/^([0-9]{1,2})?(?:[:\.-])([0-9]{1,2})-([0-9]{1,2})(?:[:\.-])([0-9]{1,2})$/';

	protected $params = array(
		'style' => 0,
		'actuality' => 604800
	);

	public $url = 'https://api.dellin.ru/v3/public/terminals.json';
	private $key;

	protected $classificator;

	protected $days = array(
		'monday' => 1,
		'tuesday' => 2,
		'wednesday' => 3,
		'thursday' => 4,
		'friday' => 5,
		'saturday' => 6,
		'sunday' => 7
	);

	protected function getClassificator()
	{
		if(!isset($this->classificator)) {
			$this->classificator = new shopDpClassificatorDellin();
		}

		return $this->classificator;
	}

	private function getKey()
	{
		return $this->key;
	}

	private function setKey($key)
	{
		$this->key = $key;
	}

	private function query($url, $content = array(), $method = waNet::METHOD_GET)
	{
		return $this->getNet(array(
			'timeout' => 25,
			'format' => waNet::FORMAT_JSON,
			'request_format' => waNet::FORMAT_JSON,
		), array(
			'Content-Type' => 'application/json;charset=utf-8'
		))->query($url, $content, $method);
	}

	protected function queryConnect()
	{
		$result = $this->query($this->url, array(
			'appkey' => $this->getKey()
		), waNet::METHOD_POST);

		return $result;
	}

	protected function queryTerminals($url)
	{
		$result = file_get_contents($url);
		$result = @json_decode($result, true);

		return $result;
	}

	protected function isCorrectWorktable($worktable)
	{
		if(!array_key_exists('department', $worktable)) {
			return false;
		}

		$department = $worktable['department'];

		return $department === 'Приём и выдача груза' || $department === 'Выдача груза' || mb_strpos(mb_strtolower($department), 'выдача') !== false;
	}

	protected function isWorktimeDayCorrect($name, $value)
	{
		return in_array($name, array_keys($this->days)) && !empty($value) && $value !== '-';
	}

	protected function getWorktimeDay($name)
	{
		return $this->days[$name];
	}

	protected function padPeriodEntity($entity)
	{
		return $entity;
		return str_pad($entity, 2, '0', STR_PAD_LEFT);
	}

	protected function getWorktimePeriod($value)
	{
		$value = trim($value);

		if($value === '24 ч') {
			return '00:00/00:00';
		} elseif(preg_match(self::WORKTIME_PERIOD_REGEXP, $value, $matches)) {
			$from_h = $this->padPeriodEntity($matches[1]);
			$from_m = $this->padPeriodEntity($matches[2]);
			$to_h = $this->padPeriodEntity($matches[3]);
			$to_m = $this->padPeriodEntity($matches[4]);

			if($to_h === '24') {
				$to_h = '00';
			}

			return "{$from_h}:{$from_m}/{$to_h}:{$to_m}";
		}
	}

	public function processWorktimePeriod(&$worktime, $name, $value)
	{
		if($this->isWorktimeDayCorrect($name, $value)) {
			$day = $this->getWorktimeDay($name);
			$period = $this->getWorktimePeriod($value);

			if(!$period) {
				return false;
			}

			$worktime[$day] = array(
				'day' => $day,
				'period' => $period
			);
		}
	}

	public function processWorktime(&$point, $worktime)
	{
		$point['worktime'] = array();

		foreach($worktime as $name => $value) {
			$this->processWorktimePeriod($point['worktime'], $name, $value);
		}

		$point['worktime_string'] = shopDpPluginHelper::worktimeString($point['worktime']);
	}

	public function processPointData(&$point, $data)
	{
		$point['code'] = $data['id'];
		$point['address'] = $data['address'];
		$point['coord_y'] = $data['latitude'];
		$point['coord_x'] = $data['longitude'];

		if(array_key_exists('mainPhone', $data)) {
			$point['phone'] = $data['mainPhone'];
		}

		if(array_key_exists('worktables', $data)) {
			$_worktables = $data['worktables'];

			if(array_key_exists('worktable', $_worktables)) {
				$worktables = $_worktables['worktable'];

				foreach($worktables as $worktable) {
					if($this->isCorrectWorktable($worktable)) {
						$this->processWorktime($point, $worktable);

						break;
					}
				}
			}
		}
	}

	public function takePoints($key = null)
	{
		$settings = $this->getPlugin()->getSettings('shipping_methods');

		$key = ifset($settings, $this->id, 'settings', 'dellin', 'appkey', null);

		if(!$key)
			return false;

		$this->setKey($key);

		try {
			$result = $this->queryConnect();
		} catch(waException $e) {
			$this->log($e->getMessage());

			return false;
		}

		if(!array_key_exists('url', $result)) {
			return false;
		}

		$url = $result['url'];

		$result = $this->queryTerminals($url);

		if(!array_key_exists('city', $result)) {
			return false;
		}

		$cities = $result['city'];

		$points = array();
		$general_point = array(
			'country_code' => 'rus'
		);

		foreach($cities as $city) {
			if(array_key_exists('terminals', $city)) {
				$_terminals = $city['terminals'];

				if(array_key_exists('terminal', $_terminals)) {
					$city_point = $general_point;
					$city_point['city_name'] = $city['name'];
					$city_point['region_code'] = substr($city['code'], 0, 2);

					$terminals = $_terminals['terminal'];

					foreach($terminals as $terminal) {
						$point = $city_point;

						$this->processPointData($point, $terminal);

						$points[] = $point;
					}
				}
			}
		}

		return $points;
	}
}
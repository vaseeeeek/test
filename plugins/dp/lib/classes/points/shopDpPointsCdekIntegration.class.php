<?php

/**
 * Список пунктов выдачи службы СДЭК
 */

class shopDpPointsCdekIntegration extends shopDpPointsIntegration implements shopDpPointsIntegrationInterface
{
	protected $params = array(
		'style' => 1,
		'actuality' => 7 * 24 * 60 * 60,
	);

	public $url = 'http://integration.cdek.ru/pvzlist.php';
	protected $query_params = array();
	protected $classificator;

	protected function getClassificator()
	{
		if(!isset($this->classificator)) {
			$this->classificator = new shopDpClassificatorCdek();
		}

		return $this->classificator;
	}

	protected function prepareQuery()
	{
		$search_params = $this->getSearchParams();

		$country_source_code = $search_params['country_code'];
		$country_code = $this->getClassificator()->getCountryCode($country_source_code, true);

		$region_source_code = $search_params['region_code'];
		$region_code = $this->getClassificator()->getRegionCode($region_source_code, true);

		$query_params = array(
			'countryid' => $country_code,
			'regionid' => $region_code
		);

		$this->setQueryParams($query_params);
	}

	protected function query()
	{
		$result = $this->getNet(array(), array('accept' => 'application/xml'))->query($this->url, $this->getQueryParams(), waNet::METHOD_GET);

		if(!$result || !($result instanceof SimpleXMLElement)) {
			throw new waException('Возникла ошибка при попытке запроса доступных пунктов выдачи СДЭК');
		}

		if(!empty($result['ErrorCode']) || !empty($result['ErrorMsg'])) {
			throw new waException(sprintf('Сервер СДЭК вернул ошибку: %s', ifset($result, 'ErrorMsg', ifset($result, 'ErrorCode', 'Неизвестная ошибка'))));
		}

		return $result;
	}

	public function takePoints($key = null)
	{
		$this->prepareQuery();

		try {
			$result = $this->query();
		} catch(waException $e) {
			$this->log($e->getMessage());

			return false;
		}

		$points = array();

		if($result instanceof SimpleXMLElement) {
			$source_points = $result->xpath('//PvzList/Pvz');

			foreach($source_points as $source_point) {
				if(strval($source_point['Type']) != 'PVZ')
					continue;

				$point = array();

				$this->processAttributes($point, $source_point->attributes());
				$this->processWorktime($point, $source_point->xpath('WorkTimeY'));

				$points[] = $point;
			}
		}

		return $points;
	}

	public function processWorktime(&$point, $worktime)
	{
		$point['worktime'] = array();

		foreach($worktime as $day) {
			$this->processWorktimeDay($point['worktime'], $day->attributes());
		}

		$point['worktime_string'] = shopDpPluginHelper::worktimeString($point['worktime']);
	}

	public function processWorktimeDay(&$worktime, $attributes)
	{
		$period = array();
		foreach($attributes as $source_key => $value) {
			switch($source_key) {
				case 'day':
					$day = intval($value);
					break;
				case 'periods':
					$period['period'] = strval($value);
					break;
			}
		}

		if(!empty($day) && !empty($period)) {
			$period['day'] = $day;
			$worktime[$day] = $period;
		}
	}

	public function processAttributes(&$point, $attributes)
	{
		foreach($attributes as $source_key => $source_value) {
			$key = null;

			switch($source_key) {
				case 'Code':
				case 'CountryName':
				case 'RegionName':
				case 'Address':
				case 'FullAddress':
				case 'AddressComment':
				case 'Phone':
				case 'Email':
				case 'Note':
				case 'coordX':
				case 'coordY':
				case 'NearestStation':
				case 'MetroStation':
					$key = shopDpPluginHelper::camelToUnderscore($source_key);
					if($key === 'address') {
						$source_value = str_replace('C', 'С', str_replace('c', 'с', str_replace('A', 'А', str_replace('a', 'а', (string) $source_value))));
					}
					break;
				case 'City':
					$key = 'city_name';
					break;
				case 'CountryCode':
					$key = 'country_code';
					break;
				case 'RegionCode':
					$key = 'region_code';
					break;
				case 'IsDressingRoom':
					$key = 'dressing_room';
					break;
				case 'HaveCashless':
					$key = 'cashless';
					break;
			}

			if(!empty($key)) {
				if($source_value instanceof SimpleXMLElement)
					$value = (string) $source_value;
				else
					$value = $source_value;

				if(in_array($key, array('dressing_room', 'cashless')))
					switch($value) {
						case 'true':
							$value = 1;
							break;
						case 'false':
							$value = 0;
							break;
					}

				if(in_array($key, array('region_code', 'country_code'))) {
					$value = $this->getClassificator()->getCode($key, intval($value));
				}

				if ($key === 'city_name') {
					if ($value === 'Острогожск, Острогожский р-н') {
						$value = 'Острогожск';
					}
				}

				$point[$key] = (string)$value;
			}
		}
	}
}

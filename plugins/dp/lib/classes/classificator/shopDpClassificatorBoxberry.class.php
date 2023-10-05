<?php

class shopDpClassificatorBoxberry
{
	const ACTUALITY = 604800;
	const TIMEOUT = 20;

	public $url = 'https://api.boxberry.ru/json.php';
	protected $path;
	protected $countries_classification;
	protected $countries_classification_inverted;
	protected $city_classification;
	private $token;

	public function __construct($token = null, $url = null)
	{
		$this->path = wa()->getAppPath('plugins/dp', 'shop') . '/lib/config/data/services/boxberry/';

		if($url) {
			$this->url = $url;
		}
		$this->token = $token;

		$this->getCountriesClassification();
		$this->getCityClassification();
	}

	protected function getCache()
	{
		return new waVarExportCache(md5('shop_dp_boxberry_classification'), self::ACTUALITY);
	}

	private function getToken()
	{
		return $this->token;
	}

	public function getCountriesClassification()
	{
		if(!isset($this->countries_classification)) {
			$this->countries_classification = json_decode(file_get_contents($this->path . 'countries.json'), true);
			$this->countries_classification_inverted = array_flip($this->countries_classification);
		}
	}

	public function getCountryCode($value, $inverted = false)
	{
		if($inverted) {
			return ifset($this->countries_classification_inverted, $value, null);
		} else {
			return ifset($this->countries_classification, $value, null);
		}
	}

	protected function takeCityClassification()
	{
		if($this->getToken() === null) {
			return null;
		}

		$net = new waNet(array(
			'request_format' => 'default',
			'format' => waNet::FORMAT_JSON,
			'timeout' => self::TIMEOUT
		));

		$response = $net->query($this->url, array(
			'token' => $this->getToken(),
			'method' => 'ListCities'
		), waNet::METHOD_GET);

		$classification = array();

		$i = 0;
		foreach($response as $row) {
			$country_code = $this->getCountryCode($row['CountryCode']);
			$city_code = $row['Code'];
			$city_name = $row['Name'];

			if($country_code == 'rus') {
				$region_code = mb_substr($row['Kladr'], 0, 2, 'UTF-8');
			} else {
				$region_code = $i++;
			}

			if(!isset($classification[$country_code])) {
				$classification[$country_code] = array();
			}

			if(!isset($classification[$country_code][$region_code])) {
				$classification[$country_code][$region_code] = array();
			}

			$classification[$country_code][$region_code][$city_code] = $city_name;
		}

		return $classification;
	}

	public function getCityClassification()
	{
		if(!isset($this->city_classification)) {
			$this->city_classification = $this->getCache()->get();

			if(!$this->city_classification) {
				$this->city_classification = $this->takeCityClassification();

				if($this->city_classification) {
					$this->getCache()->set($this->city_classification);
				}
			}
		}
	}

	public function getCountryClassifications($value)
	{
		return ifset($this->city_classification, $value, null);
	}

	public function getCityCode($params)
	{
		if(empty($params['country_code']) || empty($params['region_code']) || empty($params['city_name']))
			return null;

		if($country_classifications = $this->getCountryClassifications($params['country_code'])) {
			if($params['country_code'] === 'rus') {
				if(!empty($country_classifications[$params['region_code']])) {
					foreach($country_classifications[$params['region_code']] as $city_code => $city_name) {
						if(mb_strtolower($city_name) == mb_strtolower($params['city_name'])) {
							return $city_code;
							break;
						}
					}
				}
			} else {
				foreach($country_classifications as $region_classifications) {
					foreach($region_classifications as $city_code => $city_name)
						if(mb_strtolower($city_name) == mb_strtolower($params['city_name'])) {
							return $city_code;
							break 2;
						}
				}
			}
		}

		return null;
	}
}
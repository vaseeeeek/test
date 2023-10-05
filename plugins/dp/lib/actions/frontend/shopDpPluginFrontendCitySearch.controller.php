<?php

class shopDpPluginFrontendCitySearchController extends waJsonController
{
	private $region_model;
	private $region_names = array();

	public function __construct()
	{
		$this->region_model = new waRegionModel();
	}

	private function getRegionModel()
	{
		return $this->region_model;
	}

	public function execute()
	{
		$query = trim(waRequest::get('query'));

		if(mb_strlen($query) >= 3) {
			$plugin = shopDpPlugin::getInstance('city_search');

			$is_ip_plugin_ready = $plugin->getSettings('ip_status') && $plugin->getEnv()->isReadyIpPluginCityApi();
			if($is_ip_plugin_ready) {
				$cities = shopIpPlugin::getCityApi()->getCities(shopIpCityCondition::create()->setQuery($query), 10);

				if($cities) {
					$existing_names = array();
					foreach($cities as $key => &$city) {
						if(array_key_exists($city['name'], $existing_names))
							$existing_names[$city['name']][] = $key;
						else
							$existing_names[$city['name']] = array($key);

						$city['region_name'] = $this->getRegionName($cities[$key]['country_iso3'], $cities[$key]['region_code']);
					}

					foreach($existing_names as $name => $keys) {
						if(count($keys) > 1)
							foreach($keys as $key) {
								$cities[$key]['existing_name'] = true;
							}
					}

					$this->response = $cities;
				}
			}
		} else {
			$this->setError('Минимальная длина запроса для поиска города - 3 символа');
		}
	}

	private function getRegionName($country_iso3, $region_code)
	{
		$key = "$country_iso3/$region_code";

		if(!isset($this->region_names[$key])) {
			$data = $this->getRegionModel()->getByField(array(
				'country_iso3' => $country_iso3,
				'code' => $region_code
			));

			$this->region_names[$key] = $data['name'];
		}

		return $this->region_names[$key];
	}
}
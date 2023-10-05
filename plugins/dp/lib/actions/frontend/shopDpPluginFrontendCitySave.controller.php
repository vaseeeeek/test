<?php

class shopDpPluginFrontendCitySaveController extends waJsonController
{
	protected $location;
	protected $location_storage;

	protected function getLocation()
	{
		if(!isset($this->location)) {
			$this->location = new shopDpLocation();
		}

		return $this->location;
	}

	protected function getLocationStorage()
	{
		if(!isset($this->location_storage)) {
			$this->location_storage = new shopDpLocationStorage();
		}

		return $this->location_storage;
	}

	public function execute()
	{
		$country = waRequest::post('country');
		$region = waRequest::post('region');
		$city = waRequest::post('city');
		$zip = waRequest::post('zip');
		$url = waRequest::post('url', '');

		$location = $this->getLocation();

		$redirect_url = null;

		if($location->isUseRegionsPlugin() && $url) {
			$model = new shopRegionsCityModel();

			try {
				$row = $model->getByField(array(
					'country_iso3' => $country,
					'region_code' => $region,
					'name' => $city
				), false);

				if($row) {
					$regions_city = shopRegionsCity::build($row);

					if($regions_city) {
						$_SERVER['REQUEST_URI'] = $url;

						$params = array();
						waRequest::setParam($params);

						wa()->getRouting()->dispatch();

						$app = waRequest::param('app');

						$regions_routing = new shopRegionsRouting();
						$route = $shop_route = $regions_routing->getRoute($regions_city->getStorefront());

						if($app !== 'shop') {
							$routing = wa($app)->getRouting();
							$new_route_domain = $shop_route['domain'];
							$app_routes = $routing->getByApp($app, $new_route_domain);

							if(count($app_routes)) {
								$route = array_pop($app_routes);
								$route['domain'] = $new_route_domain;
							} else {
								$app_routes = $routing->getByApp($app, $new_route_domain);

								if(!count($app_routes)) {
									$this->displayJson(array('redirect_url' => ''));
									return;
								}

								$route = array_pop($app_routes);
							}
						}

						$redirect_url = $regions_routing->getCurrentUrlForRoute($route);
						$regions_routing->changeStorefrontRegion($regions_city);
					}
				}
			} catch(waException $e) {
			};
		}

		$location->setCountry($country);
		$location->setRegion($region);
		$location->setCity($city);
		$location->setZip($zip);

		if($location->isUseUserRegion() && wa()->getUser()->isAuth()) {
			$location_storage = $this->getLocationStorage();
			$location_storage->save($location);
		}

		wa()->getResponse()->setCookie('dp_plugin_no_regions_plugin', !isset($row));

		if($redirect_url) {
			$this->response = array(
				'url' => $redirect_url
			);
		}
	}
}
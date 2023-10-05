<?php

class shopDpLocation
{
	/**
	 * @const COUNTRY_KEY
	 * @const REGION_KEY
	 * @const CITY_KEY
	 * @const ZIP_KEY
	 */
	const COUNTRY_KEY = 'dp_plugin_country';
	const REGION_KEY = 'dp_plugin_region';
	const CITY_KEY = 'dp_plugin_city';
	const ZIP_KEY = 'dp_plugin_zip';

	/**
	 * @var shopDpEnv $env
	 * @var shopDpSettingsStorage $settings_storage
	 */
	protected static $env;
	protected static $settings_storage;

	protected $classificator;

	private $is_initialized = false;

	/**
	 * @var string $country
	 * @var string $region
	 * @var string $city
	 * @var string $zip
	 */
	private $country;
	private $region;
	private $city;
	private $zip;

	public function __construct($initiator = null)
	{
		if (!$this->is_initialized) {
			$this->init();

			$this->is_initialized = true;
		}
	}

	public function isUseRegionsPlugin()
	{
		return $this->getSettings('ip_regions_plugin_status') && $this->getEnv()->isEnabledRegionsPlugin();
	}

	public function isUseUserRegion()
	{
		return (bool) $this->getSettings('user_region_status');
	}

	public function isUseCookies()
	{
		return waRequest::cookie(self::COUNTRY_KEY) && waRequest::cookie(self::REGION_KEY) && waRequest::cookie(self::CITY_KEY);
	}

	public function isEnabledIpPlugin()
	{
		return $this->getSettings('ip_status') && $this->getEnv()->isEnabledIpPlugin();
	}

	protected function init()
	{
		list($location_info, $save) = $this->determineCurrentLocationInfo();

		$this->setLocationInfo($location_info, $save);
	}

	protected function getClassificator()
	{
		if(!isset($this->classificator)) {
			$country = $this->getCountry();

			$this->classificator = new shopDpLocationClassificator($country);
		}

		return $this->classificator;
	}

	protected static function getSettingsStorage()
	{
		if(!self::$settings_storage) {
			self::$settings_storage = new shopDpSettingsStorage(self::getEnv());
		}

		return self::$settings_storage;
	}

	protected function getSettings($name = null)
	{
		return self::getSettingsStorage()->getSettings($name);
	}

	protected static function getEnv()
	{
		if(!isset(self::$env)) {
			self::$env = new shopDpEnv();
		}

		return self::$env;
	}

	protected function getWaCountryModel()
	{
		if(!isset($this->wa_country_model)) {
			$this->wa_country_model = new waCountryModel();
		}

		return $this->wa_country_model;
	}

	protected function getWaRegionModel()
	{
		if(!isset($this->wa_region_model)) {
			$this->wa_region_model = new waRegionModel();
		}

		return $this->wa_region_model;
	}

	public function getCountry($type = 'code')
	{
		if ($type == 'code') {
			return $this->country;
		} elseif ($type == 'name') {
			$country = $this->getWaCountryModel()->get($this->country);

			return $country['name'];
		}

		return null;
	}

	public function setCountry($country, $save = true)
	{
		$this->country = $country;

		if ($save) {
			wa()->getResponse()->setCookie(self::COUNTRY_KEY, $country, 0, wa()->getRootUrl());
		}
	}

	public function getRegion($type = 'code')
	{
		if($type == 'code') {
			return $this->region;
		} elseif($type == 'name') {
			$region = $this->getWaRegionModel()->get($this->country, $this->region);

			return $region['name'];
		}

		return null;
	}

	public function setRegion($region, $save = true)
	{
		$this->region = $region;

		if($save) {
			wa()->getResponse()->setCookie(self::REGION_KEY, $region, 0, wa()->getRootUrl());
		}
	}

	public function getCity()
	{
		return $this->city;
	}

	public function setCity($city, $save = true)
	{
		$this->city = $city;

		if ($save) {
			wa()->getResponse()->setCookie(self::CITY_KEY, $city, 0, wa()->getRootUrl());
		}
	}

	public function getZip()
	{
		if($this->zip) {
			return $this->zip;
		} else {
			$zip = waRequest::cookie(self::ZIP_KEY);

			if($zip) {
				return $zip;
			}

			$country = $this->getCountry();
			$city = $this->getCity();
			$zip_key = self::ZIP_KEY . '_' . md5("{$country}_{$city}");

			if(waRequest::cookie($zip_key)) {
				return waRequest::cookie($zip_key);
			}

			$zip = $this->getClassificator()->getCityZip($city);

			if($zip) {
				wa()->getResponse()->setCookie($zip_key, $zip, 0, wa()->getRootUrl());

				return $zip;
			}
		}

		return null;
	}

	public function setZip($zip, $save = true)
	{
		$this->zip = $zip;

		if($save) {
			wa()->getResponse()->setCookie(self::ZIP_KEY, $zip, 0, wa()->getRootUrl());
		}
	}

	public function checkForCity($city)
	{
		return trim(mb_strtolower($city)) === trim(mb_strtolower($this->getCity()));
	}

	private function getLocationInfoFromRegionsPlugin()
	{
		$integration = $this->getEnv()->getPluginIntegration('regions');
		if (!$integration) {
			return null;
		}

		$location = $integration->getLocation();
		if (!$location) {
			return null;
		}

		return [
			'country' => $location['country'],
			'region' => $location['region'],
			'city' => $location['city'],
		];
	}

	private function getLocationInfoFromCurrentUserAddress()
	{
		$contact = $this->getEnv()->getContact();

		$address = $contact->get('address.shipping');

		if (!isset($address[0]['data'])) {
			return null;
		}

		$data = $address[0]['data'];

		$country = ifset($data, 'country', null);
		$region = ifset($data, 'region', null);
		$city = ifset($data, 'city', null);
		$zip = ifset($data, 'zip', null);

		return [
			'country' => $country,
			'region' => $region,
			'city' => $city,
			'zip' => $zip,
		];
	}

	private function getLocationInfoFromCookies()
	{
		return [
			'country' => waRequest::cookie(self::COUNTRY_KEY),
			'region' => waRequest::cookie(self::REGION_KEY),
			'city' => waRequest::cookie(self::CITY_KEY),
		];
	}

	private function getLocationInfoFromIpPlugin()
	{
		try {
			$geo = shopIpPlugin::getGeoIpApi()->getForCurrentIp();

			if ($geo) {
				return [
					'country' => $geo->getCountry(),
					'region' => $geo->getRegion(),
					'city' => $geo->getCity(),
				];
			}
		} catch(Exception $e) {
		}

		return null;
	}

	private function getDefaultLocationInfo()
	{
		return [
			'country' => $this->getSettings('country'),
			'region' => $this->getSettings('region'),
			'city' => $this->getSettings('city'),
		];
	}

	private function isValidLocationInfo($location_info)
	{
		return is_array($location_info)
			&& isset($location_info['country']) && isset($location_info['region']) && isset($location_info['city'])
			&& is_string($location_info['country']) && is_string($location_info['region']) && is_string($location_info['city'])
			&& trim($location_info['country']) !== '' && trim($location_info['region']) !== '' && trim($location_info['city']) !== '';
	}

	private function determineCurrentLocationInfo()
	{
		$use_regions_plugin = $this->isUseRegionsPlugin() && !waRequest::cookie('dp_plugin_no_regions_plugin');

		if ($this->isUseUserRegion() && wa()->getUser()->isAuth()) {
			$location_info = $this->getLocationInfoFromCurrentUserAddress();

			if ($this->isValidLocationInfo($location_info)) {
				return [$location_info, false];
			}
		}

		if ($use_regions_plugin) {
			$location_info = $this->getLocationInfoFromRegionsPlugin();

			if ($this->isValidLocationInfo($location_info)) {
				return [$location_info, false];
			}
		}

		if ($this->isUseCookies()) {
			$location_info = $this->getLocationInfoFromCookies();

			if ($this->isValidLocationInfo($location_info)) {
				return [$location_info, false];
			}
		}

		if ($this->isEnabledIpPlugin()) {
			$location_info = $this->getLocationInfoFromIpPlugin();

			if ($this->isValidLocationInfo($location_info)) {
				return [$location_info, !$use_regions_plugin];
			}
		}

		return [$this->getDefaultLocationInfo(), false];
	}

	/**
	 * @param array $location_info
	 * @param bool $save
	 */
	private function setLocationInfo(array $location_info, $save)
	{
		$this->setCountry($location_info['country'], $save);
		$this->setRegion($location_info['region'], $save);
		$this->setCity($location_info['city'], $save);

		if (
			isset($location_info['zip'])
			&& is_string($location_info['zip'])
			&& trim($location_info['zip']) !== ''
		) {
			$this->setZip($location_info['zip'], $save);
		}
	}
}
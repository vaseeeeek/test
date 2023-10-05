<?php


/**
 * Структура для работы с городами
 */
class shopRegionsCity
{
	private $m_city;
	private $m_c_param;
	private $m_s_page;

	private $row = array();

	private $is_loaded_params = false;
	private $params = array();

	private $m_region;
	private $is_loaded_area = array();
	private $area = array();


	public static function create()
	{
		$city = new self();
		$city->row = array(
			'id' => null,
			'country_iso3' => null,
			'region_code' => null,
			'name' => null,
			'storefront' => null,
			'is_popular' => false,
			'is_enable' => false,
			'domain_id' => 0,
			'shop_url' => '',
			'create_datetime' => date('Y-m-d H:i:s'),
			'update_datetime' => date('Y-m-d H:i:s'),
		);

		return $city;
	}

	public static function build(array $row)
	{
		$city = new self();
		$city->row = $row;

		return $city;
	}

	public static function buildByIpAnalyzerResult(shopRegionsIpAnalyzerResult $result)
	{
		$data = $result->getCityData();

		$country = ifset($data['country'], array());
		$region = ifset($data['region'], array());
		$city = ifset($data['city'], array());

        if ((isset($data['error']) && $data['error'] !== null) || !count($country) || !count($region) || !count($city))
		{
			return null;
		}

		$routing = new shopRegionsRouting();
		$route = $routing->getCurrentRoute();

		/** @var shopConfig $shop_config */
		$shop_config = wa('shop')->getConfig();
		$country_record = waCountryModel::getInstance()->getByField('iso2letter', strtolower($country['iso']));

		$city_obj = new self();
		$city_obj->row = array(
			'id' => null,
			'name' => ifset($city['name_ru'], $city['name_ru']),
			'country_iso3' => $country_record ? $country_record['iso3letter'] : '',
			'region_code' => ifset($region['code'], ''),
			'storefront' => $routing->getCurrentStorefront(),
			'phone' => $shop_config->getGeneralSettings('phone'),
			'email' => $shop_config->getGeneralSettings('email'),
			'schedule' => '',
			'is_popular' => 1,
			'is_enable' => 1,
			'is_default_for_storefront' => 1,
			'sort' => 0,
			'domain_name' => $route['domain'],
			'route' => $route['url'],
			'create_datetime' => date('Y-m-d H:i:s'),
			'update_datetime' => date('Y-m-d H:i:s'),
		);

		return $city_obj;
	}

	public static function load($id)
	{
		$model = new shopRegionsCityModel();
		$row = $model->getById($id);

		if ($row)
		{
			return self::build($row);
		}

		return null;
	}

	public static function isExists($id)
	{
		$m_city = new shopRegionsCityModel();

		return $m_city->countByField('id', $id) > 0;
	}

	public static function deleteById($ids)
	{
		if (!is_array($ids))
		{
			$ids = array($ids);
		}

		/** @var shopRegionsCity[] $cities */
		$cities = array();
		foreach ($ids as $id)
		{
			$city = shopRegionsCity::load($id);
			if (!$city)
			{
				return false;
			}
			$cities[] = $city;
		}

		foreach ($cities as $city)
		{
			if (!$city->delete())
			{
				return false;
			}
		}

		return true;
	}

	public function isCreated() { return isset($this->row['id']); }

	public function getID() { return ifset($this->row['id']); }

	public function getCountryIso3() { return ifset($this->row['country_iso3']); }

	public function setCountryIso3($country_iso3) { $this->row['country_iso3'] = $country_iso3; }

	public function getRegionCode() { return ifset($this->row['region_code']); }

	public function setRegionCode($region_code) { $this->row['region_code'] = $region_code; }

	public function getName() { return ifset($this->row['name']); }

	public function setName($name) { $this->row['name'] = $name; }

	public function getPhone() { return ifset($this->row['phone']); }

	public function setPhone($phone) { $this->row['phone'] = $phone; }

	public function getEmail() { return ifset($this->row['email']); }

	public function setEmail($email) { $this->row['email'] = $email; }

	public function getSchedule() { return ifset($this->row['schedule']); }

	public function setSchedule($schedule) { $this->row['schedule'] = $schedule; }

	public function getStorefront() { return ifset($this->row['storefront']); }

	public function setStorefront($storefront) { $this->row['storefront'] = $storefront; }

	public function getIsPopular() { return ifset($this->row['is_popular']); }

	public function setIsPopular($is_popular) { $this->row['is_popular'] = $is_popular; }

	public function getIsEnable() { return ifset($this->row['is_enable']); }

	public function setIsEnable($is_enable) { $this->row['is_enable'] = $is_enable; }

	public function getIsDefaultForStorefront() { return ifset($this->row['is_default_for_storefront']); }

	public function setIsDefaultForStorefront($is_default) { $this->row['is_default_for_storefront'] = $is_default; }

	public function getSort() { return ifset($this->row['sort']); }

	public function setSort($sort) { $this->row['sort'] = $sort; }

	public function getCreateDatetime() { return ifset($this->row['create_datetime']); }

	public function setCreateDatetime($create_datetime) { $this->row['create_datetime'] = $create_datetime; }

	public function getUpdateDatetime() { return ifset($this->row['update_datetime']); }

	public function setUpdateDatetime($update_datetime) { $this->row['update_datetime'] = $update_datetime; }

	public function getParams() { return $this->lazyLoadParams(); }

	public function getParam($id)
	{
		$params = $this->getParams();

		return ifset($params[$id]);
	}

	public function setParams(array $values)
	{
		$this->cleanParams();

		foreach ($values as $param_id => $value)
		{
			if (shopRegionsParam::isExists($param_id))
			{
				$this->params[$param_id] = $value;
			}
		}
	}

	public function getUrl()
	{
		$storefront_name = $this->getStorefront();

		if ($storefront_name)
		{
			$storefront = new shopRegionsRoute($storefront_name, $this->getDomainName(), $this->getRoute());

			return $storefront->getUrl() . '?change_city='.$this->getID();
		}

		return null;
	}

	public function getCountryName()
	{
		if (array_key_exists('country_name', $this->row))
		{
			return $this->row['country_name'];
		}

		$iso3 = $this->getCountryIso3();
		if (!$iso3)
		{
			$this->row['country_name'] = null;
			return null;
		}

		$country_model = waCountryModel::getInstance();
		$country_row = $country_model->getByField('iso3letter', $iso3);

		if (!$country_row)
		{
			$this->row['country_name'] = null;
			return null;
		}

		$this->row['country_name'] = _ws($country_row['name']);
		return $this->row['country_name'];
	}

	public function loadStorefrontSpecificSettings()
	{
		$settings_model = new shopRegionsCitySettingsModel();
		return $settings_model->loadStorefrontSettings($this->getID());
	}

	public function saveStorefrontSpecificSettings($settings)
	{
		if (!is_array($settings) || empty($settings))
		{
			return false;
		}

		$settings_model = new shopRegionsCitySettingsModel();
		return $settings_model->saveStorefrontSettings($this->getID(), $settings);
	}

	public function getAreaName()
	{
		$area = $this->lazyLoadArea($this->getCountryIso3());

		if (isset($area[$this->getRegionCode()]))
		{
			return $area[$this->getRegionCode()]['name'];
		}

		return null;
	}

	public function getDomainId()
	{
		return ifset($this->row['domain_id']);
	}

	public function getDomainName()
	{
		if (!array_key_exists('domain_name', $this->row) && ($domain_id = $this->getDomainId()))
		{
			$this->setDomain($domain_id);
		}

		return ifset($this->row['domain_name'], '');
	}

	public function getDomainTitle()
	{
		if (!array_key_exists('domain_title', $this->row) && ($domain_id = $this->getDomainId()))
		{
			$this->setDomain($domain_id);
		}

		return ifset($this->row['domain_title'], '');
	}

	public function setDomain($domain_id)
	{
		wa('site');
		$model = new siteDomainModel();

		if (wa_is_int($domain_id))
		{
			$domain = $model->getById($domain_id);
		}
		else
		{
			$domain_name = $domain_id;
			$domain = $model->getByName($domain_name);
			$domain_id = 0;
		}

		if ($domain)
		{
			$domain_id = $domain['id'];
			$this->row['domain_name'] = $domain['name'];
			$this->row['domain_title'] = $domain['title'];
		}

		$this->row['domain_id'] = $domain_id;
	}

	public function getRoute()
	{
		return ifset($this->row['route']);
	}

	public function setRoute($route)
	{
		$this->row['route'] = $route;
	}

	public function getStorefrontTitle()
	{
		return $this->getDomainTitle() . '/' . $this->getRoute();
	}

	public function getStorefrontSettings()
	{
		$city_settings_model = new shopRegionsCitySettingsModel();

		return $city_settings_model->loadStorefrontSettings($this->getID());
	}

	public function getShopRoute()
	{
		$domain_name = $this->getDomainName();
		$route_url = $this->getRoute();

		$route = array();
		foreach (wa('shop')->getRouting()->getByApp('shop', $domain_name) as $domain_route)
		{
			if (is_array($domain_route) && array_key_exists('url', $domain_route) && $domain_route['url'] == $route_url)
			{
				$route = $domain_route;

				break;
			}
		}

		$storefront_settings = $this->getStorefrontSettings();
		if ($storefront_settings)
		{
			foreach ($storefront_settings as $key => $value)
			{
				$route[$key] = $value;
			}
		}

		$route['domain'] = $domain_name;

		return $route;
	}

	public function toArray($include_params = false, $include_url = false)
	{
		$include_params = (bool)$include_params;
		$data = $this->row;
		$data['storefront'] = $this->getStorefront();
		if ($data['storefront'])
		{
			$data['storefront_title'] = $this->getStorefrontTitle();
		}

		if ($include_params)
		{
			$data['params'] = $this->getParams();
		}

		if ($include_url)
		{
			$data['url'] = $this->getUrl();
		}

		return $data;
	}

	public function save()
	{
		if (!ifset($this->row['domain_id']) && ifset($this->row['domain_name']))
		{
			$this->setDomain($this->row['domain_name']);
		}

		if ($this->row['create_datetime'] === null || $this->row['create_datetime'] == '0000-00-00 00:00:00')
		{
			$this->setCreateDatetime(date('Y-m-d H:i:s'));
		}
		$this->setUpdateDatetime(date('Y-m-d H:i:s'));

		$success = $this->isCreated()
			? $this->update()
			: $this->insert();

		return $success && $this->ensureStorefrontDefaultCityConsistency();
	}

	public function delete()
	{
		$was_default_for_storefront = $this->getIsDefaultForStorefront() == 1;

		$this->cleanParams();
		$result = $this->saveAdditionalData();

		if (!$result)
		{
			return false;
		}

		$result = $this->m_city->deleteById($this->getID());

		if (!$result)
		{
			return false;
		}

		if ($was_default_for_storefront)
		{
			$this->ensureStorefrontDefaultCityConsistency();
		}
		$this->row = array();

		return true;
	}

	private function __construct()
	{
		$this->m_city = new shopRegionsCityModel();
		$this->m_c_param = new shopRegionsCityParamModel();
		$this->m_s_page = new shopPageModel();
	}

	private function lazyLoadParams()
	{
		if (!$this->is_loaded_params)
		{
			$this->params = $this->loadParams();
			$this->is_loaded_params = true;
		}

		return $this->params;
	}

	private function cleanParams()
	{
		// Не важно что было до этого.
		$this->is_loaded_params = true;
		$this->params = array();
	}

	private function loadParams()
	{
		$params = array();

		if ($this->isCreated())
		{
			$_params = $this->m_c_param->getByField('city_id', $this->getID(), true);

			foreach ($_params as $param)
			{
				$params[$param['param_id']] = $param['value'];
			}
		}

		return $params;
	}

	private function lazyLoadArea($country)
	{
		if (!isset($this->is_loaded_area[$country]))
		{
			$this->area[$country] = $this->loadArea($country);
			$this->is_loaded_area[$country] = true;
		}

		return $this->area[$country];
	}

	private function loadArea($country)
	{
		if (!isset($this->m_region) or !($this->m_region instanceof waRegionModel))
		{
			$this->m_region = new waRegionModel();
		}

		return $this->m_region->getByCountry($country);
	}

	private function insert()
	{
		if (!ifset($this->row['sort'], false))
		{
			$sort_next = (int) $this->m_city->select('MAX(`sort`)')->fetchField() + 1;
			$this->row['sort'] = $sort_next;
		}

		$result = $this->m_city->insert($this->row);

		if (!$result)
		{
			return $result;
		}

		$id = $result;
		$this->row['id'] = $id;

		return $this->saveAdditionalData();
	}

	private function update()
	{
		$result = $this->m_city->updateById($this->getID(), $this->row);

		if (!$result)
		{
			return $result;
		}

		return $this->saveAdditionalData();
	}

	private function saveParams()
	{
		if ($this->isCreated())
		{
			$params = $this->getParams();
			$rows = array();

			foreach ($params as $param_id => $value)
			{
				$rows[] = array(
					'city_id' => $this->getID(),
					'param_id' => $param_id,
					'value' => $value,
				);
			}

			$status = $this->m_c_param->deleteByField('city_id', $this->getID());

			return $status and $this->m_c_param->multipleInsert($rows);
		}

		return false;
	}

	private function saveAdditionalData()
	{
		return $this->saveParams();
	}

	/**
	 * следим за тем, чтобы для витрины существовал ровно один регион по-умолчанию
	 *
	 * @return bool
	 */
	private function ensureStorefrontDefaultCityConsistency()
	{
		$sql = '
SELECT t.storefront, SUM(t.is_default_for_storefront) AS `sum`
FROM shop_regions_city AS t
GROUP BY t.storefront
HAVING SUM(t.is_default_for_storefront) <> 1
';

		$set_one_sql = '
UPDATE shop_regions_city
SET is_default_for_storefront = 1
WHERE storefront = :storefront
LIMIT 1
';

		$unset_all_sql = '
UPDATE shop_regions_city
SET is_default_for_storefront = 0
WHERE storefront = :storefront
LIMIT 1
';

		foreach ($this->m_city->query($sql) as $row)
		{
			$storefront = $row['storefront'];
			$sum = $row['sum'];

			if (!$storefront || $sum == 1)
			{
				continue;
			}

			if ($sum > 1)
			{
				$this->m_city->exec($unset_all_sql, array('storefront' => $storefront));
			}

			if ($this->getIsDefaultForStorefront() == 1 && $this->getID() && $this->getStorefront() == $storefront)
			{
				$this->m_city->updateById($this->getID(), array('is_default_for_storefront' => 1));
			}
			else
			{
				$this->m_city->exec($set_one_sql, array('storefront' => $storefront));
			}
		}

		return true;
	}
}
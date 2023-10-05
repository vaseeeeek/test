<?php


class shopRegionsRouting
{
	private static $current_city = false;
	private static $ip_city;
	private static $storefronts = null;
	private static $storefront_routes = null;
	private static $ip_analyzer_result = false;

	private $current_category = false;
	private $m_country;
	private $m_regions;

	public function __construct()
	{
		$this->m_country = new waCountryModel();
		$this->m_regions = new waRegionModel();
	}

	public function getCurrentCity()
	{
		return $this->lazyLoadCurrentCity();
	}

	public function getIpCity()
	{
		return $this->lazyLoadIpCity();
	}

	public static function getAllStorefronts()
	{
		if (self::$storefronts === null)
		{
			self::collectAllStorefronts();
		}

		return self::$storefronts;
	}

	public static function getStorefrontRoutes()
	{
		if (self::$storefront_routes === null)
		{
			self::collectAllStorefronts();
		}

		return self::$storefront_routes;
	}

	public static function haveStorefront($storefront)
	{
		if (self::$storefront_routes === null)
		{
			self::collectAllStorefronts();
		}

		return array_key_exists($storefront, self::$storefront_routes);
	}

	/**
	 * @deprecated
	 *
	 * @param $storefront
	 * @return array|null
	 */
	public function getRoute($storefront)
	{
		if (self::$storefront_routes === null)
		{
			self::collectAllStorefronts();
		}

		return ifset(self::$storefront_routes[$storefront]);
	}

	public function getStorefrontRoute($storefront)
	{

	}

	public function getCurrentStorefront()
	{
		$route = $this->getCurrentRoute();

		return $route['domain'] . '/' . ifset($route['url']);
	}

	public function getCurrentRoute()
	{
		$routing = wa()->getRouting();
		$domain = $routing->getDomain();
		$route = $routing->getRoute();

		$route['domain'] = $domain;

		return $route;
	}

	/**
	 * @param shopRegionsCity $city
	 * @param string $url
	 * @deprecated In case of cross-domain redirect lose user environment. Use JavaScript function shopRegions.switchCity()  [/wa-apps/shop/plugins/regions/js/window.js]
	 */
	public function changeSiteRegionAndRedirect($city, $url = null)
	{
		if (is_null($city))
		{
			return;
		}

		$route = $this->getRoute($city->getStorefront());
		$url = is_null($url)
			? $this->getCurrentUrlForRoute($route)
			: $url;

		if ($url && $this->changeStorefrontRegion($city))
		{
			wa()->getResponse()->redirect($url);
		}
	}

	/**
	 * set city as region for it's storefront
	 * @param shopRegionsCity $city
	 * @return bool
	 */
	public function changeStorefrontRegion($city)
	{
		if (is_null($city) || !$_storefront = $city->getStorefront())
		{
			return false;
		}

		$route = new shopRegionsRoute($_storefront, $city->getDomainName(), $city->getRoute());

		$this->setCurrentCityID($city->getID(), $_storefront);
		$this->setDomainLastCurrentCityID($city->getID(), $route->getDomain()->getName());

		return true;
	}

	/**
	 * @param array $route
	 * @return string
	 */
	public function getCurrentUrlForRoute($route)
	{
		$path_parts = array(
			waSystem::getApp(),
			waRequest::param('module') ? waRequest::param('module') : 'frontend',
		);

		if (waRequest::param('action'))
		{
			$path_parts[] = waRequest::param('action');
		}

		$path = implode('/', $path_parts);

		$is_https = array_key_exists('regions_ssl', $route) && $route['regions_ssl'] !== '' && $route['regions_ssl'] !== null
			? $route['regions_ssl'] == 1
			: waRequest::isHttps();

		if ($path === 'shop/frontend/page' || $path === 'shop/frontend' || $path === 'shop/frontend/cart' || $path === 'shop/frontend/order')
		{
			$url = 'http' . ($is_https ? 's' : '') . '://' . $route['domain'] . '/'
				. str_replace('*', wa()->getRouting()->getCurrentUrl(), $route['url']);
		}
		else
		{
			$params = $this->preparePathParamsForRoute($path, $route);
			$url = wa()->getRouting()->getUrl($path, $params, true, ifset($route['domain']), ifset($route['url']));

			$url = $is_https
				? str_replace('http://', 'https://', $url)
				: str_replace('https://', 'http://', $url);
		}

		return $url
			? str_replace(' ', '+', $url)
			: $url;
	}

	public function getIpCityData()
	{
		$result = $this->getIpAnalyzerResult();

		return $result
			? $result->getCityData()
			: null;
	}

	/**
	 * @return null|shopRegionsCity
	 */
	private function lazyLoadCurrentCity()
	{
		if (self::$current_city === false)
		{
			self::$current_city = $this->loadCurrentCity();
		}

		return self::$current_city;
	}

	private function loadCurrentCity()
	{
		$storefront = $this->getCurrentStorefront();
		$region_route = new shopRegionsRoute($storefront);

		$city = null;
		if ($region_route->getApp() != 'shop')
		{
			$id = $this->getDomainLastCurrentCityID($region_route->getDomain()->getName());
		}
		else
		{
			$id = $this->getCurrentCityID($storefront);

			$settings = new shopRegionsSettings();
			if (!$id && $settings->ip_analyzer_enable)
			{
				$ip_city = $this->getIpCity();

				if ($ip_city && $ip_city->getStorefront() === $storefront)
				{
					$id = $ip_city->getID();
					$city = $ip_city;
				}
			}

			if (!$id)
			{
				$city_collection = new shopRegionsCityCollection();
				$city_assoc = $city_collection->getDefaultForStorefront($storefront);

				if ($city_assoc)
				{
					$id = $city_assoc['id'];
				}
			}
		}

		if (!isset($id))
		{
			return null;
		}

		$this->setDomainLastCurrentCityID($id, $region_route->getDomain()->getName());

		return $city ? $city : shopRegionsCity::load($id);
	}

	private function lazyLoadIpCity()
	{
		if (!isset(self::$ip_city))
		{
			$ip_analyzer_result = $this->getIpAnalyzerResult();

			if ($ip_analyzer_result instanceof shopRegionsIpAnalyzerResult && $ip_analyzer_result->getCity())
			{
				$city = shopRegionsCity::build($ip_analyzer_result->getCity());
				self::$ip_city = $city;
			}
		}

		return self::$ip_city;
	}

	/**
	 * @return null|shopRegionsIpAnalyzerResult
	 */
	public function getIpAnalyzerResult()
	{
		if (self::$ip_analyzer_result === false)
		{
			$analyzer = new shopRegionsIpAnalyzer();
			$ip = waRequest::getIp();

			self::$ip_analyzer_result = null;
			if (preg_match('/[0-9\.]+/', $ip, $matches))
			{
				$ip = $matches[0];
				self::$ip_analyzer_result = $analyzer->analyze($ip);
			}

			$event_params = array(
				'ip_analyzer_result' => &self::$ip_analyzer_result
			);
			wa('shop')->event('regions_plugin.ip_analyzer', $event_params);
		}

		return self::$ip_analyzer_result;
	}

	private function getCurrentCityID($storefront)
	{
		$sessionStorage = wa()->getStorage();
		$current_city_id = $sessionStorage->get('current_city_id');
		if (!$current_city_id or !is_array($current_city_id))
		{
			$sessionStorage->set('current_city_id', array());
		}

		return isset($current_city_id[$storefront]) ? $current_city_id[$storefront] : null;
	}

	private function setCurrentCityID($id, $storefront)
	{
		$sessionStorage = wa()->getStorage();
		$currentValue = $sessionStorage->get('current_city_id');
		if (!$currentValue)
		{
			$currentValue = array();
		}

		$currentValue[$storefront] = $id;
		$sessionStorage->set('current_city_id', $currentValue);
	}

	private function getDomainLastCurrentCityID($domain)
	{
		$sessionStorage = wa()->getStorage();
		$last_current_city_id = $sessionStorage->get('last_current_city_id');

		if (!$last_current_city_id)
		{
			$last_current_city_id = array();
		}

		if (isset($last_current_city_id[$domain]))
		{
			if (!shopRegionsCity::isExists($last_current_city_id[$domain]))
			{
				unset($last_current_city_id[$domain]);
			}
		}

		if (!isset($last_current_city_id[$domain]))
		{
			wa('site');
			$domain_model = new siteDomainModel();
			$collection = new shopRegionsCityCollection();

			$city = $collection
				->enabledOnly()
				->join($domain_model->getTableName(), ':table.id = t.domain_id', null, $alias)
				->where($alias . '.name = :domain', array('domain' => $domain))
				->where('t.is_default_for_storefront = 1')
				->getFirst();

			if ($city)
			{
				$last_current_city_id[$domain] = $city['id'];
			}
		}

		$sessionStorage->set('last_current_city_id', $last_current_city_id);

		return ifset($last_current_city_id[$domain]);
	}

	private function setDomainLastCurrentCityID($id, $domain)
	{
		$sessionStorage = wa()->getStorage();
		$last_current_city_id = $sessionStorage->get('last_current_city_id');

		if (!$last_current_city_id)
		{
			$last_current_city_id = array();
		}

		$last_current_city_id[$domain] = $id;

		$sessionStorage->set('last_current_city_id', $last_current_city_id);
	}

	private function getCurrentAppUrlForStorefront($storefront)
	{
		$domain_search = new shopRegionsStorefrontDomain();
		$found_domain = $domain_search->search($storefront);
		$current_url = wa()->getRouting()->getCurrentUrl();
		$current_url = $current_url === false ? '' : $current_url;

		$url = null;
		if ($found_domain)
		{
			$routes = wa()->getRouting()->getRoutes($found_domain);
			$app_url = null;
			foreach ($routes as $i => $route)
			{
				if (ifset($route['app']) === waSystem::getApp())
				{
					$app_url = trim($route['url'], '*');
					break;
				}
			}

			if ($app_url !== null)
			{
				$url = $found_domain . '/' . $app_url . $current_url;
			}
		}

		$protocol = wa()->getRequest()->isHttps() ? 'https://' : 'http://';
		if ($url === null)
		{
			$url = wa()->getRouting()->getDomain() . '/' . wa()->getRouting()->getRootUrl() . $current_url;
		}

		return $protocol . $url;
	}

	private static function collectAllStorefronts()
	{
		$routing = wa()->getRouting();
		$domains = $routing->getByApp('shop');

		$storefronts = array();
		$storefront_routes = array();
		foreach ($domains as $domain => $domain_routes)
		{
			foreach ($domain_routes as $route)
			{
				$storefront = $domain . '/' . ifset($route['url']);
				$storefronts[] = $storefront;

				$storefront_routes[$storefront] = $route;
				$storefront_routes[$storefront]['domain'] = $domain;
			}
		}
		self::$storefronts = $storefronts;
		self::$storefront_routes = $storefront_routes;
	}

	private function preparePathParamsForRoute($path, $route)
	{
		$params = waRequest::param();
		if (is_array($params))
		{
			unset($params['app']);
		}

		$param_url_type = isset($params['url_type']) ? $params['url_type'] : null;
		$route_url_type = isset($route['url_type']) ? $route['url_type'] : null;

		if ($route_url_type !== null)
		{
			$params['url_type'] = $route_url_type;
		}

		// для естественных урлов товара в параметрах нужен урл категории
		if ($path === 'shop/frontend/product' && $route_url_type == 2)
		{
			if ($this->current_category === false)
			{
				$product_model = new shopCategoryModel();
				$category_model = new shopCategoryModel();

				$product = $product_model->getByField('url', ifset($params['product_url']));

				if ($product)
				{
					$this->current_category = $category_model->getById($product['category_id']);
				}
			}

			if ($this->current_category)
			{
				$params['category_url'] = $this->current_category['full_url'];
			}
		}
		elseif ($path === 'shop/frontend/product' && ($route_url_type == 1 || $route_url_type == 0))
		{
			$params['category_url'] = '';
		}
		// замена url на full_url (и наоборот)
		elseif ($path === 'shop/frontend/category' && ((($param_url_type == 1) !== ($route_url_type == 1)) || !isset($params['category_url'])))
		{
			if ($this->current_category === false)
			{
				$category_model = new shopCategoryModel();

				$category = $category_model->getById(ifset($params['category_id']));

				$url_field = $param_url_type == 1 ? 'url' : 'full_url';
				$this->current_category = $category
					? $category
					: $category_model->getByField($url_field, ifset($params['category_url']));
			}

			if ($this->current_category)
			{
				$params['category_url'] = $this->current_category[$route_url_type == 1 ? 'url' : 'full_url'];
			}
		}
		elseif ($path === 'shop/frontend/cart')
		{
			$params = null;
		}

		return $params;
	}
}

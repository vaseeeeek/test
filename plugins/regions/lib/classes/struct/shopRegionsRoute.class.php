<?php


class shopRegionsRoute
{
	/** @var shopRegionsDomain */
	private $domain;
	/** @var string */
	private $storefront;
	/** @var string */
	private $route;

	/**
	 * todo постепенно избавляемся от $storefront в конструкторе
	 *
	 * @param string $storefront
	 * @param string|null $domain
	 * @param string|null $route
	 */
	public function __construct($storefront, $domain = null, $route = null)
	{
		if ($domain === null || $route === null)
		{
			$domain_search = new shopRegionsStorefrontDomain();

			$found_domain = $domain_search->search($storefront);

			$this->domain = new shopRegionsDomain($found_domain);
			$this->storefront = $storefront;
			$this->route = str_replace($found_domain . '/', '', $storefront);
		}
		else
		{
			$this->storefront = $domain . '/' . $route;

			$this->domain = new shopRegionsDomain($domain);
			$this->route = $route;
		}
	}

	public function getUrl()
	{
		$route = $this->getConfigRoute();

		if ($route)
		{
			return wa()->getRouting()->getUrlByRoute($this->getConfigRoute(), $this->domain->getName());
		}
		else
		{
			return null;
		}
	}

	public function getDomain()
	{
		return $this->domain;
	}

	public function getRoute()
	{
		return $this->route;
	}

	public function getPayments()
	{
		return $this->getConfigRouteParam('payment_id');
	}

	public function updatePayments($payments)
	{
		$this->updateConfigRouteParam('payment_id', $payments);
	}

	public function getShipping()
	{
		return $this->getConfigRouteParam('shipping_id');
	}

	public function updateShipping($shipping)
	{
		$this->updateConfigRouteParam('shipping_id', $shipping);
	}

	public function getStock()
	{
		return $this->getConfigRouteParam('stock_id');
	}

	public function getPublicStocks()
	{
		return $this->getConfigRouteParam('public_stocks');
	}

	public function getDropOutOfStock()
	{
		return $this->getConfigRouteParam('drop_out_of_stock');
	}

	public function getCurrency()
	{
		return $this->getConfigRouteParam('currency');
	}

	public function getSsl()
	{
		return $this->getConfigRouteParam('regions_ssl');
	}

	public function updateSsl($ssl)
	{
		$this->updateConfigRouteParam('regions_ssl', $ssl);
	}

	public function updateStock($id)
	{
		$this->updateConfigRouteParam('stock_id', $id);
	}

	public function updatePublicStocks($public_stocks)
	{
		$this->updateConfigRouteParam('public_stocks', $public_stocks);
	}

	public function updateDropOutOfStock($drop_out_of_stock)
	{
		$this->updateConfigRouteParam('drop_out_of_stock', $drop_out_of_stock);
	}

	public function updateCurrency($currency)
	{
		return $this->updateConfigRouteParam('currency', $currency);
	}

	public function getApp()
	{
		return $this->getConfigRouteParam('app');
	}

	public function getPages()
	{
		$m_pages = new shopPageModel();

		return $m_pages->getByField(
			array(
				'domain' => $this->domain->getName(),
				'route' => $this->route,
			),
			true
		);
	}

	public function createClone($new_domain_name, $route, $selected_apps)
	{
		$new_domain_name = strtolower($new_domain_name);
		$route = strtolower($route);

		$domain = $this->cloneDomain($new_domain_name, $route, $selected_apps);

		if ($domain->getId())
		{
			$this->cloneShopPages($domain, $route);
			$this->cloneSitePages($domain);
		}
	}

	public function toArray($include_domain = false, $include_url = false, $include_pages = false)
	{
		$array = array(
			'payment' => $this->getPayments(),
			'shipping' => $this->getShipping(),
			'stock' => $this->getStock(),
			'pages' => $this->getPages(),
			'public_stocks' => $this->getPublicStocks(),
			'drop_out_of_stock' => $this->getDropOutOfStock(),
			'currency' => $this->getCurrency(),
			'regions_ssl' => $this->getSsl(),
		);

		if ($include_url)
		{
			$array['url'] = $this->getUrl();
		}

		if ($include_domain)
		{
			$array['domain'] = array(
				'robots_txt' => $this->domain->getRobotsTxt(),
				'head' => $this->domain->getHead(),
				'name' => $this->domain->getName(),
				'route_index' => $this->domain->getIndexRouteByUrl($this->getRoute()),
			);
		}

		if ($include_pages)
		{
			$m_page = new shopPageModel();
			$_pages = $m_page->getByField(array(
				'domain' => $this->getDomain()->getName(),
				'route' => $this->getRoute(),
			), true);

			$pages = array();

			foreach ($_pages as $page)
			{
				$pages[] = array(
					'id' => $page['id'],
					'name' => $page['name'],
					'url' => $page['url'],
					'full_url' => $page['full_url'],
				);
			}

			$array['pages'] = $pages;
		}

		return $array;
	}

	/**
	 * @param $new_domain_name
	 * @param $route_url
	 * @param array $selected_apps
	 * @return shopRegionsDomain
	 */
	private function cloneDomain($new_domain_name, $route_url, $selected_apps)
	{
		if ($new_domain_name == $this->domain->getName())
		{
			$routes = $this->domain->getRoutes();

			$is_found_route = false;

			foreach ($routes as $i => $route)
			{
				if ($route['app'] == 'shop' && $route['url'] == $route_url)
				{
					$is_found_route = true;
					$config = $this->getConfigRoute();
					$config['url'] = $route_url;
					$routes[$i] = $config;
					break;
				}
			}

			if (!$is_found_route)
			{
				$config = $this->getConfigRoute();
				$config['url'] = $route_url;
				$routes[] = $config;
			}

			$this->domain->updateRoutes($routes);

			return $this->domain;
		}
		else
		{
			$new_domain = $this->domain->createClone($new_domain_name, $selected_apps);
			$routes = $new_domain->getRoutes();

			foreach ($routes as $i => $route)
			{
				if ($route['app'] == 'shop')
				{
					unset($routes[$i]);
				}
			}

			$config = $this->getConfigRoute();
			$config['url'] = $route_url;
			$routes[] = $config;

			$new_domain->updateRoutes($routes);

			return $new_domain;
		}
	}

	/**
	 * @param shopRegionsDomain $new_domain
	 * @param $new_route
	 */
	private function cloneShopPages($new_domain, $new_route)
	{
		$m_page = new shopPageModel();
		$m_shop_page_params = new shopPageParamsModel();

		$domain_name = $this->domain->getName();
		$new_domain_name = $new_domain->getName();

		$queue = $m_page->select('*')
			->where('domain = :domain', array('domain' => $domain_name))
			->where('route = :route', array('route' => $this->route))
			->where('(parent_id IS NULL OR parent_id = 0)')
			->fetchAll();

		$old_to_new_page_id_map = array();

		while (count($queue) > 0)
		{
			$page = array_shift($queue);
			$old_page_id = $page['id'];
			$old_page_parent_id = $page['parent_id'];

			$new_page = $page;

			$new_page['id'] = null;
			$new_page['domain'] = $new_domain_name;
			$new_page['route'] = $new_route;
			$new_page['parent_id'] = $old_page_parent_id > 0 && isset($old_to_new_page_id_map[$old_page_parent_id])
				? $old_to_new_page_id_map[$old_page_parent_id]
				: null;

			$new_page_id = $m_page->insert($new_page);

			$old_to_new_page_id_map[$old_page_id] = $new_page_id;

			$params = $m_shop_page_params->getByField('page_id', $old_page_id, true);
			foreach ($params as $param)
			{
				$param['page_id'] = $new_page_id;
				$m_shop_page_params->insert($param);
			}


			$page_children = $m_page->select('*')
				->where('domain = :domain', array('domain' => $domain_name))
				->where('route = :route', array('route' => $this->route))
				->where('parent_id = :parent_id', array('parent_id' => $old_page_id))
				->fetchAll();
			$queue = array_merge($queue, $page_children);
		}
	}

	/**
	 * @param shopRegionsDomain $new_domain
	 */
	private function cloneSitePages($new_domain)
	{
		wa('site');
		$m_site_page = new sitePageModel();
		$m_site_page_params = new sitePageParamsModel();

		$domain_id = $this->domain->getId();
		$new_domain_id = $new_domain->getId();

		$queue = $m_site_page->select('*')
			->where('domain_id = :domain_id', array('domain_id' => $domain_id))
			->where('(parent_id IS NULL OR parent_id = 0)')
			->fetchAll();

		$old_to_new_page_id_map = array();

		while (count($queue) > 0)
		{
			$page = array_shift($queue);
			$old_page_id = $page['id'];
			$old_page_parent_id = $page['parent_id'];

			$new_page = $page;

			$new_page['id'] = null;
			$new_page['domain_id'] = $new_domain_id;
			$new_page['parent_id'] = $old_page_parent_id > 0 && isset($old_to_new_page_id_map[$old_page_parent_id])
				? $old_to_new_page_id_map[$old_page_parent_id]
				: null;

			$new_page_id = $m_site_page->insert($new_page);

			$old_to_new_page_id_map[$old_page_id] = $new_page_id;

			$params = $m_site_page_params->getByField('page_id', $old_page_id, true);
			foreach ($params as $param)
			{
				$param['page_id'] = $new_page_id;
				$m_site_page_params->insert($param);
			}


			$page_children = $m_site_page->select('*')
				->where('domain_id = :domain_id', array('domain_id' => $domain_id))
				->where('parent_id = :parent_id', array('parent_id' => $old_page_id))
				->fetchAll();
			$queue = array_merge($queue, $page_children);
		}
	}

	private function getConfigRoute()
	{
		return $this->domain->getConfigRoute('shop', $this->route);
	}

	private function updateConfigRoute($config)
	{
		$this->domain->updateConfigRoute('shop', $this->route, $config);
	}

	private function getConfigRouteParam($name)
	{
		$route = $this->getConfigRoute();

		return ifset($route[$name]);
	}

	private function updateConfigRouteParam($name, $value)
	{
		$route = $this->getConfigRoute();
		$route[$name] = $value;
		$this->updateConfigRoute($route);
	}
}
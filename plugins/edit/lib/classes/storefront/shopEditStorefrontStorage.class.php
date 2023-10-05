<?php

class shopEditStorefrontStorage
{
	private $routes;

	public function __construct()
	{
		$this->routes = wa()->getRouting()->getByApp('shop');
	}

	/**
	 * @param string $app_id
	 * @return shopEditStorefront[]
	 */
	public function getAllAppStorefronts($app_id)
	{
		$storefronts = array();

		foreach (wa()->getRouting()->getByApp($app_id) as $domain => $routes)
		{
			foreach ($routes as $route)
			{
				$storefronts[] = new shopEditStorefront($domain, $route);
			}
		}

		return $storefronts;
	}

	/**
	 * @param string $app_id
	 * @param string[] $storefront_ids
	 * @return shopEditStorefront[]
	 */
	public function getAppStorefronts($app_id, $storefront_ids)
	{
		$_ids = array();
		foreach ($storefront_ids as $id)
		{
			$_ids[$id] = $id;
		}

		$storefronts = array();

		foreach (wa()->getRouting()->getByApp($app_id) as $domain => $routes)
		{
			foreach ($routes as $route)
			{
				if (!array_key_exists('url', $route))
				{
					continue;
				}

				$storefront_id = $domain . '/' . $route['url'];
				if (!array_key_exists($storefront_id, $_ids))
				{
					continue;
				}

				$storefronts[] = new shopEditStorefront($domain, $route);
			}
		}

		return $storefronts;
	}

	/**
	 * @param string $app_id
	 * @param shopEditStorefront[] $storefronts
	 * @return bool
	 */
	public function updateAppStorefronts($app_id, $storefronts)
	{
		if (count($storefronts) == 0)
		{
			return true;
		}

		$routing_path = wa()->getConfigPath() . '/routing.php';
		if (!file_exists($routing_path))
		{
			return false;
		}

		$routing = include($routing_path);
		if (!is_array($routing))
		{
			return false;
		}

		foreach ($storefronts as $storefront)
		{
			if (!array_key_exists($storefront->domain, $routing) || !is_array($routing[$storefront->domain]))
			{
				continue;
			}

			foreach ($routing[$storefront->domain] as &$route)
			{
				if (
					array_key_exists('app', $route) && $route['app'] == $app_id
					&& array_key_exists('url', $route) && $route['url'] == $storefront->url
				)
				{
					foreach ($storefront->route as $key => $value)
					{
						$route[$key] = $value;
					}

					break;
				}
			}
		}

		waUtils::varExportToFile($routing, $routing_path);

		return true;
	}


	public function getAllShopStorefronts()
	{
		return $this->getAllAppStorefronts('shop');
	}

	public function getShopStorefronts($ids)
	{
		return $this->getAppStorefronts('shop', $ids);
	}

	/**
	 * @param shopEditStorefront[] $storefronts
	 * @return bool
	 */
	public function updateShopStorefronts($storefronts)
	{
		return $this->updateAppStorefronts('shop', $storefronts);
	}
}
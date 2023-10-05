<?php

class shopBrandStorefront
{
	const GENERAL = '*';

	private static $storefronts = null;

	public static function getAll()
	{
		if (self::$storefronts === null)
		{
			self::$storefronts = array();

			$routing = wa()->getRouting();
			$domains = $routing->getByApp('shop');

			foreach ($domains as $domain => $routes)
			{
				foreach ($routes as $route)
				{
					if ((!method_exists($routing, 'isAlias') || !$routing->isAlias($domain)) and isset($route['url']))
					{
						self::$storefronts[] = $domain . '/' . $route['url'];
					}
				}
			}
		}

		return self::$storefronts;
	}

	public static function getCurrent()
	{
		$routing = wa()->getRouting();
		$route = $routing->getRoute();
		$domain = $routing->getDomain();

		if(wa()->getEnv() === 'cli') {
		    $domain = waRequest::server('HTTP_HOST');
        }

		return $domain . '/' . $route['url'];
	}
}
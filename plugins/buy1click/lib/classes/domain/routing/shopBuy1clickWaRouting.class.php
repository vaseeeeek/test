<?php


class shopBuy1clickWaRouting implements shopBuy1clickRouting
{
	private $routing;
	
	public function __construct(waRouting $routing)
	{
		$this->routing = $routing;
	}
	
	public function getAllStorefronts()
	{
		$domains = $this->routing->getByApp(shopBuy1clickPlugin::SHOP_ID);
		$urls = array();
		
		foreach ($domains as $domain => $routes)
		{
			foreach ($routes as $route)
			{
				if ((!method_exists($this->routing, 'isAlias') || !$this->routing->isAlias($domain)) and isset($route['url']))
				{
					$urls[] = $domain . '/' . $route['url'];
				}
			}
		}
		
		return $urls;
	}
}

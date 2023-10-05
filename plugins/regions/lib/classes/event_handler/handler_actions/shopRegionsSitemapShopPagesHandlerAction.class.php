<?php

class shopRegionsSitemapShopPagesHandlerAction implements shopRegionsIHandlerAction
{
	public function execute($route)
	{
		$settings = new shopRegionsSettings();

		$domain = wa()->getRouting()->getDomain(null, true, false);
		$storefront = $domain . '/' . $route['url'];

		$active_pages = $settings->getActivePageUrls($storefront);

		$shop_page_model = new shopPageModel();
		$storefront_pages = $shop_page_model
			->select('full_url, url, create_datetime, update_datetime')
			->where('status = 1')
			->where('domain = s:domain', array('domain' => $domain))
			->where('route = s:route', array('route' => $route['url']))
			->fetchAll('full_url');

		$main_url = wa()->getRouting()->getUrl('shop/frontend', array(), true, $domain);

		$urls = array();
		foreach ($active_pages as $page)
		{
			if (isset($storefront_pages[$page['url']]))
			{
				continue;
			}


			$urls[] = array(
				'loc' => $main_url . $page['url'],
				'lastmod' => time(),
				'changefreq' => waSitemapConfig::CHANGE_MONTHLY,
				'priority' => 0.6,
			);
		}

		return $urls;
	}
}
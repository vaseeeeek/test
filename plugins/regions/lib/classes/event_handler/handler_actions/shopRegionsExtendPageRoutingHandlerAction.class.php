<?php

class shopRegionsExtendPageRoutingHandlerAction implements shopRegionsIHandlerAction
{
	public function execute($plugin_routes)
	{
		$settings = new shopRegionsSettings();

		$region_routing = new shopRegionsRouting();
		$storefront = $region_routing->getCurrentStorefront();

		$active_pages = $settings->getActivePageUrls($storefront);

		$page_routes = array();
		$ignore_default_page = array();
		foreach ($active_pages as $page)
		{
			$page_url = trim($page['url']);

			if (strlen($page_url))
			{
				$page_routes['<regions_page_url:' . $page_url . '>'] = 'frontend/page';
				$ignore_default_page[$page_url] = $page['ignore_default'] == 'Y';
			}
		}

		waRequest::setParam('regions_ignore_default_pages', $ignore_default_page);

		return $page_routes;
	}
}
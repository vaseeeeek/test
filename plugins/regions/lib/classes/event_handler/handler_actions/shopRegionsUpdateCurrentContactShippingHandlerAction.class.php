<?php

class shopRegionsUpdateCurrentContactShippingHandlerAction implements shopRegionsIHandlerAction
{
	public function execute($plugin_routes)
	{
		$regions_routing = new shopRegionsRouting();
		$current_city = $regions_routing->getCurrentCity();

		$settings = new shopRegionsSettings();
		if (wa()->getEnv() != 'frontend' || waRequest::getMethod() == 'post' || !$settings->auto_select_city_enable)
		{
			return;
		}

		$shipping_city = $current_city;
		$regions_routing = new shopRegionsRouting();

		$confirm_cookie = waRequest::cookie('shop_regions_confirm');

		$current_city_is_confirmed = $confirm_cookie === null || $confirm_cookie != $current_city->getID();
		$current_city_is_not_full = !$current_city->getName() || !$current_city->getRegionCode() || !$current_city->getCountryIso3();

		if ($settings->ip_analyzer_enable && ($current_city_is_confirmed || $current_city_is_not_full) && ($ip_analyzer_result = $regions_routing->getIpAnalyzerResult()))
		{
			$ip_city = shopRegionsCity::buildByIpAnalyzerResult($ip_analyzer_result);

			$ip_city_has_different_name = $ip_city && mb_strtolower($ip_city->getName()) !== mb_strtolower($current_city->getName());

			if ($ip_city && ($current_city_is_not_full || $ip_city_has_different_name))
			{
				$shipping_city = $ip_city;
			}
		}

		shopRegionsCurrentContact::updateShipping($shipping_city);
	}
}
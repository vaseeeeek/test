<?php

class shopProductgroupWaPluginEnvFactory implements shopProductgroupPluginEnvFactory
{
	public function createPluginEnv()
	{
		$wa_routing = wa()->getRouting();
		$domain = $wa_routing->getDomain();
		$route = $wa_routing->getRoute();

		$theme_id = waRequest::isMobile() && waRequest::param('theme_mobile')
			? waRequest::param('theme_mobile')
			: waRequest::param('theme');

		$route_url = ifset($route, 'url', '');
		$storefront = "{$domain}/{$route_url}";

		$plugin_config_storage = new shopProductgroupPluginConfigStorage();
		$style_config_storage = new shopProductgroupStyleConfigStorage();

		return new shopProductgroupPluginEnv(
			$theme_id,
			$storefront,
			$plugin_config_storage->getConfig($storefront),
			$style_config_storage->getConfig($theme_id, $storefront)
		);
	}
}
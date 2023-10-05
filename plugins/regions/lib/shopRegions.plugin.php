<?php

class shopRegionsPlugin extends shopPlugin
{
	const EDIT_RIGHT_NAME = 'regions.edit_region';

	public function seoFetchTemplatesHandler($view_vars)
	{
		$handler = new shopRegionsFetchTemplateEventHandler($view_vars);

		return $handler->handle();
	}

	public function seoFetchTemplateHelperHandler()
	{
		return shopRegionsPluginVariablesMeta::getMeta();
	}

	public function frontendHeadHandler()
	{
		$handler = new shopRegionsFrontendHeadEventHandler();

		return $handler->handle();
	}

	public function cartAddHandler()
	{
		self::saveUserEnvironment(); // для сохранения куки корзины
	}

	public function backendProductsHandler($params)
	{
		$handler = new shopRegionsBackendProductsEventHandler($params);

		return $handler->handle();
	}

	public function backendMenuHandler()
	{
		$handler = new shopRegionsBackendMenuEventHandler();

		return $handler->handle();
	}

	public function rightsConfigHandler(waRightConfig $config)
	{
		$config->addItem('regions_header', 'SEO-регионы', 'header', array('cssclass' => 'c-access-subcontrol-header', 'tag' => 'div'));
		$config->addItem(shopRegionsPlugin::EDIT_RIGHT_NAME, 'Редактирование регионов', 'checkbox', array('cssclass' => 'c-access-subcontrol-item'));
	}

	public function sitemapHandler($route)
	{
		$handler = new shopRegionsSitemapEventHandler($route);

		return $handler->handle();
	}

	public function routing($route = array())
	{
		if (wa()->getEnv() !== 'frontend')
		{
			return array();
		}

		//$autoload = waAutoload::getInstance();
		//$autoload->add('shopFrontendPageAction', "wa-apps/shop/plugins/regions/lib/classes/shopFrontendPage.action.php");


		$plugin_routes = parent::routing($route);

		$current_page_is_sitemap = preg_match('/^sitemap.*\.xml$/', wa()->getRouting()->getCurrentUrl());

		$current_city = null;

		if (!$current_page_is_sitemap)
		{
			$regions_routing = new shopRegionsRouting();
			$current_city = $regions_routing->getCurrentCity();
		}

		if ($current_page_is_sitemap || !$current_city || !$current_city->getID())
		{
			return $plugin_routes;
		}

		$handler = new shopRegionsRoutingEventHandler($plugin_routes);

		return $handler->handle();
	}

	public function handleCheckoutBeforeRegion($params)
	{
		if (ifset($params, 'data', 'origin', null) === 'calculate')
		{
			$response = wa()->getResponse();
			$response->setCookie('shop_region_remember_address', '1', strtotime('+200 days'));
		}
	}

	public function handleAppSitemapStructure()
	{
		return array(
			'is_shown' => false,
		);
	}

	public static function userHasRightsToEditRegions($user = null)
	{
		if ($user === null)
		{
			$user = wa()->getUser();
		}

		return $user->getRights('shop', shopRegionsPlugin::EDIT_RIGHT_NAME) != 0;
	}

	public static function getVariablesTemplatePath()
	{
		$plugin = wa('shop')->getPlugin('regions');

		return $plugin->path . '/templates/Variables.html';
	}

	public static function getPath()
	{
		$plugin = wa('shop')->getPlugin('regions');

		return $plugin->path;
	}

	public static function push()
	{
		waLocale::loadByDomain(array('shop', 'regions'));
		waSystem::pushActivePlugin('seo', 'regions');
	}

	public static function pop()
	{
		waSystem::popActivePlugin();
	}

	public static function saveUserEnvironment()
	{
		$cookie_key = wa()->getRequest()->cookie('shop_regions_env_key', false);

		$user_environment_model = new shopRegionsUserEnvironmentModel();

		$key = $cookie_key ? $cookie_key : $user_environment_model->generateKey();
		wa()->getResponse()->setCookie('shop_regions_env_key', $key, 0, '/');

		$frontendActions = new shopRegionsPluginFrontendActions();
		$frontendActions->saveUserEnvironment($key);
	}
}
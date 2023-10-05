<?php

class shopRegionsPluginFrontendActions extends waPluginsActions
{
	public function restoreUserEnvironmentAction()
	{
		$key = waRequest::request('key');
		$city_id = waRequest::request('city_id');
		if (is_null($key))
		{
			return;
		}

		if (is_null($city_id) || is_null($city = shopRegionsCity::load($city_id)))
		{
			$this->displayJson(array(
				'error' => "Cant find city with id [{$city_id}]"
			));

			return;
		}

		$user_environment_model = new shopRegionsUserEnvironmentModel();
		$cookies = $user_environment_model->loadUserEnvironment($key);

		$storage = wa()->getStorage();
		$storage_options = $storage->getOptions();

		$session_name = ifset($storage_options['session_name'], session_name());

		if (array_key_exists($session_name, $cookies))
		{
			$stored_session_id = $cookies[$session_name];

			if (session_id() != $stored_session_id)
			{
				$storage->destroy();
				session_id($stored_session_id);
				$storage->init(array(
					'session_id' => $stored_session_id,
				));
				$storage->open();
			}
		}

		$routing = new shopRegionsRouting();
		$routing->changeStorefrontRegion($city);

		unset($cookies['shop_region_remember_address']);
		foreach ($cookies as $name => $value)
		{
			$this->getResponse()->setCookie($name, $value);
		}
		$this->getResponse()->setCookie('shop_regions_env_key', $key, 0, '/');

		$this->getResponse()->setCookie('shop_regions_confirm', $city_id, strtotime('+200 days'));
	}

	/**
	 * через два редиректа
	 */
	public function changeRegionAction()
	{
		$city_id = waRequest::param('city_id', 0, waRequest::TYPE_INT);

		if (is_null($city_id) || is_null($city = shopRegionsCity::load($city_id)))
		{
			return;
		}

		$routing = new shopRegionsRouting();
		$current_storefront = $routing->getCurrentStorefront();
		$proto = waRequest::isHttps() ? 'https://' : 'http://';

		$city_storefront = $city->getStorefront();
		if ($current_storefront != $city_storefront)
		{
			$route = new shopRegionsRoute($city_storefront, $city->getDomainName(), $city->getRoute());
			$url = $proto . $route->getDomain()->getName() . wa('shop')->getRouteUrl('shop/frontend/changeRegion', array('city_id' => $city->getID()));
		}
		else
		{
			$url = $proto . trim($city_storefront, '*');
		}

		$routing->changeSiteRegionAndRedirect($city, $url);
	}

	public function getRedirectUrlAction()
	{
		$url = waRequest::post('url', '');
		$city_id = waRequest::post('city_id');

		$city = shopRegionsCity::load($city_id);

		if (!strlen($url) || !$city)
		{
			$restore_user_environment_url = '';
			if ($city)
			{
				$route_params = array(
					'module' => 'frontend',
					'plugin' => 'regions',
					'action' => 'restoreUserEnvironment',
				);
				$restore_user_environment_url = wa('shop')->getRouting()->getUrl('shop', $route_params, true, $city->getDomainName(), $city->getRoute());
			}

			$this->displayJson(array('redirect_url' => '', 'restore_user_environment_url' => $restore_user_environment_url));
			return;
		}

		$_SERVER['REQUEST_URI'] = $url;

		$params = array();
		waRequest::setParam($params);

		wa()->getRouting()->dispatch();

		$app = waRequest::param('app');

		$regions_routing = new shopRegionsRouting();
		$route = $shop_route = $city->getShopRoute();

		if ($app !== 'shop')
		{
			$routing = wa($app)->getRouting();
			$new_route_domain = $shop_route['domain'];
			$app_routes = $routing->getByApp($app, $new_route_domain);

			if (count($app_routes) == 0)
			{
				$app_routes = $routing->getByApp($app, $new_route_domain);

				if (count($app_routes) == 0)
				{
					$this->displayJson(array('redirect_url' => ''));
					return;
				}
			}

			$route = null;

			$blog_url_type = waRequest::param('blog_url_type');
			if ($app === 'blog' && $blog_url_type > 0)
			{
				foreach (array_reverse($app_routes) as $possible_route)
				{
					if (array_key_exists('blog_url_type', $possible_route) && $possible_route['blog_url_type'] == $blog_url_type)
					{
						$route = $possible_route;
						$route['domain'] = $new_route_domain;

						break;
					}
				}
			}

			if ($route === null)
			{
				$route = end($app_routes);
				$route['domain'] = $new_route_domain;
			}
		}

		$redirect_url = $regions_routing->getCurrentUrlForRoute($route);

		$route_params = array(
			'module' => 'frontend',
			'plugin' => 'regions',
			'action' => 'restoreUserEnvironment',
		);
		$restore_user_environment_url = wa('shop')->getRouting()->getUrl('shop', $route_params, true, $city->getDomainName(), $city->getRoute());

		wa()->getResponse()->addHeader('Cache-Control', 'no-store');
		$this->displayJson(array(
			'redirect_url' => $redirect_url,
			'restore_user_environment_url' => $restore_user_environment_url,
		));
	}

	/**
	 * @param null $key
	 * @return null|string
	 */
	public function saveUserEnvironment($key = null)
	{
		$storage = wa()->getStorage();
		$storage_options = $storage->getOptions();

		$session_name = isset($storage_options['session_name'])
			? $storage_options['session_name']
			: session_name();

		$cookies = waRequest::cookie();
		$cookies[$session_name] = session_id();

		$user_environment_model = new shopRegionsUserEnvironmentModel();

		return $user_environment_model->saveUser($cookies, $key);
	}
}
<?php


class shopRegionsPluginDataActions extends waJsonActions
{
	protected function preExecute()
	{
		if (!shopRegionsPlugin::userHasRightsToEditRegions())
		{
			throw new waException('Доступ запрещен', 403);
		}
	}

	public function regionsAction()
	{
		$country = waRequest::post('value', '');
		$helper = new shopRegionsHelper();

		$this->response = $helper->getRegionsByCountry($country);
	}

	public function storefrontAction()
	{
		$storefront_name = waRequest::post('value', '');

		if ($storefront_name == 'clone')
		{
			$helper = new shopRegionsHelper();

			wa()->getView()->assign('storefronts', $helper->getAllStorefronts());
			wa()->getView()->assign('apps', $this->getAllApps());
			wa()->getView()->assign('selected_apps', array('shop' => true, 'site' => true));
			$this->response['form'] = wa()->getView()->fetch($this->getPluginRoot() . 'templates/StorefrontClone.html');
		}
		else if ($storefront_name != '')
		{
			$domain = waRequest::post('domain', 0);
			$route = waRequest::post('route', '');

			$storefront_settings = new shopRegionsRoute($storefront_name, $domain, $route);
			$this->response = $storefront_settings->toArray(true, false, true);
		}
		else
		{
			$this->response = array();
		}
	}

	public function cloneAction()
	{
		$new_domain_name = strtolower(trim(waRequest::post('new_domain', '')));
		$new_route = strtolower(trim(waRequest::post('new_route', '')));
		$storefront = trim(waRequest::post('storefront', ''));
		$selected_apps = waRequest::post('selected_apps', array());
		$new_route = trim($new_route);

		if ($new_route == '')
		{
			$new_route = '*';
		}
		elseif ($new_route != '*' && substr($new_route, -1) != '*')
		{
			if (substr($new_route, -1) == '/')
			{
				$new_route .= '*';
			}
			else
			{
				$new_route .= '/*';
			}
		}

		$new_storefront = $new_domain_name . '/' . $new_route;
		$routing = new shopRegionsRouting();
		$storefront_routes = shopRegionsRouting::getStorefrontRoutes();

		$this->response['new_storefront'] = null;

		//if (in_array($storefront, $storefront_routes) && !in_array($new_storefront, $storefront_routes))
		if (array_key_exists($storefront, $storefront_routes) && !array_key_exists($new_storefront, $storefront_routes))
		{
			//$storefront = new shopRegionsRoute($storefront);
			$storefront = new shopRegionsRoute($storefront, $storefront_routes[$storefront]['domain'], $storefront_routes[$storefront]['url']);
			$storefront->createClone($new_domain_name, $new_route, $selected_apps);
			$storefront_routes[] = $new_storefront;
			$this->response['new_storefront'] = $new_storefront;
		}

		$helper = new shopRegionsHelper();
		$this->response['storefronts'] = $helper->getAllStorefronts(true);
	}

	public function deleteCityAction()
	{
		$id = waRequest::request('id', null);

		if (!shopRegionsCity::isExists($id))
		{
			throw new waException("Region not found", 404);
		}

		$city = shopRegionsCity::load($id);
		$city->delete();
	}

	public function uploadFilesAction()
	{
		$domain = waRequest::post('domain', '');

		if (!empty($domain))
		{
			$favicon = waRequest::file('favicon');
			if ($favicon->uploaded()) {
				if ($favicon->extension !== 'ico')
				{
					$this->errors = _w('Files with extension *.ico are allowed only.');
				}
				else
				{
					$path = wa('site')->getDataPath('data/'.$domain.'/', true);
					if (!file_exists($path) || !is_writable($path))
					{
						$this->errors = sprintf(_w('File could not be saved due to the insufficient file write permissions for the "%s" folder.'), 'wa-data/public/site/data/'.siteHelper::getDomain());
					}
					elseif (!$favicon->moveTo($path, 'favicon.ico'))
					{
						$this->errors = _w('Failed to upload file.');
					}
				}
			}
			else
			{
				try
				{
					if ($favicon->error_code != UPLOAD_ERR_NO_FILE)
					{
						$this->errors = $favicon->error;
					}
				}
				catch (waException $e)
				{}
			}

			$touch_icon = waRequest::file('touchicon');
			if ($touch_icon->uploaded())
			{
				if ($touch_icon->extension !== 'png')
				{
					$this->errors = _w('Files with extension *.png are allowed only.');
				}
				else
				{
					$path = wa('site')->getDataPath('data/'.$domain.'/', true);
					if (!file_exists($path) || !is_writable($path))
					{
						$this->errors = sprintf(_w('File could not be saved due to the insufficient file write permissions for the "%s" folder.'), 'wa-data/public/site/data/'.siteHelper::getDomain());
					}
					elseif (!$touch_icon->moveTo($path, 'apple-touch-icon.png'))
					{
						$this->errors = _w('Failed to upload file.');
					}
				}
			}
			else
			{
				try
				{
					if (!$touch_icon || $touch_icon->error_code != UPLOAD_ERR_NO_FILE)
					{
						$this->errors = $touch_icon->error;
					}
				}
				catch (waException $e)
				{}
			}
		}
	}

	public function massEditAction()
	{
		$action = waRequest::post('action');
		$ids = waRequest::post('region_ids', array());

		$m_city = new shopRegionsCityModel();
		$success = true;

		switch ($action)
		{
			case 'enable':
				$success = $m_city->enableById($ids);
				break;
			case 'disable':
				$success = $m_city->disableById($ids);
				break;
			case 'makePopular':
				$success = $m_city->makePopularById($ids);
				break;
			case 'makeUnpopular':
				$success = $m_city->makeUnpopularById($ids);
				break;
			case 'delete':
				$success = shopRegionsCity::deleteById($ids);
				break;
			case 'clone':
				$success = $m_city->cloneById($ids);
				break;
		}

		if (!$success)
		{
			$this->errors = '';
		}
	}

	public function sortRegionsAction()
	{
		$new_order = waRequest::post('order', array());
		$offset = waRequest::post('offset', 0);

		$m_city = new shopRegionsCityModel();
		$m_city->updateCustomOrder($new_order, $offset);
	}

	private function getAllApps()
	{
		$apps = array();

		foreach (wa()->getApps() as $app)
		{
			$app_id = $app['id'];
			if (!ifset($app['frontend'], false) || $app_id === 'logs')
			{
				continue;
			}

			$app_icon = $app['icon'][24];
			$app_name = $app['name'];
			$apps[$app_id] = array(
				'name' => $app_name,
				'icon' => '/' . $app_icon,
			);
		}

		return $apps;
	}
}
<?php

class shopRegionsPluginBackendActions extends waViewActions
{
	public function preExecute()
	{
		if (!shopRegionsPlugin::userHasRightsToEditRegions())
		{
			throw new waException('Доступ запрещен', 403);
		}

		$layout = new shopRegionsBackendLayout();
		$layout->assign('no_level2', true);
		$this->setLayout($layout);

		$settings_are_available = wa()->getUser()->getRights('shop', 'settings') != 0;
		$this->view->assign('settings_are_available', $settings_are_available);

		$this->initSidebar();
	}

	public function initSidebar()
	{
		$plugin_root = wa()->getAppPath($this->getPluginRoot(), 'shop');
		$this->view->assign('action', waRequest::get('action', 'default'));
		$city_collection = new shopRegionsCityCollection();
		if (waRequest::get('id'))
		{
			$this->view->assign('city_id', waRequest::get('id'));
		}
		$this->view->assign('count_cities', $city_collection->count());
		$sidebar = $this->view->fetch($plugin_root.'/templates/Sidebar.html');
		$this->view->assign('sidebar', $sidebar);
	}

	public function defaultAction()
	{
		shopRegionsPlugin::saveUserEnvironment();
		shopRegionsPlugin::push();

		$this->addCss(array(
			'general.css',
			'shop.css',
			'list.css',
			'select2.css',
		));
		$this->addJs(array(
			'select2.full.min.js',
			'list.js',
		));

		list($sort, $order) = $this->initListGetParameters();
		list($filter, $filter_partial) = $this->initFilterGetParameters();
		$page = waRequest::get('page', 1, waRequest::TYPE_INT);

		shopRegionsPagination::setItemsPerPage(waRequest::get('limit', null, waRequest::TYPE_INT));
		$limit = shopRegionsPagination::itemsPerPage();

		$plugin_root = wa()->getAppPath($this->getPluginRoot(), 'shop');
		$mass_actions_sidebar = $this->view->fetch($plugin_root . '/templates/MassActionsSidebar.html');
		$this->view->assign('mass_actions_sidebar', $mass_actions_sidebar);

		$city_collection = new shopRegionsCityCollection();

		wa('site');
		$domain_model = new siteDomainModel();

		$city_collection
			->leftJoin($domain_model->getTableName(), ':table.id = t.domain_id', null, $domain_alias)
			->select("t.*, {$domain_alias}.name domain_name, {$domain_alias}.title domain_title")
			->leftJoinRegion($region_alias, $country_alias)
			->filter($this->prepareFilterForCollection($filter))
			->filterPartial($filter_partial);

		$total_count = $city_collection->count();

		if ($limit != 0)
		{
			$city_collection->limit($limit, $limit * ($page - 1));
		}

		$collection_sort = $sort;
		if ($sort == 'country_name')
		{
			$collection_sort = $country_alias . '.name';
		}
		if ($sort == 'region_name')
		{
			$collection_sort = $region_alias . '.name';
		}
		$city_collection->orderBy($collection_sort, $order);

		$cities_assoc = $city_collection->getCities();

		$domain_name = null;
		$route_url = null;
		foreach ($cities_assoc as $i => $city)
		{
			if (!$domain_name || strlen($route_url))
			{
				$domain_name = $city['domain_name'];
				$domain_title = $city['domain_title'];
				$route_url = $city['route'];

				$cities_assoc[$i]['storefront_title'] = ($domain_title ? $domain_title . '/' . $route_url : $city['storefront']);
			}

			$cities_assoc[$i]['change_region_url'] = wa('shop')->getRouteUrl('shop/frontend', array('city_id' => $city['id']), true, $domain_name, $route_url);
		}

		$this->assignSort($sort, $order);
		$this->assignFilters($filter, $filter_partial);
		$this->assignIsSingleForStorefront($cities_assoc);
		$this->assignPagination($total_count, $page);

		$this->view->assign('cities', $cities_assoc);
		$has_regions = !empty($cities_assoc);

		$this->view->assign(array(
			'cities_list' => $this->view->fetch($plugin_root . '/templates/actions/regions/default/TableRows.html'),
			'has_regions' => $has_regions,
			'limit' => $limit,
		));

		$restore_url = wa('shop')->getRouteUrl('shop/frontend/restoreUserEnvironment', array(), true, $domain_name, $route_url);
		$this->view->assign('trigger_environment_restore_url', $restore_url);

		shopRegionsPlugin::pop();
	}

	public function editAction()
	{
		shopRegionsPlugin::push();

		if (waRequest::post('is_submit', false))
		{
			$this->ajaxEditAction();
		}

		$id = waRequest::get('id', null);

		$city = $this->getCityById($id);
		if (!$city)
		{
			shopRegionsPlugin::pop();
			throw new waException("City not found", 404);
		}

		$this->prepareCityForm($city);

		shopRegionsPlugin::pop();
	}

	public function cloneAction()
	{
		shopRegionsPlugin::push();

		if (waRequest::post('is_submit', false))
		{
			$this->ajaxEditAction();
		}

		$id = waRequest::get('id', null);

		$city = $this->getCityById($id);
		if (!$city)
		{
			shopRegionsPlugin::pop();
			throw new waException("City not found", 404);
		}

		$city->setIsDefaultForStorefront(false);

		$this->prepareCityForm($city);

		shopRegionsPlugin::pop();
	}

	public function createAction()
	{
		shopRegionsPlugin::push();

		if (waRequest::post('is_submit', false))
		{
			$this->ajaxCreateAction();
		}

		$this->prepareCityForm(null);

		shopRegionsPlugin::pop();
	}

	public function cleanerAction()
	{
		$cleaner = new shopRegionsCleaner();
		$cleaner->clean();
	}

	/**
	 * @param shopRegionsCity $city
	 */
	private function prepareCityForm($city)
	{
		$this->addCss(array(
			'general.css',
			'shop.css',
			'bs_ui.css',
			'helper.css',
			'variable.css',
			'city-form.css',
		));
		$this->addJs(array(
			'bs_ui.js',
			'variable.js',
			'city-form.js',
			$city ? 'edit.js' : 'create.js'
		));

		$settings = new shopRegionsSettings();
		$this->view->assign('params', $settings->getParams());



		$helper = new shopRegionsHelper();
		$this->view->assign('storefronts', $helper->getAllStorefronts());
		$this->view->assign('currencies', $helper->getAllCurrencies());
		$this->view->assign('countries', $helper->getAllCountries());
		$this->view->assign('payments', $helper->getAllPayments());
		$this->view->assign('shipping', $helper->getAllShipping());

		list($stocks, $public_stocks) = $helper->getStocks();
		$this->view->assign('stocks', $stocks);
		$this->view->assign('public_stocks', $public_stocks);

		if ($city)
		{
			$this->view->assign('city', $city->toArray(true));
			$storefront_settings = new shopRegionsRoute($city->getStorefront(), $city->getDomainName(), $city->getRoute());

			$this->view->assign('storefront_settings', $storefront_settings->toArray(true));
			$m_page = new shopPageModel();
			$pages = $m_page->getByField(array(
				'domain' => $storefront_settings->getDomain()->getName(),
				'route' => $storefront_settings->getRoute(),
			), true);
			$this->view->assign('storefront_pages', $pages);

			$country_regions = $helper->getRegionsByCountry($city->getCountryIso3());
			$this->view->assign('country_regions', $country_regions);
		}

		$this->assignStorefrontOptions();
	}


	public function robotsAction()
	{
		shopRegionsPlugin::push();
		wa('site');

		$this->addCss(array(
			'general.css',
			'shop.css',
			'bs_ui.css',
			'helper.css',
			'variable.css',
			'robots_edit.css',
		));

		$this->addJs(array(
			'bs_ui.js',
			'variable.js',
			'robots_edit.js',
		));

		$selected_domain = waRequest::get('domain', waRequest::post('domain'));

		$domains = array();
		foreach (wa()->getRouting()->getDomains() as $domain)
		{
			$domain_robots = shopRegionsRobotsFactory::robots($domain);
			$domains[] = array(
				'name' => $domain,
				'selected' => ($domain == $selected_domain ? 'selected="selected"' : ''),
				'is_custom' => $domain_robots instanceof shopRegionsDomainRobots && $domain_robots->isCustom(),
			);
		}

		$selected_robots = shopRegionsRobotsFactory::robots($selected_domain);

		if (waRequest::post('is_submit', false))
		{
			if ($selected_robots instanceof shopRegionsRobotsGlobalTemplate)
			{
				$robots_template_content = waRequest::post('robots_content', '');
				$submitted_domains = waRequest::post('domains', array());

				$selected_robots->saveForDomains($robots_template_content, $submitted_domains);
			}
			else
			{
				$selected_robots->save(waRequest::post('robots_content', ''));
			}
		}

		$robots_content = $selected_robots->getTemplate();
		$placeholder = $robots_content;

		if ($selected_robots instanceof shopRegionsRobotsGlobalTemplate)
		{
			$placeholder = '';
		}
		else if (($selected_robots instanceof shopRegionsDomainRobots) && !$selected_robots->isCustom())
		{
			$robots_content = '';
		}

		$backup = $selected_robots->getTemplateBackup();

		$warning = false;
		$root_robots_path = realpath('./robots.txt');
		if (file_exists($root_robots_path))
		{
			$warning = 'Созданные тут robots.txt выводиться не будут, так как в корне сайта есть robots.txt';
		}

		$this->view->assign(array(
			'domain' => $selected_domain,
			'robots_content' => $robots_content,
			'placeholder' => $placeholder,
			'domains' => $domains,
			'backup' => $backup,
			'warning' => $warning,
		));

		shopRegionsPlugin::pop();
	}

	public function cleanFilesAction()
	{
		try
		{
			$cleaner = new shopRegionsCleaner();
			$cleaner->clean();
			echo "ok";
		}
		catch (waException $e)
		{
			echo '<pre>' . $e->getMessage() . '</pre>';

			if (waSystemConfig::isDebug())
			{
				echo '<pre>' . $e->getFullTraceAsString() . '</pre>';
			}
		}
	}

	public function display()
	{
		if ($this->action !== 'cleanFiles')
		{
			parent::display();
		}
	}

	private function ajaxEditAction()
	{
		$id = waRequest::get('id', null);
		$city = shopRegionsCity::load($id);

		if (!$city)
		{
			return;
		}

		$specific_settings_enabled = waRequest::post('specific_settings_enabled', 0) == '1';

		$input_city_data = waRequest::post('city', array());
		$valid_city_data = $this->validateCityData($input_city_data);
		$this->setCityData($city, $valid_city_data);

		$city->save();

		$settings_model = new shopRegionsCitySettingsModel();
		if ($specific_settings_enabled)
		{
			$input_storefront_data = waRequest::post('storefront_specific', array());
			$valid_storefront_data = $this->validateStorefrontSpecificData($input_storefront_data);

			$settings_model->saveStorefrontSettings($city->getID(), $valid_storefront_data);
		}
		else
		{
			$settings_model->resetStorefrontSettings($city->getID());
		}

		if ($storefront_name = $city->getStorefront())
		{
			$storefront = new shopRegionsRoute($storefront_name);

			$input_storefront_data = waRequest::post('storefront', array());
			$valid_storefront_data = $this->validateStorefrontData($input_storefront_data);
			$this->updateStorefrontData($storefront, $valid_storefront_data);
		}
	}

	private function ajaxCreateAction()
	{
		$city = shopRegionsCity::create();

		$specific_settings_enabled = waRequest::post('specific_settings_enabled', 0) == '1';

		$input_city_data = waRequest::post('city', array());
		$valid_city_data = $this->validateCityData($input_city_data);
		$this->setCityData($city, $valid_city_data);

		$city->save();

		$settings_model = new shopRegionsCitySettingsModel();
		if ($specific_settings_enabled)
		{
			$input_storefront_data = waRequest::post('storefront_specific', array());
			$valid_storefront_data = $this->validateStorefrontSpecificData($input_storefront_data);

			$settings_model->saveStorefrontSettings($city->getID(), $valid_storefront_data);
		}
		else
		{
			$settings_model->resetStorefrontSettings($city->getID());
		}

		if ($storefront_name = $city->getStorefront())
		{
			$storefront = new shopRegionsRoute($storefront_name);

			$input_storefront_data = waRequest::post('storefront', array());
			$valid_storefront_data = $this->validateStorefrontData($input_storefront_data);
			$this->updateStorefrontData($storefront, $valid_storefront_data);
		}
	}

	private function validateCityData($city)
	{
		$_city = $city;
		$_city['region_code'] = (string)ifset($city['region_code'], '');
		$_city['country_iso3'] = (string)ifset($city['country_iso3'], '');
		$_city['name'] = (string)ifset($city['name'], '');
		$_city['is_popular'] = (bool)ifset($city['is_popular'], false);
		$_city['is_enable'] = (bool)ifset($city['is_enable'], false);
		$_city['params'] = (array)ifset($city['params'], array());
		$_city['storefront'] = (string)ifset($city['storefront'], '');
		$_city['phone'] = (string)ifset($city['phone'], '');
		$_city['email'] = (string)ifset($city['email'], '');
		$_city['schedule'] = (string)ifset($city['schedule'], '');
		$_city['is_default_for_storefront'] = (bool)ifset($city['is_default_for_storefront'], false);
		$_city['domain_name'] = (string)ifset($city['domain'], '');
		$_city['route'] = (string)ifset($city['route'], '');

		$routing = new shopRegionsRouting();
		$storefronts = $routing->getAllStorefronts();

		if (!in_array($city['storefront'], $storefronts))
		{
			$_city['storefront'] = '';
		}

		return $_city;
	}

	private function setCityData(shopRegionsCity $city, array $data)
	{
		$city->setRegionCode($data['region_code']);
		$city->setCountryIso3($data['country_iso3']);
		$city->setName($data['name']);
		$city->setPhone($data['phone']);
		$city->setEmail($data['email']);
		$city->setSchedule($data['schedule']);
		$city->setIsPopular($data['is_popular']);
		$city->setIsEnable($data['is_enable']);
		$city->setParams($data['params']);
		$city->setStorefront($data['storefront']);
		$city->setIsDefaultForStorefront($data['is_default_for_storefront']);
		$city->setDomain($data['domain']);
		$city->setRoute($data['route']);
	}

	private function validateStorefrontData($storefront)
	{
		$_storefront = $storefront;
		$_storefront['robots_txt'] = ifset($storefront['robots_txt'], '');
		$_storefront['head'] = ifset($storefront['head'], '');
		$_storefront['payment'] = ifempty($storefront['payment'], '0');
		$_storefront['shipping'] = ifempty($storefront['shipping'], '0');
		$_storefront['stock'] = ifempty($storefront['stock']);
		$_storefront['regions_ssl'] = ifset($storefront['regions_ssl'], '');
		$_storefront['public_stocks'] = ifempty($storefront['public_stocks'], '0');
		$_storefront['drop_out_of_stock'] = ifempty($storefront['drop_out_of_stock'], '0');
		$_storefront['currency'] = ifempty($storefront['currency'], 'USD');

		return $_storefront;
	}

	private function validateStorefrontSpecificData($storefront)
	{
		$_storefront = array();
		$_storefront['payment_id'] = ifempty($storefront['payment'], '0');
		$_storefront['shipping_id'] = ifempty($storefront['shipping'], '0');
		$_storefront['stock_id'] = ifempty($storefront['stock']);
		$_storefront['public_stocks'] = ifempty($storefront['public_stocks'], '0');
		$_storefront['drop_out_of_stock'] = ifempty($storefront['drop_out_of_stock'], '0');
		$_storefront['currency'] = ifempty($storefront['currency'], 'USD');

		return $_storefront;
	}

	private function updateStorefrontData(shopRegionsRoute $storefront, array $data)
	{
		$storefront->updatePayments($data['payment']);
		$storefront->updateShipping($data['shipping']);
		$storefront->updateStock($data['stock']);
		$storefront->updateSsl($data['regions_ssl']);
		$storefront->updatePublicStocks($data['public_stocks']);
		$storefront->updateDropOutOfStock($data['drop_out_of_stock']);
		$storefront->updateCurrency($data['currency']);
		$storefront->getDomain()->updateRobotsTxt($data['robots_txt']);
		$storefront->getDomain()->updateHead($data['head']);
	}

	private function addCss(array $local_css)
	{
		foreach ($local_css as $_css)
		{
			wa()->getResponse()->addCss('plugins/regions/css/'.$_css, 'shop');
		}
	}

	private function addJs(array $local_js)
	{
		foreach ($local_js as $_js)
		{
			wa()->getResponse()->addJs('plugins/regions/js/'.$_js, 'shop');
		}
	}

	private function initListGetParameters()
	{
		$sort = waRequest::get('sort');
		$order = waRequest::get('order');

		$storage = wa()->getStorage();
		if (is_null($sort))
		{
			$sort = $storage->get('shop_regions_list_sort');
			$sort = ifset($sort, shopRegionsCityModel::DEFAULT_SORT);
		}
		if (is_null($order))
		{
			$order = $storage->get('shop_regions_list_order');
			$order = ifset($order, shopRegionsCityModel::DEFAULT_ORDER);
		}

		if ($sort === shopRegionsCityModel::CUSTOM_SORT_COLUMN_NAME)
		{
			$order = 'asc';
		}

		$storage->set('shop_regions_list_sort', $sort);
		$storage->set('shop_regions_list_order', $order);

		return array($sort, $order);
	}

	private function initFilterGetParameters()
	{
		$storage = wa()->getStorage();
		$session_filter = $storage->get('shop_regions_session_filter');
		$session_filter_partial = $storage->get('shop_regions_session_filter_partial');
		unset($session_filter_partial['name']);

		$filter = waRequest::get('filter', array());
		$filter_partial = waRequest::get('filter_partial', array());

		if (!count($filter) && is_array($session_filter))
		{
			$filter = $session_filter;
		}
		if (!count($filter_partial) && is_array($session_filter_partial))
		{
			$filter_partial = $session_filter_partial;
		}

		unset($filter['region_full_code']);
		$storage->set('shop_regions_session_filter', $filter);
		$storage->set('shop_regions_session_filter_partial', $filter_partial);


		$to_unset = array();
		foreach ($filter_partial as $name => $value)
		{
			if ($value === '0' || !strlen($value))
			{
				$to_unset[] = $name;
			}
		}
		foreach ($to_unset as $unset)
		{
			unset($filter_partial[$unset]);
		}

		$to_unset = array();
		foreach ($filter as $name => $value)
		{
			if ($value === '0' || !strlen($value))
			{
				$to_unset[] = $name;
			}
		}
		foreach ($to_unset as $unset)
		{
			unset($filter[$unset]);
		}

		return array($filter, $filter_partial);
	}

	/**
	 * @param array $cities_assoc
	 */
	private function assignIsSingleForStorefront($cities_assoc)
	{
		$is_single_for_storefront = array();
		$city_storefront_count = array();
		foreach ($cities_assoc as $city)
		{
			if (!isset($city_storefront_count[$city['storefront']]))
			{
				$city_storefront_count[$city['storefront']] = 0;
			}
			$city_storefront_count[$city['storefront']]++;
		}
		foreach ($cities_assoc as $city)
		{
			$is_single_for_storefront[$city['id']] = $city_storefront_count[$city['storefront']] === 1;
		}
		$this->view->assign('is_single_for_storefront', $is_single_for_storefront);
	}

	/**
	 * @param $sort
	 * @param $order
	 */
	private function assignSort($sort, $order)
	{
		$city_model = new shopRegionsCityModel();
		$without_storefront_column = $city_model->countDistinct('storefront') <= 1;
		$without_region_column = $city_model->countDistinct(array('region_code', 'country_iso3')) <= 1;
		$without_country_column = $city_model->countDistinct('country_iso3') <=  1;

		$sort_columns = array();
		foreach (shopRegionsCityModel::getSortColumns() as $column => $name)
		{
			if (($without_region_column && $column === 'region_name') || ($without_country_column && $column === 'country_name'))
			{
				$sort = $sort === $column ? shopRegionsCityModel::DEFAULT_SORT : $sort;
				continue;
			}

			if ($column === shopRegionsCityModel::CUSTOM_SORT_COLUMN_NAME)
			{
				$href = "?plugin=regions&sort={$column}&order=asc";
			}
			else
			{
				$column_order = $column === $sort
					? ($order === 'desc' ? 'asc' : 'desc')
					: 'asc';
				$href = "?plugin=regions&sort={$column}&order={$column_order}";
			}

			$highlighted_class = $sort !== shopRegionsCityModel::DEFAULT_SORT || $order !== shopRegionsCityModel::DEFAULT_ORDER
				? 'highlighted'
				: '';

			$sort_columns[$column] = array(
				'title' => _wp($name),
				'href' => $href,
				'order_icon_class' => $order === 'asc' ? 'uarr-tiny' : 'darr-tiny',
				'highlighted_class' => $highlighted_class,
				'li_class' => $sort === $column ? 'active' : '',
			);
		}

		$this->view->assign(
			array(
				'sort' => $sort,
				'order' => $order,
				'sort_columns' => $sort_columns,
				'is_custom_sortable' => $sort === shopRegionsCityModel::CUSTOM_SORT_COLUMN_NAME,
				'without_region_column' => $without_region_column,
				'without_country_column' => $without_country_column,
				'without_storefront_column' => $without_storefront_column,
			)
		);
	}

	private function assignFilters($filter, $filter_partial)
	{
		$city_model = new shopRegionsCityModel();
		if ($city_model->countAll() == 0)
		{
			$this->view->assign('filter_name_value', null);
			$this->view->assign('select_filters', array());

			return;
		}

		list($filter_popular, $filter_storefront, $filter_country, $filter_region_full_code, $filter_name) = array(
			ifset($filter['is_popular'], ''),
			ifset($filter['storefront'], '0'),
			ifset($filter['country_iso3'], '0'),
			ifset($filter['region_country_iso3']) && ifset($filter['region_code']) ? $filter['region_country_iso3'] . '|' . $filter['region_code'] : '0',
			ifset($filter_partial['name'], ''),
		);


		if ($filter_storefront !== '0' || $filter_country !== '0' || $filter_region_full_code !== '0' || !empty($filter_partial['name']))
		{
			$this->view->assign('is_custom_sortable', false);
		}

		$select_filters = array();
		$checkbox_filters = array();

		if (!$this->view->getVars('without_storefront_column'))
		{
			$helper = new shopRegionsHelper();
			$all_storefronts = $helper->getAllStorefronts();
			$storefronts = array();
			foreach ($city_model->getDistinct('storefront') as $row)
			{
				$storefront = $row['storefront'];
				if (empty($storefront) || !array_key_exists($storefront, $all_storefronts))
				{
					continue;
				}

				$storefronts[] = array(
					'title' => rtrim($all_storefronts[$storefront]['title'], '*/'),
					'value' => $storefront,
					'selected' => $filter_storefront === $storefront ? 'selected="selected"' : '',
				);
			}
			$select_filters[] = array(
				'id' => 'filter_storefront',
				'title' => 'Витрина',
				'field' => 'storefront',
				'options' => $storefronts,
			);
		}



		$m_region = new waRegionModel();

		if (!$this->view->getVars('without_country_column'))
		{
			$helper = new shopRegionsHelper();
			$country_names = $helper->getGroupByIso3Countries();
			$countries = array();
			foreach ($city_model->getDistinct('country_iso3') as $row)
			{
				$country_code = $row['country_iso3'];
				if (empty($country_code))
				{
					continue;
				}

				$countries[] = array(
					'title' => ifset($country_names[$country_code], ''),
					'value' => $country_code,
					'selected' => $filter_country == $country_code ? 'selected="selected"' : '',
				);
			}
			$select_filters[] = array(
				'id' => 'filter_country',
				'title' => 'Страна',
				'field' => 'country_iso3',
				'options' => $countries,
			);
		}



		if (!$this->view->getVars('without_region_column'))
		{
			$regions = array();
			$region_full_codes = array();
			foreach ($city_model->getDistinct(array('region_code', 'country_iso3')) as $row)
			{
				$region_code = $row['region_code'];
				$country_code = $row['country_iso3'];

				if (empty($region_code))
				{
					continue;
				}
				$region_full_codes[] = array(
					'country_iso3' => $country_code,
					'code' => $region_code,
				);
			}
			foreach ($region_full_codes as $region_full_code)
			{
				foreach ($m_region->getByField($region_full_code, true) as $row)
				{
					$regions[] = array(
						'title' => ifset($row['name'], ''),
						'value' => $row['country_iso3'] . '|' . $row['code'],
						'selected' => ($row['country_iso3'] . '|' . $row['code'] == $filter_region_full_code) ? 'selected="selected"' : '',
					);
				}
			}

			usort($regions, array($this, 'sortByTitle'));

			$select_filters[] = array(
				'id' => 'filter_region',
				'title' => 'Область',
				'field' => 'region_full_code',
				'options' => $regions,
			);
		}

		$select_filters[] = array(
			'id' => 'filter_popular',
			'title' => 'Популярные',
			'field' => 'is_popular',
			'remove_default_value' => true,
			'options' => array(
				array(
					'title' => 'Все',
					'value' => '',
					'selected' => $filter_popular == '' ? 'selected="selected"' : '',
				),
				array(
					'title' => 'Да',
					'value' => '1',
					'selected' => $filter_popular == '1' ? 'selected="selected"' : '',
				),
				array(
					'title' => 'Нет',
					'value' => '-1',
					'selected' => $filter_popular == '-1' ? 'selected="selected"' : '',
				),
			),
		);

		$this->view->assign('select_filters', $select_filters);
		$this->view->assign('checkbox_filters', $checkbox_filters);
		$this->view->assign('filter_name_value', $filter_name);
	}

	/**
	 * @param int $total_count
	 * @param int $current_page
	 */
	private function assignPagination($total_count, $current_page)
	{
		$pagination = new shopRegionsPagination($total_count, $current_page);

		if (shopRegionsPagination::itemsPerPage() == 0)
		{
			$this->view->assign(array(
				'cities_count' => $total_count,
				'pagination_items' => null,
				'page' => 1,
				'pagination_offset' => 0,
			));
		}
		else
		{
			$this->view->assign(array(
				'cities_count' => $total_count,
				'pagination_items' => $pagination->generateTemplateItems(),
				'page' => $current_page,
				'pagination_offset' => shopRegionsPagination::itemsPerPage() * ($current_page - 1),
			));
		}
	}

	private function assignStorefrontOptions()
	{
		$plugin_root = wa()->getAppPath($this->getPluginRoot(), 'shop');

		$this->view->assign('for_specific_settings', false);
		$this->view->assign('form_attribute_name', 'storefront');

		$this->view->assign(
			'storefront_options',
			$this->view->fetch($plugin_root . '/templates/actions/regions/form/Storefront.html')
		);

		$city_id = waRequest::get('id', 0);
		$options_model = new shopRegionsCitySettingsModel();
		$city_settings = $options_model->loadStorefrontSettings($city_id);

		$specific_settings_enabled = '0';

		if ($city_settings !== false)
		{
			$specific_settings_enabled = '1';
			$storefront_settings = $this->view->getVars('storefront_settings');

			foreach ($city_settings as $option => $value)
			{
				switch ($option)
				{
					case 'payment_id':
						$tmp_option = 'payment';
						break;
					case 'shipping_id':
						$tmp_option = 'shipping';
						break;
					case 'stock_id':
						$tmp_option = 'stock';
						break;
					default:
						$tmp_option = $option;
				}

				$storefront_settings[$tmp_option] = $value;
			}
			$this->view->assign('storefront_settings', $storefront_settings);
		}

		$this->view->assign('for_specific_settings', true);
		$this->view->assign('form_attribute_name', 'storefront_specific');
		$region_specific_storefront_options = $this->view->fetch($plugin_root . '/templates/actions/regions/form/Storefront.html');

		$this->view->assign(array(
			'region_specific_storefront_options' => $region_specific_storefront_options,
			'specific_settings_enabled' => $specific_settings_enabled
		));
	}

	private function sortByTitle($a, $b)
	{
		return strcasecmp($a['title'], $b['title']);
	}

	/**
	 * @param int $city_id
	 * @return null|shopRegionsCity
	 */
	private function getCityById($city_id)
	{
		wa('site');
		$domain_model = new siteDomainModel();

		$city_collection = new shopRegionsCityCollection();
		$row = $city_collection
			->leftJoin($domain_model->getTableName(), ':table.id = t.domain_id', null, $alias)
			->select('t.*, ' . $alias . '.name domain_name')
			->where('t.id = :id', array('id' => $city_id))
			->getFirst();

		return $row
			? shopRegionsCity::build($row)
			: null;
	}

	private function prepareFilterForCollection($filter)
	{
		if (isset($filter['region_country_iso3']))
		{
			$filter['country_iso3'] = $filter['region_country_iso3'];
			unset($filter['region_country_iso3']);
		}

		$is_popular = ifset($filter['is_popular'], '');
		if ($is_popular === '')
		{
			unset($filter['is_popular']);
		}
		elseif ($is_popular === '-1')
		{
			$filter['is_popular'] = '0';
		}

		return $filter;
	}
}
<?php

class shopDpFrontend
{
	/**
	 * @var array $assigns
	 */
	public $assigns = array();

	/**
	 * @var shopDpEnv $env
	 * @var shopDpPlugin $plugin
	 * @var shopDpLocation $location
	 * @var array $settings
	 * @var string $storefront_id
	 * @var string $theme_id
	 * @var array $params
	 * @var array $is_disabled_assets
	 */
	protected static $env;
	protected $plugin;
	protected $location;
	protected $settings = array();
	protected $storefront_id;
	protected $theme_id;
	protected $params = array(
		'css' => true
	);
	protected $is_disabled_assets = array(
		'init' => false
	);

	/**
	 * @var waView $view
	 * @var shopDpTemplates $templates_instance
	 */
	private $view;
	private $templates_instance;

	/**
	 * @param waView|null $view
	 * @param array|null $settings
	 * @param shopDpEnv|null $env
	 * @param shopDpPlugin|null $plugin
	 * @throws waException
	 */
	public function __construct($view = null, $settings = null, $env = null, $plugin = null)
	{
		if($view)
			$this->view = $view;

		if($plugin)
			$this->plugin = $plugin;

		if($settings !== null)
			$this->settings = $settings;
		else
			$this->settings = $this->getPlugin()->getSettings();

		if($env !== null && $env instanceof shopDpEnv)
			self::$env = $env;
	}

	/**
	 * @param array $params
	 */
	public function setParams($params)
	{
		$this->params = array_merge($this->params, $params);
	}

	/**
	 * @return array $params
	 */
	public function getParams()
	{
		return $this->params;
	}

	/**
	 * @return mixed $name
	 */
	public function getParam($name)
	{
		return ifset($this->params, $name, null);
	}

	/**
	 * @param array $assets
	 */
	public function disableAssets($assets)
	{
		foreach($assets as $asset)
			$this->is_disabled_assets[$asset] = true;
	}

	/**
	 * @param array $assets
	 */
	public function enableAssets($assets)
	{
		foreach($assets as $asset)
			$this->is_disabled_assets[$asset] = false;
	}

	/**
	 * Возвращает URL подключаемых скриптов и таблиц стилей
	 * @param array $assets
	 * @return array $output_assets
	 * @throws waException
	 */
	public function getAssets($assets)
	{
		$output_assets = array();

		if(isset($assets['css']) && $this->getParam('css'))
			foreach($assets['css'] as $asset) {
				$stylesheet_url = $this->getTemplatesInstance()->get('stylesheet_' . strtolower($asset), 'url');
				if($stylesheet_url) {
					$output_assets[$asset] = array(
						'type' => 'css',
						'url' => $stylesheet_url . '?v' . $this->getPlugin()->getVersion()
					);
				}

				$custom_stylesheet_url = shopDpStylesheet::getOutputUrl($this->getCurrentTheme(), $asset);
				if($custom_stylesheet_url) {
					$output_assets["custom.$asset"] = array(
						'type' => 'css',
						'url' => $custom_stylesheet_url
					);
				};
			}

		if(empty($this->is_disabled_assets['init'])) {
			$output_assets['init'] = array(
				'type' => 'js',
				'url' => wa()->getRouteUrl('shop/frontend/config/', array(
						'plugin' => 'dp'
					)) . '?v' . $this->getPlugin()->getVersion()
			);

			if($this->getParam('css'))
				$output_assets['dialog'] = array(
					'type' => 'css',
					'url' => $this->getTemplatesInstance()->get('stylesheet_dialog', 'url') . '?v' . $this->getPlugin()->getVersion()
				);
		}

		return $output_assets;
	}

	/**
	 * @return shopDpEnv
	 */
	protected static function getEnv()
	{
		if(!isset(self::$env))
			self::$env = new shopDpEnv();

		return self::$env;
	}

	/**
	 * @return string
	 */
	protected function getCurrentStorefront()
	{
		if(!isset($this->storefront_id))
			$this->storefront_id = $this->getEnv()->getCurrentStorefront();

		return $this->storefront_id;
	}

	/**
	 * @return string
	 */
	protected function getCurrentTheme()
	{
		if(!isset($this->theme_id))
			$this->theme_id = $this->getEnv()->getCurrentTheme();

		return $this->theme_id;
	}

	/**
	 * @return shopDpPlugin
	 * @throws waException
	 */
	protected function getPlugin()
	{
		if(!isset($this->plugin))
			$this->plugin = shopDpPlugin::getInstance('frontend');

		return $this->plugin;
	}

	/**
	 * @return shopDpLocation
	 */
	public function getLocation()
	{
		if(!isset($this->location))
			$this->location = new shopDpLocation('frontend');

		return $this->location;
	}

	/**
	 * @param array $location
	 * @param array [string]string $location['country']
	 * @param array [string]string $location['region']
	 * @param array [string]string $location['city']
	 */
	public function setLocation($location)
	{
		if($location instanceof shopDpLocation) {
			$this->location = $location;
		} elseif(is_array($location)) {
			if(!empty($location['country']))
				$this->getLocation()->setCountry($location['country']);
			if(!empty($location['region']))
				$this->getLocation()->setRegion($location['region']);
			if(!empty($location['city']))
				$this->getLocation()->setCity($location['city']);
		}

		$country = $this->getLocation()->getCountry();
		$region = $this->getLocation()->getRegion();
		$city = $this->getLocation()->getCity();
		$this->assign('location', compact('country', 'region', 'city'));
	}

	/**
	 * @return string
	 */
	protected function getPluginPath()
	{
		return $this->getEnv()->getPluginPath();
	}

	/**
	 * @param string $template
	 * @return string
	 */
	protected function getTemplatePath($template)
	{
		return $this->getPluginPath() . "templates/actions/frontend/Frontend$template.html";
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 * @param bool $directly
	 */
	public function assign($name, $value, $directly = false)
	{
		if($directly) {
			$this->view->assign($name, $value);
		} else {
			$this->getView()->assign($name, $value);
		}

		$this->assigns[$name] = true;
	}

	/**
	 * @param string $name
	 */
	public function unassign($name)
	{
		$this->assigns[$name] = null;
	}

	/**
	 * @return waTheme
	 */
	public function getTheme()
	{
		if(!isset($this->theme)) {
			$this->theme = new waTheme($this->getCurrentTheme(), 'shop');
		}

		return $this->theme;
	}

	/**
	 * @return string
	 */
	public function getTemplate()
	{
		$params = waRequest::param();

		if(!array_key_exists('action', $params)) {
			return 'index.html';
		}

		switch($params['action']) {
			case 'category':
				return 'category.html';
				break;
			case 'product':
				return 'product.html';
				break;
			case 'page':
				return 'page.html';
				break;
			default:
				return 'index.html';
				break;
		}
	}

	/**
	 * @return shopDpTemplates
	 */
	private function getTemplatesInstance()
	{
		if(!isset($this->templates_instance))
			$this->templates_instance = new shopDpTemplates();

		return $this->templates_instance;
	}

	private function createView()
	{
		return new waSmarty3View(wa());
	}

	/**
	 * Регистрирует View перед выводом во фронтенд
	 */
	private function registerView()
	{
		if(!isset($this->view)) {
			$this->view = $this->createView();
		}

		if(empty($this->assigns['location']) && $this->getLocation()) {
			$country = $this->getLocation()->getCountry();
			$region = $this->getLocation()->getRegion();
			$city = $this->getLocation()->getCity();
			$this->assign('location', compact('country', 'region', 'city'), true);
		}

		if(empty($this->assigns['_theme'])) {
			$this->view->setThemeTemplate($this->getTheme(), $this->getTemplate());
			$this->assigns['_theme'] = true;
			$this->assign('current_theme_id', $this->getCurrentTheme(), true);
		}

		if(empty($this->assigns['plugin_path'])) {
			$this->assign('plugin_path', $this->getPluginPath(), true);
		}

		if(empty($this->assigns['plugin_url'])) {
			$this->assign('plugin_url', $this->getEnv()->getPluginUrl(), true);
		}

		if(empty($this->assigns['frontend_dialog'])) {
			$this->assign('frontend_dialog', $this->getPluginPath() . 'templates/actions/frontend/FrontendDialog.html', true);
		}

		if(empty($this->assigns['_register'])) {
			$this->getTemplatesInstance()->register($this->view);
			$this->assigns['_register'] = true;
		}
	}

	/**
	 * @return waResponse
	 */
	private function getResponse()
	{
		return wa()->getResponse();
	}

	/**
	 * @return waView
	 */
	public function getView()
	{
		$this->registerView();

		return $this->view;
	}

	/**
	 * @param string|null $name
	 * @return mixed
	 */
	public function getSettings($name = null)
	{
		return $this->getEnv()->getActiveSettings($this->settings, $name);
	}

	/**
	 * @return array
	 */
	private function getShippingMethods()
	{
		$shipping_methods = $this->getSettings('shipping_methods');
		$sort = $this->getSettings('shipping_sort');

		$point_services = shopDpPluginHelper::getPointServices();

		foreach($shipping_methods as $id => &$shipping_method) {
			if($this->getSettings('design_points_group') && !empty($shipping_method['service']) && in_array($shipping_method['service'], $point_services)) {
				$shipping_method['service_id'] = 'points';
			} else {
				$shipping_method['service_id'] = $id;
			}
		}

		if(!empty($sort)) {
			$ordered_shipping_methods = array();

			foreach($sort as $id) {
				if(array_key_exists($id, $shipping_methods)) {
					$ordered_shipping_methods[$id] = $shipping_methods[$id];
					unset($shipping_methods[$id]);
				}
			}

			return $ordered_shipping_methods + $shipping_methods;
		}

		return $shipping_methods;
	}

	/**
	 * @param string $entity
	 * @return bool
	 */
	public function getGroupStatus($entity)
	{
		return (bool) $this->getSettings("{$entity}_group_status");
	}

	/**
	 * Получает сервисы доставки
	 * @param bool $is_group
	 * @param array $options
	 * @param bool $is_array
	 * @return array
	 * @throws waException
	 */
	public function getServices($is_group = false, $options = array(), $is_array = false)
	{
		$default_options = array(
			'env' => $this->getEnv(),
			'plugin' => $this->getPlugin(),
			'frontend' => $this
		);

		$options = array_merge($default_options, $options);

		$services_instance = new shopDpServices($this->getShippingMethods(), $options);

		return $services_instance->getServices(array(
			'availability' => 'available',
			'group' => $is_group,
			'array' => $is_array
		));
	}

	/**
	 * @param shopDpService|shopDpServicePointsGroup $service
	 * @return string
	 */
	public function serviceDialogTitle($service)
	{
		$view = $this->getView();

		return $view->fetch('string:' . $service->getDialogTitle());
	}

	/**
	 * @param shopDpService|shopDpServicePointsGroup $service
	 * @return string
	 */
	public function serviceDialog($service)
	{
		$view = $this->getView();

		$view->assign('plugin_url', $this->getEnv()->getPluginUrl());
		$view->assign('service', $service);

		$view->assign('filter', array(
			'payment' => $this->getSettings('design_points_filter_payment'),
			'work' => $this->getSettings('design_points_filter_work'),
			'search' => $this->getSettings('design_points_filter_search'),
			'service' => !empty($service['filter_by_service'])
		));

		$path = wa()->getAppPath('plugins/dp/templates/actions/frontend/FrontendDialogService.html', 'shop');

		return $view->fetch($path);
	}

	/**
	 * @param array $params
	 * @param array $vars
	 * @return string
	 * @throws waException
	 */
	public function citySelect($params = array(), $vars = array())
	{
		if(!$this->getSettings('status') || !$this->getSettings('ip_status') || !$this->getEnv()->isReadyIpPluginCityApi()) {
			return '';
		}

		if(empty($vars['is_next_page']))
			$vars['is_next_page'] = false;
		if(empty($vars['is_next_product']))
			$vars['is_next_product'] = false;

		$vars['params'] = $params;

		$view = $this->getView();
		$view->assign($vars);

		$assets = array();
		if(empty($this->is_disabled_assets['citySelect']))
			$assets = array(
				'js' => array('citySelect'),
				'css' => array('city_select')
			);

		$current_theme_id = $this->getCurrentTheme();
		return $this->outputFrontend("shop_dp:{$current_theme_id}/city_select_link", $assets, 'js-dp-city-select-wrapper dp-container__city-select', array(
			'title' => $this->citySelectTitle($vars),
			'content' => $this->citySelectDialog($vars)
		));
	}

	/**
	 * @param array $params
	 * @param array $vars
	 * @return string
	 * @throws waException
	 */
	public function page($params = array(), $vars = array())
	{
		if(!$this->getSettings('status')) {
			return '';
		}

		$vars['params'] = $params;

		$vars['columns'] = array(
			'name' => (bool) $this->getSettings('design_page_name_col'),
			'date' => (bool) $this->getSettings('design_page_date_col'),
			'cost' => (bool) $this->getSettings('design_page_cost_col'),
			'payment' => (bool) $this->getSettings('design_page_payment_col')
		);
		$vars['payment_style'] = $this->getSettings('design_page_payment_style');

		$cost_mode = $this->getSettings('page_cost_mode');
		$estimated_date_mode = $this->getSettings('page_estimated_date_mode');

		$service_options = array(
			'caller' => 'page',
			'cost' => $cost_mode,
			'estimated_date' => $estimated_date_mode,
			'calculate_params' => self::getEnv()->getCalculateParams(null, $cost_mode === 'cart'),
			'show_column_headers' => $this->getSettings('design_page_show_column_headers'),
		);

		$groups = $this->getServices($this->getGroupStatus('page'), $service_options, true);

		$assets = array();
		if(empty($this->is_disabled_assets['page']))
			$assets = array(
				'js' => array('service', 'page'),
				'css' => array('page', 'service')
			);

		$current_theme_id = $this->getCurrentTheme();
		$output = $this->outputGroupsFrontend(array(
			'content' => "shop_dp:{$current_theme_id}/page",
			'wrapper' => 'js-dp-page-wrapper dp-container__page',
		), $groups, $assets, $vars);

		if($this->getSettings('design_page_city_select_status')) {
			$this->disableAssets(array('init'));

			$city_select_output = $this->citySelect(array(), array(
				'is_next_page' => true
			));
			$output = $city_select_output . $output;
		}

		return $output;
	}

	/**
	 * @param shopProduct|array|int|string $product
	 * @param array $params
	 * @param array $vars
	 * @return string
	 * @throws waException
	 */
	public function product($product, $params = array(), $vars = array())
	{
		if(!$this->getSettings('status')) {
			return '';
		}

		if(!$product instanceof shopProduct) {
			if(is_array($product) && isset($product['id']))
				$product = new shopProduct($product['id']);
			elseif(wa_is_int($product))
				$product = new shopProduct($product);
			else
				return null;
		}

		$header_template = $this->getSettings('design_product_header');
		$header = '';

		if($header_template) {
			if($this->getEnv()->isReadyIpPluginCityApi()) {
				$this->disableAssets(array('init'));
				$city_select_output = $this->citySelect(array(), array(
					'is_next_product' => true
				));
				$this->enableAssets(array('init'));
			} else {
				$city_select_output = $this->getLocation()->getCity();
			}

			$header_view = $this->createView();
			$header_view->assign('location', $this->getLocation());
			$header_view->assign('city_select', $city_select_output);
			$header = $header_view->fetch('string:' . nl2br($header_template));
		}

		$vars['header'] = $header;

		$params['is_break_services'] = $this->getSettings('design_product_break_services_status') === '1';
		$params['group_style'] = $this->getSettings('design_product_group_style');

		$vars['params'] = $params;
		$vars['async'] = false;

		$cost_mode = $this->getSettings('product_cost_mode');
		$estimated_date_mode = $this->getSettings('product_estimated_date_mode');
		$calculate_mode = $this->getSettings('product_calculate_mode');

		$service_options = array(
			'caller' => 'product',
			'cost' => $cost_mode,
			'estimated_date' => $estimated_date_mode,
			'mode' => $calculate_mode,
			'calculate_params' => $this->getEnv()->getCalculateParams(
				$product,
				in_array($cost_mode, array('cart', 'cart+product'))
			),
			'show_column_headers' => true,
		);
		$groups = $this->getServices($this->getGroupStatus('product'), $service_options, true);

		if(!$groups && $this->getSettings('product_hide_if_no_services'))
		{
			return;
		}

		$assets = array();
		if(empty($this->is_disabled_assets['product']))
			$assets = array(
				'js' => array('service', 'product'),
				'css' => array('product', 'service'),
			);

		$vars['product'] = $product;

		$wrapper_id = uniqid('dp-product-');

		$current_theme_id = $this->getCurrentTheme();
		$output = $this->outputGroupsFrontend(array(
			'content' => "shop_dp:{$current_theme_id}/product",
			'wrapper' => 'js-dp-product-wrapper dp-container__product',
			'data' => array(
				'product-id' => $product->getId()
			),
		), $groups, $assets, $vars);

		return $output;
	}

	/**
	 * @param array $output
	 * @param array $groups
	 * @param array $assets
	 * @param array $vars
	 * @return string
	 * @throws waException
	 */
	protected function outputGroupsFrontend($output, $groups, $assets = array(), $vars = array())
	{
		$view = $this->getView();

		$view->assign('c', shopDpPluginHelper::getCurrency());
		$view->assign('groups', $groups);

		$view->assign($vars);

		return $this->outputFrontend($output['content'], $assets, ifset($output, 'wrapper', null), ifset($output, 'data', array()));
	}

	/**
	 * @param string $content
	 * @param array $assets
	 * @param string|null $wrapper
	 * @param array $data
	 * @param array|null $replaces
	 * @return string
	 * @throws waException
	 */
	protected function outputFrontend($content, $assets = array(), $wrapper = null, $data = array(), $replaces = null)
	{
		$view = $this->getView();
		$view->assign('assets', $assets);

		$asset_mode = $this->getSettings('asset_mode');

		$output_assets = $this->getAssets($assets);
		$view->assign('output_assets', $output_assets);
		$view->assign('asset_mode', $asset_mode);

		if($asset_mode === 'add') {
			foreach($output_assets as $asset) {
				if($asset['type'] === 'js') {
					$this->getResponse()->addJs($asset['url'] . '#://');
				} else {
					$this->getResponse()->addCss($asset['url'] . '#://'); // Ты ничего не видел. Иди дальше
				}
			}
		}

		if(!empty($replaces) && is_array($replaces)) {
			if($replaces['location'] && $replaces['location'] instanceof shopDpLocation) {
				$country = $replaces['location']->getCountry();
				$region = $replaces['location']->getRegion();
				$city = $replaces['location']->getCity();

				$view->assign('location', compact('country', 'region', 'city'));
			}
		}

		$content = $view->fetch($content);

		if(!$content)
			return null;

		$view->assign(compact('content', 'wrapper', 'data'));

		$output = $view->fetch($this->getTemplatePath('Output'));

		if(!empty($replaces) && is_array($replaces)) {
			if($replaces['location'] && $replaces['location'] instanceof shopDpLocation) {
				$this->unassign('location');
			}
		}

		return $output;
	}

	/**
	 * @param ArrayAccess $service
	 * @return string
	 */
	public function productCalculate($service)
	{
		$view = $this->getView();
		$view->assign(array(
			'async' => true,
			'service' => $service,
		));

		$current_theme_id = $this->getCurrentTheme();

		return $view->fetch("shop_dp:{$current_theme_id}/product");
	}

	/**
	 * @param array $vars
	 * @return string
	 */
	public function citySelectTitle($vars = array())
	{
		$view = $this->getView();
		$view->assign($vars);

		return $view->fetch('string:' . $this->getSettings('design_city_select_title'));
	}

	/**
	 * @param array $vars
	 * @return string
	 */
	public function citySelectDialog($vars = array())
	{
		$view = $this->getView();
		$view->assign($vars);

		$current_theme_id = $this->getCurrentTheme();
		return $view->fetch("shop_dp:{$current_theme_id}/city_select_dialog");
	}

	/**
	 * @param array $vars
	 * @return string
	 */
	public function pointBalloon($vars = array())
	{
		$view = $this->getView();
		$view->assign($vars);

		$current_theme_id = $this->getCurrentTheme();
		return $view->fetch("shop_dp:{$current_theme_id}/point_balloon");
	}

	/**
	 * @param array $vars
	 * @return string
	 */
	public function zoneBalloon($vars = array())
	{
		$view = $this->getView();
		$view->assign($vars);

		$current_theme_id = $this->getCurrentTheme();
		return $view->fetch("shop_dp:{$current_theme_id}/zone_balloon");
	}

	/**
	 * @param string $value
	 * @param array $vars
	 * @return string
	 */
	public function serviceField($value, $vars = array())
	{
		$view = $this->getView();
		$view->assign($vars);

		return $view->fetch("string:$value");
	}

	/**
	 * @param array $vars
	 * @return string
	 */
	public function pointsServiceSwitcher($vars = array())
	{
		$view = $this->getView();
		$view->assign($vars);

		$current_theme_id = $this->getCurrentTheme();
		return $view->fetch("shop_dp:{$current_theme_id}/points_switcher");
	}
}

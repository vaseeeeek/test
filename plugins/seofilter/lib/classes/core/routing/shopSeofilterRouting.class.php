<?php

class shopSeofilterRouting
{
	const ROUTING_STEP_CATEGORY = 'CATEGORY';
	const ROUTING_STEP_PRODUCT = 'PRODUCT';
	const ROUTING_STEP_SEOFILTER = 'SEOFILTER';

	private static $instance = null;


	/** @var shopSeofilterPluginSettings */
	private $settings;
	/** @var shopSeofilterFilterTreeSettings */
	private $tree_settings;

	/** @var shopCategoryModel */
	private $category_model;
	/** @var shopProductModel */
	private $product_model;
	/** @var shopSeofilterFiltersStorage */
	private $filter_storage;
	/** @var shopSeofilterFiltersFrontendStorage */
	private $filter_frontend_storage;

	private $category_root_url;
	private $product_root_url;
	private $simple_filter_url_root;

	private $original_request_uri;
	private $original_url;
	private $storefront_url_type;

	private $currency;


	private $is_initialized = false;

	private $is_category_page = false;
	private $is_product_page = false;
	private $is_seofilter_page = false;

	/** @var array|null */
	private $category = null;
	/** @var shopSeofilterFilter|null */
	private $filter = null;
	/** @var shopSeofilterFrontendFilter|null */
	private $frontend_filter = null;

	private $seofilter_url_suffix;
	private $seofilter_url_suffix_is_removed = false;

	private $route = null;

	/**
	 * @return shopSeofilterRouting
	 */
	public static function instance()
	{
		if (self::$instance === null)
		{
			self::$instance = new shopSeofilterRouting();
		}

		return self::$instance;
	}

	private function __construct()
	{
		$this->initFields();
	}

	private function __clone()
	{
		throw new waException("can't clone instance of [shopSeofilterRouting]");
	}

	public function getStorefront()
	{
		$routing = wa()->getRouting();
		$domain = $routing->getDomain();

		$route = $this->route === null
			? $routing->getRoute()
			: $this->route;

		return $domain . '/' . $route['url'];
	}

	public function isSeofilterPage()
	{
		return $this->is_seofilter_page;
	}

	public function isProductPage()
	{
		return $this->is_product_page;
	}

	public function isCategoryPage()
	{
		return $this->is_category_page;
	}

	public function getFilter()
	{
		return $this->filter;
	}

	public function getFrontendFilter()
	{
		return $this->frontend_filter;
	}

	public function getCategory()
	{
		return $this->category;
	}

	public function getCategoryId()
	{
		return is_array($this->category) && array_key_exists('id', $this->category)
			? $this->category['id']
			: null;
	}

	public function isInitialized()
	{
		return $this->is_initialized;
	}

	public function initializePluginRouting($url)
	{
		if ($this->is_initialized)
		{
			return;
		}

		$this->is_initialized = true;
		$this->original_request_uri = $_SERVER['REQUEST_URI'];
		$this->original_url = $url;

		$category = null;
		$filter = null;

		/**
		 * пытаемся понять чем является текущая страница
		 * порядок проверки по-умолчанию: категория, товар, фильтр
		 *
		 * можно изменить его настройкой routing_steps_order в файле wa-config/apps/shop/plugins/seofilter/config.php
		 */
		foreach ($this->getRoutingStepsOrder() as $step_name)
		{
			if ($step_name == self::ROUTING_STEP_CATEGORY)
			{
				$category = $this->getCategoryByUrl($this->original_url);

				if ($category)
				{
					$this->category = $category;
					$this->is_category_page = true;

					return;
				}
			}
			elseif ($step_name == self::ROUTING_STEP_PRODUCT)
			{
				try
				{
					$product = $this->getProductByUrl($this->original_url);
				}
				catch (waException $e)
				{
					$product = null;
				}

				if ($product)
				{
					$this->is_product_page = true;

					return;
				}
			}
			elseif ($step_name == self::ROUTING_STEP_SEOFILTER)
			{
				try
				{
					/** @var shopSeofilterFilter $filter */
					list($category, $filter, $seofilter_url_suffix) = $this->tryGetCategoryAndFilterByUrl($this->original_url);
				}
				catch (waException $e)
				{
					continue;
				}

				$this->filter = $filter;
				$this->category = $category;
				$this->seofilter_url_suffix = $seofilter_url_suffix;

				$this->is_seofilter_page = true;

				break;
			}
		}

		if (!$category || !$filter)
		{
			return;
		}

		$this->frontend_filter = new shopSeofilterFrontendFilter(
			$this->getStorefront(),
			$category['id'],
			$filter,
			waRequest::get('page', 1, waRequest::TYPE_INT)
		);
	}

	public function redispatch()
	{
		/**
		 * @var shopConfig $config
		 */
		$config = wa('shop')->getConfig();
		shopSeofilterShopConfig::cleanRoutes($config);

		shopSeofilterSystem::cleanFactories();

		wa()->getRouting()->dispatch();
		wa('shop', true)->getFrontController()->dispatch();

		exit;
	}

	public function tryToPerformRedirects()
	{
		$redirect_url = null;

		$extra_params = shopSeofilterFilterFeatureValuesHelper::normalizeParams(waRequest::get());
		if (count($extra_params))
		{
			$filter = $this->filter_frontend_storage->getByFilterParams($this->getStorefront(), $this->category['id'], $extra_params, $this->currency);

			if ($filter)
			{
				$redirect_url = $filter->getFrontendCategoryUrlWithAdditionalParams($this->category);
			}
			else
			{
				if ($this->isSeofilterPage())
				{
					$this->removeSeofilterSuffixFromUrl();
				}

				$redirect_url = waSystem::getInstance()->getConfig()->getRequestUrl(false, true) . '?' . http_build_query($extra_params, null, '&');
			}
		}
		elseif ($this->seofilter_url_suffix !== strtolower($this->seofilter_url_suffix) || substr($this->original_url, -1, 1) != '/')
		{
			$redirect_url = $this->filter->getFrontendCategoryUrlWithAdditionalParams($this->category);
		}

		if (is_string($redirect_url) && strlen($redirect_url) > 0)
		{
			wa()->getResponse()->redirect($redirect_url, 301);
		}
	}

	public function tryRedirectToFilterPage()
	{
		$filter_params = shopSeofilterFilterFeatureValuesHelper::getGetParametersForSearch();

		$seofilter_url = new shopSeofilterFilterUrl($this->settings->url_type, waRequest::param('url_type'));

		$filter = $this->filter_frontend_storage->getByFilterParams(
			$this->getStorefront(),
			$this->category['id'],
			$filter_params,
			$this->currency
		);

		if ($filter && !$seofilter_url->haveShopUrlCollision($filter->url) && $filter->countProducts($this->category['id'], $this->currency) > 0)
		{
			//wa()->getResponse()->redirect($filter->getFrontendCategoryUrlWithAdditionalParams($this->category, array('sort', 'order')), 301);
			wa()->getResponse()->redirect($filter->getFrontendCategoryUrlWithAdditionalParams($this->category), 301);
		}
	}

	public function removeSeofilterSuffixFromUrl()
	{
		if ($this->seofilter_url_suffix_is_removed)
		{
			return;
		}

		$this->seofilter_url_suffix_is_removed = true;

		if ($this->settings->url_type == shopSeofilterPluginUrlType::CATEGORY_JOIN)
		{
			$pattern = '/' . preg_quote('-' . $this->seofilter_url_suffix, '/') . '\/?$/';
			$replace_to = '';
		}
		else
		{
			$pattern = '/' . preg_quote($this->seofilter_url_suffix, '/') . '\/?$/';
			$replace_to = '/';
		}

		$parts = array();

		$root_part = trim(wa()->getRouting()->getRootUrl(), '/');
		if (strlen($root_part))
		{
			$parts[] = $root_part;
		}

		$parts[] = trim(preg_replace($pattern, $replace_to, $this->original_url), '/');

		$_SERVER['REQUEST_URI'] = '/' . implode('/', $parts) . '/';
	}

	public function restoreInitialUrl()
	{
		$_SERVER['REQUEST_URI'] = $this->original_request_uri;
	}

	public function getOriginalRequestUri()
	{
		return $this->original_request_uri;
	}

	public function isOriginalRequestUriHasSort()
	{
		$url_parsed = parse_url($this->original_request_uri);

		if (!array_key_exists('query', $url_parsed))
		{
			return false;
		}

		parse_str($url_parsed['query'], $params);

		return array_key_exists('sort', $params) || array_key_exists('order', $params);
	}

	public function patchGetParameters()
	{
		$get_params = $this->filter->getFeatureValuesAsFilterParamsForCurrency($this->currency);

		foreach ($this->filter->featureValues as $feature_value)
		{
			$feature = $feature_value->feature;
			if ($feature && $feature->type === 'double' && $feature->selectable == '0' && $value_row = $feature_value->featureValue)
			{
				$get_params[$feature->code] = array(
					'min' => $value_row['value'],
					'max' => $value_row['value'],
				);
			}
		}

		foreach ($get_params as $param => $val)
		{
			$_GET[$param] = $val;
		}

		if (!array_key_exists('sort', $_GET))
		{
			$rule = $this->filter->getActivePersonalRule($this->getStorefront(), $this->category['id']);

			$sort = '';
			$order = '';

			if ($rule && $rule->default_product_sort)
			{
				$sort = $rule->getDefaultSortSort();
				$order = $rule->getDefaultSortOrder();
			}
			elseif ($this->filter->default_product_sort)
			{
				$sort = $this->filter->getDefaultSortSort();
				$order = $this->filter->getDefaultSortOrder();
			}
			elseif ($this->settings->default_product_sort)
			{
				$sort = $this->settings->getDefaultProductSortSort();
				$order = $this->settings->getDefaultProductSortOrder();
			}

			if ($sort && $sort != 'category')
			{
				$_GET['sort'] = $sort;

				if ($order)
				{
					$_GET['order'] = strtolower($order) == 'asc' ? 'asc' : 'desc';
				}
			}
		}
	}

	public function triggerEvent()
	{
		$filter_attributes = new shopSeofilterFilterAttributes($this->filter);

		if ($this->settings->url_type == shopSeofilterPluginUrlType::CATEGORY_JOIN)
		{
			$filter_attributes->setFullUrl("{$this->category['url']}-{$this->filter->url}");
		}
		else
		{
			$filter_attributes->setFullUrl($this->seofilter_url_suffix);
		}

		$category_id = $this->category['id'];

		$storefront = $this->getStorefront();
		$canonical = $this->filter_storage->getFilterCanonical($this->filter->id, $storefront, $category_id);
		if ($canonical)
		{
			$plugin_canonical = new shopSeofilterLinkcanonicalCanonical(
				$canonical->canonical_url_template,
				$storefront,
				$this->category
			);

			$filter_attributes->setCanonicalUrlTemplate($canonical->canonical_url_template);
			$filter_attributes->setCanonicalUrl($plugin_canonical->fetchUrl());
		}

		$event_params = array(
			'filter' => $filter_attributes,
			'category_id' => $category_id,
		);
		waRequest::setParam('seofilter_filter_url', $this->seofilter_url_suffix); // todo setParam'у тут не место

		wa()->event('shop_seofilter_frontend', $event_params);
	}

	public function setRoute($route)
	{
		$this->route = $route;
	}

	private function getCategoryByUrl($url)
	{
		/**
		 *
		 * category
		 * 0: category/<cat>/<sub_cat>/
		 * 1: category/<sub_cat>/
		 * 2: <cat>/<sub_cat>/
		 */

		$category = null;

		if ($this->storefront_url_type == shopSeofilterWaShopUrlType::MIXED)
		{
			if (strpos($url, $this->category_root_url) !== 0)
			{
				return null;
			}

			$category_full_url = substr($url, strlen($this->category_root_url));
			$category_full_url = trim($category_full_url, '/');
			$category = $this->category_model->getByField('full_url', $category_full_url);
		}
		elseif ($this->storefront_url_type == shopSeofilterWaShopUrlType::PLAIN)
		{
			if (strpos($url, $this->category_root_url) !== 0)
			{
				return null;
			}

			$category_url = substr($url, strlen($this->category_root_url));
			$category_url = trim($category_url, '/');
			$category = $this->category_model->getByField('url', $category_url);
		}
		elseif ($this->storefront_url_type == shopSeofilterWaShopUrlType::NATURAL)
		{
			$category_full_url = trim($url, '/');
			$category = $this->category_model->getByField('full_url', $category_full_url);
		}

		return $category;
	}

	private function getProductByUrl($url)
	{
		/**
		 * product
		 * 0: <product>/
		 * 1: product/<product>/
		 * 2: <cat>/<sub_cat>/<product>/
		 */
		$product = null;

		if ($this->storefront_url_type == shopSeofilterWaShopUrlType::MIXED)
		{
			$product_url = trim($url, '/');

			$product = $this->product_model->getByUrl($product_url);
		}
		elseif ($this->storefront_url_type == shopSeofilterWaShopUrlType::PLAIN)
		{
			if (strpos($url, $this->product_root_url) !== 0)
			{
				return null;
			}

			$product_url = substr($url, strlen($this->product_root_url));
			$product_url = trim($product_url, '/');

			$product = $this->product_model->getByUrl($product_url);
		}
		elseif ($this->storefront_url_type == shopSeofilterWaShopUrlType::NATURAL)
		{
			$url_trimmed = trim($url, '/');
			$url_parts = explode('/', $url_trimmed);

			if (count($url_parts) == 0)
			{
				throw new waException("invalid product url [{$url}]");
			}

			if (count($url_parts) > 1)
			{
				$category_url = implode('/', array_slice($url_parts, 0, -1));
				$category = $this->getCategoryByUrl($category_url);

				if (!$category)
				{
					return null;
				}
			}

			$product_url = $url_parts[count($url_parts) - 1];
			$product = $this->product_model->getByUrl($product_url);
		}

		return $product;
	}

	/**
	 * @param $url
	 * @return array
	 * @throws waException
	 */
	private function tryGetCategoryAndFilterByUrl($url)
	{
		$found_category = null;
		$found_filter = null;
		$found_filter_url_suffix = null;

		$storefront = $this->getStorefront();

		$possible_partitions = $this->getPossibleUrlPartitions($url);
		foreach ($possible_partitions as $possible_partition)
		{
			$found_category = null;
			$found_filter = null;
			$found_filter_url_suffix = null;

			list($category_url, $seofilter_url, $found_filter_url_suffix) = $possible_partition;

			$found_category = $this->getCategoryByUrl($category_url);

			if (!$found_category)
			{
				continue;
			}

			$category_id = $found_category['id'];


			if (!$this->tree_settings->isPluginEnabledOnStorefrontCategory($storefront, $category_id))
			{
				continue;
			}

			$found_filter = $this->filter_storage->getByUrl(
				$storefront,
				$category_id,
				$seofilter_url
			);

			if (!$found_filter)
			{
				continue;
			}

			break;
		}


		if (!$found_filter || !$found_category)
		{
			throw new waException("can't find filter on url [{$url}] for storefront [{$storefront}]");
		}


		if ($this->settings->consider_category_filters)
		{
			$validator = new shopSeofilterFilterFeaturesValidator();

			$filter_get_params = $found_filter->getFeatureValuesAsFilterParamsForCurrency($this->currency);
			if (!$validator->validateCategoryParams($found_category['id'], $filter_get_params))
			{
				throw new waException("filter [{$found_filter->id}] is not enabled for category [{$found_category['id']}] due category filtration settings");
			}
		}

		if (!$this->tree_settings->isFilterEnabled($storefront, $found_category['id'], $found_filter))
		{
			throw new waException("filter [{$found_filter->id}] is not enabled for storefront [{$storefront}] and category id [{$found_category['id']}] due storefront category tree settings");
		}

		if ($found_filter->hasDeletedFeatureValues())
		{
			throw new waException("filter [{$found_filter->id}] has deleted feature or feature value");
		}

		return array($found_category, $found_filter, $found_filter_url_suffix);
	}

	/**
	 * @param $url
	 * @return array
	 * @throws waException
	 */
	private function getPossibleUrlPartitions($url)
	{
		$url_trimmed = $url;
		if (substr($url, -1, 1) == '/')
		{
			$url_trimmed = substr($url, 0, -1);
		}

		$possible_partitions = array();

		$url_parts = explode('/', $url_trimmed);

		$seofilter_url_type = $this->settings->url_type;
		if ($seofilter_url_type == shopSeofilterPluginUrlType::SHORT)
		{
			if (count($url_parts) > 1)
			{
				$category_url = implode('/', array_slice($url_parts, 0, -1));
				$seofilter_url = $url_parts[count($url_parts) - 1];
				$seofilter_url_suffix = $seofilter_url;

				$possible_partitions[] = array($category_url, $seofilter_url, $seofilter_url_suffix);
			}
		}
		elseif ($seofilter_url_type == shopSeofilterPluginUrlType::SIMPLE)
		{
			if (count($url_parts) > 2 && $url_parts[count($url_parts) - 2] === trim($this->simple_filter_url_root, '/'))
			{
				$category_url = implode('/', array_slice($url_parts, 0, -2));
				$seofilter_url = $url_parts[count($url_parts) - 1];
				$seofilter_url_suffix = implode('/', array_slice($url_parts, -2));

				$possible_partitions[] = array($category_url, $seofilter_url, $seofilter_url_suffix);
			}
		}
		elseif ($seofilter_url_type == shopSeofilterPluginUrlType::CATEGORY_JOIN)
		{
			$category_url_prefix = count($url_parts) > 1
				? implode('/', array_slice($url_parts, 0, -1))
				: '';
			$joined_url = $url_parts[count($url_parts) - 1];

			$joined_url_parts = explode('-', $joined_url);
			if (count($joined_url_parts) > 1)
			{
				for ($category_part_length = 1; $category_part_length < count($joined_url_parts); $category_part_length++)
				{
					$category_url_suffix = implode('-', array_slice($joined_url_parts, 0, $category_part_length));

					$category_url = $category_url_prefix === ''
						? $category_url_suffix
						: $category_url_prefix . '/' . $category_url_suffix;
					$seofilter_url = implode('-', array_slice($joined_url_parts, $category_part_length));
					$seofilter_url_suffix = $seofilter_url;

					$possible_partitions[] = array($category_url, $seofilter_url, $seofilter_url_suffix);
				}
			}
		}
		else
		{
			throw new waException("unknown seofilter url type [{$seofilter_url_type}]");
		}

		if (count($possible_partitions) === 0)
		{
			throw new waException("url [{$url}] can't be seofilter url");
		}

		return $possible_partitions;
	}

	private function initFields()
	{
		$this->settings = shopSeofilterBasicSettingsModel::getSettings();
		$this->tree_settings = new shopSeofilterFilterTreeSettings();

		$this->category_model = new shopCategoryModel();
		$this->product_model = new shopProductModel();
		$this->filter_storage = new shopSeofilterFiltersStorage();
		$this->filter_frontend_storage = new shopSeofilterFiltersFrontendStorage();

		$settings_root_url = $this->settings->root_url;
		$this->category_root_url = ($settings_root_url ? $settings_root_url : 'category') . '/';
		$this->product_root_url = 'product' . '/';
		$this->simple_filter_url_root = 'filter' . '/';

		$this->storefront_url_type = waRequest::param('url_type');

		$this->is_seofilter_page = false;
		$this->is_initialized = false;

		/** @var shopConfig $shop_config */
		$shop_config = wa('shop')->getConfig();
		$this->currency = $shop_config->getCurrency(false);
	}

	private function getRoutingStepsOrder()
	{
		$default_routing_steps_order = array(
			self::ROUTING_STEP_CATEGORY,
			self::ROUTING_STEP_PRODUCT,
			self::ROUTING_STEP_SEOFILTER,
		);

		if (!is_array($this->settings->routing_steps_order))
		{
			return $default_routing_steps_order;
		}

		$check_steps = array();
		foreach ($this->settings->routing_steps_order as $step_name)
		{
			$check_steps[$step_name] = true;
		}

		if (
			!array_key_exists(self::ROUTING_STEP_CATEGORY, $check_steps)
			|| !array_key_exists(self::ROUTING_STEP_PRODUCT, $check_steps)
			|| !array_key_exists(self::ROUTING_STEP_SEOFILTER, $check_steps)
		)
		{
			return $default_routing_steps_order;
		}

		return $this->settings->routing_steps_order;
	}
}

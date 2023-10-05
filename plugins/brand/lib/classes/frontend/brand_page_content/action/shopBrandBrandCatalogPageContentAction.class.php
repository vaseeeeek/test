<?php

class shopBrandBrandCatalogPageContentAction extends shopBrandBrandPageContentAction
{
	private $products_collection;
	private $filters;

	public function __construct($params = null)
	{
		parent::__construct($params);

		$this->products_collection = new shopBrandProductsCollection('', array('brand_id' => $this->brand->id));

		$sort = $this->products_collection->getSortBySortOption($this->brand->product_sort);
		if ($sort && !waRequest::get('sort'))
		{
			$this->products_collection->setBrandSortProducts($sort);
		}

		$this->filters = $this->getFilters();
	}

	public function executeBrandPage(shopBrandFetchedLayout $fetched_layout)
	{
		$view = $this->view;

		$this->setResponseCode();

		$this->addCanonical();

		$feature = shopBrandHelper::getBrandFeature();

		$categories_cache = $this->getCategoriesCache();

		if ($categories_cache->isCached()) {
			$categories = $categories_cache->get();
		} else {
			$categories = $this->getCategories($feature);
			$this->appendCategoriesParams($categories);

			$categories_cache->set($categories);
		}

		$categories_plain_tree = $this->buildPlainTree($categories);

		$view->assign('brand_categories', $categories);
		$view->assign('brand_categories_plain_tree', $categories_plain_tree);

		$view->assign('filters', $this->filters);
		$view->assign('filters_hash', 'brand_plugin/'.$this->brand->id);

		$products_collection = new shopBrandProductsCollection();

		$sort = $products_collection->getSortBySortOption($this->brand->product_sort);
        $sort_as_default_string = $sort;
		if ($sort && !waRequest::get('sort'))
		{
			$products_collection->setBrandSortProducts($sort);
		}

		$sort = $products_collection->getSortBySortOption($this->brand->product_sort);
		$view->assign('sorting', $sort);
		if ($sort && !waRequest::get('sort')) {
			$sort = explode(' ', $sort);
			$view->assign('active_sort', $sort[0] == 'count' ? 'stock' : $sort[0]);

		} elseif (!$sort && !waRequest::get('sort')) {
			$view->assign('active_sort', '');
		}

		$this->setCollection($products_collection);

		if (count($this->brand->filter) && !empty($filters)) {
			$this->fixPrices($filters);
		}

		waSystem::popActivePlugin();

		$brand_clone = clone $this->brand;
		$brand_clone->description = $fetched_layout->description;
		$brand_clone->additional_description = $fetched_layout->additional_description;

		$h1 = $fetched_layout->h1;
		if (!$h1)
		{
			$h1 = $brand_clone->name;
		}
        $brand_clone["sort_products"] = $sort_as_default_string;
		$view->assign('category', $brand_clone);
		$view->assign('title', $h1);

		$current_brand_page = $view->getVars('page');

		$view->assign(array(
			'h1' => $h1,
			'description' => $brand_clone->description,
			'additional_description' => $brand_clone->additional_description,
			'page' => waRequest::get('page', 1, waRequest::TYPE_INT),
			'current_brand_page' => $current_brand_page,
		));

		/**
		 * @event frontend_search
		 * @return array[string]string $return[%plugin_id%] html output for search
		 */
		$frontend_search = wa()->event('frontend_search');

		$frontend_search['plugin-brand'] = $this->getCatalogHeader();
		$view->assign('frontend_search', $frontend_search);
	}

	protected function getViewBufferTemplateVars()
	{
		$vars = array();

		$products_count = $this->products_collection->count();

		$rounding = new shopBrandWaRounding();

		$range = $this->products_collection->getPriceRange();
		$min_price = $rounding->roundPrice($range['min']);
		$max_price = $rounding->roundPrice($range['max']);

		$limit = (int)waRequest::cookie('products_per_page');
		if (!$limit || $limit < 0 || $limit > 500) {
			$limit = $this->getConfig()->getOption('products_per_page');
		}
		$pages_count = ceil((float)$products_count / $limit);

		$vars['page_number'] = wa()->getRequest()->get('page', 1);
		$vars['pages_count'] = $pages_count;

		$vars['brand']['products_count'] = $products_count;
		$vars['brand']['min_price'] = shop_currency($min_price);
		$vars['brand']['min_price_without_currency'] = $min_price;
		$vars['brand']['max_price'] = shop_currency($max_price);
		$vars['brand']['max_price_without_currency'] = $max_price;

		return shopBrandHelper::mergeViewVarArrays(parent::getViewBufferTemplateVars(), $vars);
	}

	protected function getTemplateLayout()
	{
		$template_layout = parent::getTemplateLayout();
		$templates = $template_layout->getTemplates();

		$fields_to_check = array(
			'h1',
			'meta_title',
		);

		foreach ($fields_to_check as $field)
		{
			if (!is_string($templates[$field]) || mb_strlen(trim($templates[$field])) == 0)
			{
				$templates[$field] = $this->brand->name;
			}
		}

		return new shopBrandTemplateLayout($templates);
	}

	protected function getMainViewVarNamesToReplace()
	{
		return array(
			'h1' => 'h1',
			'filters' => 'filters',
			'filters_hash' => 'filters_hash',
			'sorting' => 'sorting',
			'active_sort' => 'active_sort',
			'frontend_search' => 'frontend_search',
		);
	}

	/**
	 * @return array
	 */
	protected function getFilters()
	{
		$collection = $this->products_collection;
		$brand = $this->brand;

		$filters = array();
		if (count($brand->filter)) {
			$filter_ids = $brand->filter;
			$feature_model = new shopFeatureModel();
			$features = $feature_model->getById(array_filter($filter_ids, 'is_numeric'));
			if ($features) {
				$features = $feature_model->getValues($features);
			}
			$brand_value_ids = $collection->getFeatureValueIds();

			$view = $this->view;
			foreach ($filter_ids as $fid) {
				if ($fid == 'price') {
					$range = $collection->getPriceRange();
					if ($range['min'] != $range['max']) {
						$filters['price'] = array(
							'min' => shop_currency($range['min'], null, null, false),
							'max' => shop_currency($range['max'], null, null, false),
						);
						$view->assign('price_min', $filters['price']['min']);
						$view->assign('price_max', $filters['price']['max']);
					}
				} elseif (isset($features[$fid]) && isset($brand_value_ids[$fid])) {
					$filters[$fid] = $features[$fid];
					$min = $max = $unit = null;
					foreach ($filters[$fid]['values'] as $v_id => $v) {
						if (!in_array($v_id, $brand_value_ids[$fid])) {
							unset($filters[$fid]['values'][$v_id]);
						} else {
							if ($v instanceof shopRangeValue) {
								$begin = $this->getFeatureValue($v->begin);
								if ($min === null || $begin < $min) {
									$min = $begin;
								}
								$end = $this->getFeatureValue($v->end);
								if ($max === null || $end > $max) {
									$max = $end;
									if ($v->end instanceof shopDimensionValue) {
										$unit = $v->end->unit;
									}
								}
							} else {
								$tmp_v = $this->getFeatureValue($v);
								if ($min === null || $tmp_v < $min) {
									$min = $tmp_v;
								}
								if ($max === null || $tmp_v > $max) {
									$max = $tmp_v;
									if ($v instanceof shopDimensionValue) {
										$unit = $v->unit;
									}
								}
							}
						}
					}
					if (!$filters[$fid]['selectable'] && ($filters[$fid]['type'] == 'double' ||
							substr($filters[$fid]['type'], 0, 6) == 'range.' ||
							substr($filters[$fid]['type'], 0, 10) == 'dimension.')
					) {
						if ($min == $max) {
							unset($filters[$fid]);
						} else {
							$type = preg_replace('/^[^\.]*\./', '', $filters[$fid]['type']);
							if ($type != 'double') {
								$filters[$fid]['base_unit'] = shopDimension::getBaseUnit($type);
								$filters[$fid]['unit'] = shopDimension::getUnit($type, $unit);
								if ($filters[$fid]['base_unit']['value'] != $filters[$fid]['unit']['value']) {
									$dimension = shopDimension::getInstance();
									$min = $dimension->convert($min, $type, $filters[$fid]['unit']['value']);
									$max = $dimension->convert($max, $type, $filters[$fid]['unit']['value']);
								}
							}
							$filters[$fid]['min'] = $min;
							$filters[$fid]['max'] = $max;
						}
					}
				}
			}
		}

		return $filters;
	}

	/**
	 * @param $feature
	 * @return array
	 */
	protected function getCategories($feature)
	{
		$brand_id = $this->brand->id;

		$category_model = new shopCategoryModel();

		$categories_count_sql = "
SELECT cp.category_id, COUNT(DISTINCT cp.product_id) AS `count`
FROM shop_category_products cp
	JOIN shop_product_features pf
		ON cp.product_id = pf.product_id
	JOIN shop_product p
		ON p.id = pf.product_id
WHERE pf.feature_id = ".(int)$feature['id']." AND pf.feature_value_id = ".$brand_id." AND p.`status` = 1
GROUP BY cp.category_id";

		$categories_count = $category_model->query($categories_count_sql)->fetchAll('category_id', true);

		$temp = $categories_parents = array();
		if (count($categories_count) > 0)
		{
			$temp = $categories_parents = $category_model->select('id,parent_id')
				->where('id IN (:ids)', array('ids' => array_keys($categories_count)))
				->fetchAll('id', true);

			$temp = array_unique(array_values($temp));
		}

		while (count($temp) > 0)
		{
			$parents = $category_model
				->select('id,parent_id')
				->where('id IN (:ids)', array('ids' => $temp))
				->where('include_sub_categories = 1')
				->fetchAll('id', true);

			$categories_parents = array_replace_recursive($categories_parents, $parents);

			$to_merge = array();

			foreach ($categories_count as $id => $category_count)
			{
				$p_id = $categories_parents[$id];
				if (!isset($parents[$p_id]))
				{
					continue;
				}

				if (!isset($to_merge[$p_id]))
				{
					$to_merge[$p_id] = 0;
				}

				$to_merge[$p_id] += $category_count;
			}

			$categories_count = array_replace_recursive($categories_count, $to_merge);

			$temp = array_unique(array_values($parents));
		}

		if ($categories_count) {
			$route = wa()->getRouting()->getDomain(null, true) . '/' . wa()->getRouting()->getRoute('url');
			$sql = 'SELECT c.* FROM shop_category c
                    LEFT JOIN shop_category_routes cr ON c.id = cr.category_id
                    WHERE c.id IN (i:ids) AND c.status = 1 AND
                    (cr.route IS NULL OR cr.route = s:route)
                    ORDER BY c.left_key';
			$query_params = array('ids' => array_keys($categories_count), 'route' => $route);
			$categories = $category_model->query($sql, $query_params)->fetchAll('id', true);

			foreach ($categories as $c_id => $c) {
				$categories[$c_id]['id'] = $c_id;
				$categories[$c_id]['count'] = $categories_count[$c_id];
			}
		} else {
			$categories = array();
		}

		$category_url_template = wa()->getRouteUrl('shop/frontend/category', array('category_url' => '%CATEGORY_URL%')) . '?' . $feature['code'] . '[]=' . $brand_id;

		foreach ($categories as &$c)
		{
			$c['frontend_url'] = str_replace('%CATEGORY_URL%', waRequest::param('url_type') == 1 ? $c['url'] : $c['full_url'], $category_url_template);
		}
		unset($c);

		$category_link_options = new shopBrandCategoryLinkModeEnumOptions();

		$settings_storage = new shopBrandSettingsStorage();
		$settings = $settings_storage->getSettings();

		if ($settings->category_link_mode == $category_link_options->RAW)
		{
			foreach ($categories as $category_id => &$c)
			{
				$collection = new shopProductsCollection('category/' . $category_id);
				$collection->filters(array($feature['code'] => array($brand_id)));
				$product_count = $collection->count();
				$c['product_count'] = $product_count;
				$c['product_count_cached'] = $product_count;
			}
			unset($c);

			return $categories;
		}


		$filter_storage = new shopBrandSeofilterFiltersFrontendStorage();

		$without_filter = array();

		$category_param_model = new shopCategoryParamsModel();

		$personal_link_templates = $category_param_model
			->select('category_id,value')
			->where('name = \'brand_catalog_seofilter_link_template\'')
			->fetchAll('category_id', true);

		foreach ($categories as $category_id => $category)
		{
			if ($category)
			{
				$filter = $filter_storage->getByFilterParams(
					$this->storefront,
					$category_id,
					array($feature['code'] => array($brand_id)),
					$this->currency
				);

				if (
					$filter
					&& $filter_storage->filterHaveProducts($this->storefront, $category_id, $filter)
					&& ($product_count = $filter->countProducts($category_id, $this->currency))
				)
				{
					$frontend_filter = new shopSeofilterFrontendFilter($this->storefront, $category_id, $filter);
					$context = new shopSeofilterCategoryContext(
						$frontend_filter,
						$this->currency,
						$this->storefront,
						$category_id,
						1
					);
					$context->prepareContext();

					$link_template = array_key_exists($category_id, $personal_link_templates) && trim($personal_link_templates[$category_id]) !== ''
						? $personal_link_templates[$category_id]
						: $frontend_filter->h1;

					$h1 = $context->fetchFromBufferAll($link_template);

					if (is_string($h1) && strlen(trim($h1)) > 0)
					{
						$categories[$category_id]['name'] = $h1;
					}

					$categories[$category_id]['frontend_url'] = $filter->getFrontendCategoryUrl($category);
					$categories[$category_id]['product_count'] = $product_count;
					$categories[$category_id]['product_count_cached'] = $product_count;
				}
				else
				{
					$without_filter[] = $category_id;
				}
			}
		}


		foreach ($without_filter as $category_id)
		{
			if ($settings->category_link_mode == $category_link_options->SEOFILTER_ONLY)
			{
				unset($categories[$category_id]);
			}
			else
			{
				$collection = new shopProductsCollection('category/' . $category_id);
				$collection->filters(array($feature['code'] => array($brand_id)));
				$product_count = $collection->count();

				$categories[$category_id]['product_count'] = $product_count;
				$categories[$category_id]['product_count_cached'] = $product_count;
			}
		}

		return $categories;
	}

	protected function fixPrices($filters)
	{
		// fix prices
		$view = $this->view;
		/** @var array $products */
		$products = $view->getVars('products');
		$product_ids = array();
		foreach ($products as $p_id => $p) {
			if ($p['sku_count'] > 1) {
				$product_ids[] = $p_id;
			}
		}
		if ($product_ids) {
			$min_price = $max_price = null;
			$tmp = array();
			foreach ($filters as $fid => $f) {
				if ($fid == 'price') {
					$min_price = waRequest::get('price_min');
					if (!empty($min_price)) {
						$min_price = (double)$min_price;
					} else {
						$min_price = null;
					}
					$max_price = waRequest::get('price_max');
					if (!empty($max_price)) {
						$max_price = (double)$max_price;
					} else {
						$max_price = null;
					}
				} else {
					$fvalues = waRequest::get($f['code']);
					if ($fvalues && !isset($fvalues['min']) && !isset($fvalues['max'])) {
						$tmp[$fid] = $fvalues;
					}
				}
			}

			$rows = array();
			if ($tmp) {
				$pf_model = new shopProductFeaturesModel();
				$rows = $pf_model->getSkusByFeatures($product_ids, $tmp);
			} elseif ($min_price || $max_price) {
				$ps_model = new shopProductSkusModel();
				$rows = $ps_model->getByField('product_id', $product_ids, true);
			}
			$product_skus = array();
			shopRounding::roundSkus($rows, $products);
			foreach ($rows as $row) {
				$product_skus[$row['product_id']][] = $row;
			}

			if ($product_skus) {
				foreach ($product_skus as $product_id => $skus) {
					$currency = $products[$product_id]['currency'];
					usort($skus, array($this, 'sortSkus'));
					$k = 0;
					if ($min_price || $max_price) {
						foreach ($skus as $i => $sku) {
							if ($min_price) {
								$tmp_price = shop_currency($min_price, true, $currency, false);
								if ($sku['price'] < $tmp_price) {
									continue;
								}
							}
							if ($max_price) {
								$tmp_price = shop_currency($max_price, true, $currency, false);
								if ($sku['price'] > $tmp_price) {
									continue;
								}
							}
							$k = $i;
							break;
						}
					}
					$sku = $skus[$k];
					if ($products[$product_id]['sku_id'] != $sku['id']) {
						$products[$product_id]['sku_id'] = $sku['id'];
						$products[$product_id]['frontend_url'] .= '?sku='.$sku['id'];
						$products[$product_id]['price'] =
							shop_currency($sku['price'], $currency, $this->default_currency, false);
						$products[$product_id]['compare_price'] =
							shop_currency($sku['compare_price'], $currency, $this->default_currency, false);
					}
				}
				$view->assign('products', $products);
			}
		}
	}

	public function addCanonical()
	{
		$get_vars = waRequest::get();
		unset($get_vars['page']);

		if ($get_vars)
		{
			$this->view->assign('canonical', wa()->getConfig()->getHostUrl() . wa()->getConfig()->getRequestUrl(false, true));
		}
	}

	private function getFeatureValue($v)
	{
		if ($v instanceof shopDimensionValue)
		{
			return $v->value_base_unit;
		}
		if (is_object($v))
		{
			return $v->value;
		}

		return $v;
	}

	public function setCollection(shopProductsCollection $collection)
	{
		$get = waRequest::get();
		$brand_feature = shopBrandHelper::getBrandFeature();

		if ($brand_feature)
		{
			$get[$brand_feature['code']] = array($this->brand->id);
		}

		$collection->filters($get);
		$limit = (int)waRequest::cookie('products_per_page');
		if (!$limit || $limit < 0 || $limit > 500) {
			$limit = $this->getConfig()->getOption('products_per_page');
		}
		$page = waRequest::get('page', 1, 'int');
		if ($page < 1) {
			$page = 1;
		}
		$offset = ($page - 1) * $limit;

		$products = $collection->getProducts('*,skus_filtered,skus_image,frontend_url', $offset, $limit);
		$count = $collection->count();

		$pages_count = intval(ceil((float)$count / $limit));

		$view = $this->view;
		$view->assign('pages_count', $pages_count);
		wa()->getView()->assign('pages_count', $pages_count);

		$view->assign('products', $products);
		$view->assign('products_count', $count);
	}

	protected function getActionTemplate()
	{
		return new shopBrandBrandCatalogActionThemeTemplate($this->getTheme());
	}

	private function getCatalogHeader()
	{
		$action = new shopBrandBrandCatalogHeaderContentAction(array(
			self::BRAND_PAGE_ACTION_PARAM => $this->action,
		));

		$action->view->assign($this->view->getVars());

		return $action->display(false);
	}

	private function buildPlainTree($categories)
	{
		usort($categories, array($this, '_compareLeftKeys'));

		return $categories;
	}

	private function _compareLeftKeys($c1, $c2)
	{
		$k1 = $c1['left_key'];
		$k2 = $c2['left_key'];

		if ($k1 == $k2)
		{
			return 0;
		}

		return $k1 > $k2 ? 1 : -1;
	}

	private function getCategoriesCache()
	{
		$ttl_seconds = 24 * 60 * 60 * (1 + rand(-20, 20) * 0.01);

		$cache_path = 'plugins/brand/brand_categories/' . md5("{$this->storefront}:{$this->brand->id}");

		return new waSerializeCache($cache_path, $ttl_seconds, 'shop');
	}

	private function setResponseCode()
	{
		if ($this->products_collection->count() > 0)
		{
			return;
		}

		$empty_page_response_mode_options = new shopBrandEmptyPageResponseModeEnumOptions();

		if($this->brand->empty_page_response_mode == $empty_page_response_mode_options->DEFAULT) {

            $settings_storage = new shopBrandSettingsStorage();
            $settings = $settings_storage->getSettings();

            if ($settings->empty_page_response_mode == $empty_page_response_mode_options->ERROR_404)
            {
                throw new waException('', 404);
            }
            elseif ($settings->empty_page_response_mode == $empty_page_response_mode_options->DEFAULT_200)
            {
                wa()->getResponse()->setStatus(200);
            }
            elseif ($settings->empty_page_response_mode == $empty_page_response_mode_options->DEFAULT_404)
            {
                wa()->getResponse()->setStatus(404);
            }
		} elseif ($this->brand->empty_page_response_mode == $empty_page_response_mode_options->ERROR_404)
        {
            throw new waException('', 404);
        }
        elseif ($this->brand->empty_page_response_mode == $empty_page_response_mode_options->DEFAULT_200)
        {
            wa()->getResponse()->setStatus(200);
        }
        elseif ($this->brand->empty_page_response_mode == $empty_page_response_mode_options->DEFAULT_404)
        {
            wa()->getResponse()->setStatus(404);
        }
	}

	private function appendCategoriesParams(&$categories)
	{
		if (!is_array($categories) || count($categories) === 0)
		{
			return;
		}

		$category_param_model = new shopCategoryParamsModel();

		foreach (array_keys($categories) as $category_index)
		{
			$categories[$category_index]['params'] = $category_param_model->get($categories[$category_index]['id']);
		}
	}
}
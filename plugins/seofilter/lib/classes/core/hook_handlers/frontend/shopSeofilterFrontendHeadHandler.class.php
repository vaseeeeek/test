<?php

class shopSeofilterFrontendHeadHandler extends shopSeofilterHookHandler
{
	private $hook_content = '';

	/** @var waSmarty3View|waView */
	private $view;

	public function __construct($params = null)
	{
		parent::__construct($params);

		$this->view = wa()->getView();
	}

	protected function handle()
	{
		$this->hook_content = '';

		if ($this->settings->sitemap_lazy_generation && !waRequest::isXMLHttpRequest())
		{
			$sitemap_cache = new shopSeofilterSitemapCache();
			$sitemap_cache->step();
		}

		$context = $this->plugin_environment->getContext();
		if ($this->plugin_routing->getFilter() && $context)
		{
			$context->apply();
		}

		$category = $this->plugin_routing->getCategory();
		if ($category)
		{
			$this->assignHookTemplateVariables($category);

			$this->hook_content = $this->view->fetch(shopSeofilterHelper::getPath('templates/handlers/FrontendHead.html'));
		}


		return $this->hook_content;
	}

	private function assignHookTemplateVariables($category)
	{
		$category_route_params = array(
			'category_url' => waRequest::param('url_type') == shopSeofilterWaShopUrlType::PLAIN
				? $category['url']
				: $category['full_url']
		);
		$this->view->assign('seofilter_category_url', wa()->getRouteUrl('shop/frontend/category', $category_route_params));

		$code = $this->settings->getYandexCounterCode(shopSeofilterStorefrontModel::getCurrentStorefront());

		$this->view->assign('yandex_counter_code', $code);
		$this->view->assign('plugin_url', wa()->getAppStaticUrl('shop/plugins/seofilter'));

		if ($this->plugin_routing->isSeofilterPage())
		{
			$this->view->assign('seofilter_current_filter_params', $this->prepareParamsForJs($category['id'], $this->plugin_routing->getFilter()));
			$this->view->assign('seofilter_filter_url', $this->plugin_routing->getFilter()->getFrontendCategoryUrl($category['id']));
		}

		$this->view->assign('seofilter_keep_page_number_param', $this->settings->keep_page_number_param);
		$this->view->assign('seofilter_block_empty_feature_values', $this->settings->block_empty_feature_values);
		$this->view->assign('excluded_get_params', $this->settings->excluded_get_params);


		$current_feature_value_ids = false;
		if ($this->settings->block_empty_feature_values)
		{
			$current_feature_value_ids = $this->getCurrentFeatureValueIdsForBlocking();
		}
		$this->view->assign('seofilter_feature_value_ids', $current_feature_value_ids);
		$this->view->assign('stop_propagation_in_frontend_script', $this->settings->stop_propagation_in_frontend_script);
	}

	private function prepareParamsForJs($category_id, shopSeofilterFilter $filter)
	{
		$result = array();

		$filters = shopSeofilterHelper::getViewFilters($category_id);

		$category_filters = array();
		if (is_array($filters))
		{
			foreach ($filters as $view_filter)
			{
				if (isset($view_filter['id']))
				{
					$category_filters[$view_filter['id']] = true;
				}
				elseif (isset($view_filter['code']))
				{
					$feature = shopSeofilterFilterFeatureValuesHelper::getFeatureByCode($view_filter['code']);
					if ($feature)
					{
						$category_filters[$feature['id']] = true;
					}
				}
			}
		}

		foreach ($filter->featureValues as $feature_value)
		{
			$feature = $feature_value->feature;

			if ($feature && $feature_value->value_id && !isset($category_filters[$feature->id]))
			{
				$result[] = array(
					'name' => $feature->code . '[]',
					'value' => $feature_value->value_id,
				);
			}
		}
		unset($feature_value);

		return $result;
	}

	private function getCurrentFeatureValueIdsForBlocking()
	{
		return $this->plugin_environment->getCurrentFeatureValueIdsForBlocking($this->plugin_routing);
	}
}

<?php

class shopBrandPlugin extends waPlugin
{
	private $plugin_settings;

	public function __construct($info)
	{
		parent::__construct($info);

		$settings_storage = new shopBrandSettingsStorage();
		$this->plugin_settings = $settings_storage->getSettings();
	}

	public function routing($route = array())
	{
		if (!$this->plugin_settings->is_enabled)
		{
			return array();
		}

		$routing = parent::routing($route);
		$seofilter_helper = new shopBrandSeofilterHelper();

		if ($seofilter_helper->isSeofilterPluginEnabled())
		{
			$routing = $this->addSeofilterRoutes($routing);
		}

        if ($this->plugin_settings->base_url != ''){
            $routing = $this->setRootUrl($routing);
        }

		if (!$this->plugin_settings->routing_is_extended)
		{
            if ($this->plugin_settings->base_url != '') {
                unset($routing[$this->plugin_settings->base_url . '/<brand>/reviews/add/']);
            } else {
                unset($routing['brand/<brand>/reviews/add/']);
            }
			unset($routing['brand_json/instant_review_rating/']);
		}

		return $routing;
	}

	public function handleBackendMenu()
	{
		$rights = new shopBrandPluginUserRights();
		if (!$rights->hasRights())
		{
			return array();
		}

		return array(
			'core_li' => '<li class="no-tab shop-brand__li"><a href="?plugin=brand">Бренды PRO</a></li>',
		);
	}

	public function handleFrontendNav($_)
	{
		if (!$this->plugin_settings->is_enabled || !$this->plugin_settings->display_brands_to_frontend_nav)
		{
			return null;
		}

		$themes = wa()->getThemes('shop');
		$theme_id = waRequest::getTheme();

		if (!array_key_exists($theme_id, $themes))
		{
			return null;
		}


		$theme = $themes[$theme_id];
		$template = new shopBrandFrontendNavTemplate($theme);

		wa()->getResponse()->addCss($template->getActionCssUrl());

		$brands_collection = shopBrandBrandsCollectionFactory::getBrandsCollection($this->plugin_settings);

		$all_brands = $brands_collection->getBrands();

		if (!is_array($all_brands) || count($all_brands) == 0)
		{
			return null;
		}

		$view = wa()->getView();
		$view->assign('brands', $all_brands);

		shopBrandWaAppConfig::renameBrandPlugin(wa()->getConfig(), 'Бренды');

		$template_path = $template->isThemeTemplate()
			? $theme->getPath() . '/' . $template->getActionThemeTemplate()
			: $template->getActionTemplate();

		$html = $view->fetch($template_path);
		ob_get_clean();

		return trim($html) == '' ? null : $html;
	}

	public function handleSitemap($route)
	{
		if (!$this->plugin_settings->is_enabled)
		{
			return array();
		}


		$domain = wa()->getRouting()->getDomain();
        if(wa()->getEnv() === 'cli') {
            $domain = waRequest::server('HTTP_HOST');
        }
		$route_url = $route['url'];
		$storefront = $domain . '/' . $route_url;

		$urls = array();

		$route_params = array(
			'plugin' => 'brand',
			'module' => 'frontend',
			'action' => 'brands',
		);

		$urls[] = array(
			'loc' => wa()->getRouting()->getUrl('shop', $route_params, true, $domain, $route_url),
			'lastmod' => date('Y-m-d H:i:s'),
			'changefreq' => shopSitemapConfig::CHANGE_WEEKLY,
			'priority' => 0.6,
		);

		//$page_model = new shopBrandBrandPageModel();
		//$page_urls = $page_model->select('url')
		//	->where('brand_id = 0')
		//	->fetchAll('url');
		//unset($page_urls[shopBrandPluginFrontendBrandPageAction::PAGE_CATALOG]);
		//$page_urls = array_keys($page_urls);

		$page_status_options = new shopBrandPageStatusEnumOptions();
		$page_types = new shopBrandPageTypeEnumOptions();

		$pages_storage = new shopBrandPageStorage();
		$brand_page_storage = new shopBrandBrandPageStorage();

		$pages = $pages_storage->getAll();
		foreach (array_keys($pages) as $page_id)
		{
			$page = $pages[$page_id];
			if ($page->status != $page_status_options->PUBLISHED)
			{
				unset($pages[$page_id]);
			}
		}
		unset($page);

		$brands_collection = shopBrandBrandsCollectionFactory::getBrandsCollection($this->plugin_settings);

		$brand_value_ids = $brands_collection->getBrandValueIds();

		$brands = $brands_collection
			->withImagesOnly(false)
			->getBrands();

		foreach ($brands as $brand)
		{
			if (!array_key_exists($brand->id, $brand_value_ids))
			{
				continue;
			}

			//$products_collection = new shopBrandProductsCollection('', array('brand_id' => $brand->id));
			//if ($products_collection->count() == 0)
			//{
			//	continue;
			//}

			foreach ($pages as $page)
			{
				$page_type = $page->type;
				if ($page_type == $page_types->PAGE)
				{
					$brand_page = $brand_page_storage->getPage($brand->id, $page->id, $storefront);

					if (!$brand_page || $brand_page->isEmpty())
					{
						continue;
					}
				}
				elseif ($page_type == $page_types->REVIEWS)
				{
					if ($this->plugin_settings->hide_reviews_tab_if_empty)
					{
						$reviews_collection = new shopBrandBrandReviewSmartCollection($brand->id, $route);
						if ($reviews_collection->count() == 0)
						{
							continue;
						}
					}
				}

				$urls[] = array(
					'loc' => $brand->getFrontendUrl($page, true, $domain, $route_url),
					'lastmod' => date('Y-m-d H:i:s'),
					'changefreq' => shopSitemapConfig::CHANGE_WEEKLY,
					'priority' => 0.5,
				);
			}
		}

		return $urls;
	}

	public function handleProductsCollection(&$event_params)
	{
		if (!array_key_exists('collection', $event_params) || !($event_params['collection'] instanceof shopProductsCollection))
		{
			return false;
		}

		/** @var shopProductsCollection $collection */
		$collection = $event_params['collection'];
		$hash = $collection->getHash();
		if ($hash[0] == 'brand_plugin' && wa_is_int($hash[1]) && $hash[1] > 0)
		{
			$brand_id = $hash[1];

			shopBrandProductsCollection::patchWaProductsCollection($collection, $brand_id);
		}

		return true;
	}

	public function handleRightsConfig(waRightConfig $config)
	{
		$handler = new shopBrandRightsConfigHandler();

		$handler->handle($config);
	}

	private function addSeofilterRoutes($routing)
	{
		return $routing;
	}

	private function setRootUrl($routing){
        $_routing = [];
        foreach ($routing as $url => $route) {
            $_url = str_replace('brand/', $this->plugin_settings->base_url.'/', $url);
            $_routing[$_url] = $route;
        }
        return $_routing;
    }

    public function handleFrontendHead()
    {
        shopBrandViewHelper::initGroupedAssets();
    }
}

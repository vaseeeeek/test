<?php

class shopBrandViewHelper
{
	private static $plugin_settings = null;
    protected static $storefronts;
    protected static $current_storefront;
    protected static $domains = null;

	/**
	 * @param int $brand_id
	 * @return null|shopBrandBrand
	 */
	public static function getBrand($brand_id)
	{
		if (!self::isEnabled())
		{
			return null;
		}

		$storage = new shopBrandBrandStorage();
		$brand = $storage->getById($brand_id);

		return $brand && $brand->is_shown
			? $brand
			: null;
	}

	public static function getBrandMainPage($product){

        if (!self::isEnabled())
        {
            return null;
        }

        $storage = new shopBrandBrandStorage();

        try
        {
            $brand = $storage->getByProduct($product);

            $brand_page_storage = new shopBrandBrandPageStorage();
            $brand_page = $brand_page_storage->getPage($brand->id, 1);

            return $brand_page
                ? $brand_page
                : null;
        }
        catch (waException $e)
        {
            return null;
        }

    }

    public static function getGroupedBrands()
    {
        if (!self::isEnabled())
        {
            return null;
        }

        $storefront = self::getCurrentStorefront();

        $cache = new waSerializeCache('plugins/brand/brand_grouped_brands_' . md5($storefront), 300, 'shop');

        if ($cache->isCached())
        {
            return $cache->get();
        }

        $all = self::getBrands();
        $groups = [];

        $routing = wa()->getRouting();
        $domain = $routing->getDomain();
        $route = $routing->getRoute();
        $route_url = $route['url'];
        $route_params = [
            'plugin' => 'brand',
            'module' => 'frontend',
            'action' => 'brands',
        ];

        $url = wa()->getRouting()->getUrl('shop', $route_params, true, $domain, $route_url);

        foreach ($all as $b) {
            $letter = mb_strtoupper(mb_substr(trim($b->name), 0, 1));
            if(!array_key_exists($letter, $groups)) {
                $groups[$letter] = [
                    'link' => $url . '#' . $letter,
                    'brands' => []
                ];
            }
            $assoc = $b->assoc();
            $assoc['url'] = $b->getFrontendUrl();
            $assoc['image_url'] = $b->getImageUrl();
            $groups[$letter]['brands'][] = $assoc;
        }
        ksort($groups, SORT_LOCALE_STRING);

        $cache->set($groups);

        return $groups;
    }

	/**
	 * @param array|shopProduct|int $product
	 * @return null|shopBrandBrand
	 */
	public static function getProductBrand($product)
	{
		if (!self::isEnabled())
		{
			return null;
		}

		$storage = new shopBrandBrandStorage();

		try
		{
			$brand = $storage->getByProduct($product);

			return $brand && $brand->is_shown
				? $brand
				: null;
		}
		catch (waException $e)
		{
			return null;
		}
	}

	/**
	 * @param array|shopProduct|int $product
	 * @return shopBrandBrand[]
	 */
	public static function getAllProductBrands($product)
	{
		if (!self::isEnabled())
		{
			return null;
		}

		$storage = new shopBrandBrandStorage();

		try
		{
			$product_brands = $storage->getAllByProduct($product);
		}
		catch (waException $e)
		{
			$product_brands = array();
		}

		$product_brands_shown = array();
		foreach ($product_brands as $brand)
		{
			if ($brand->is_shown)
			{
				$product_brands_shown[] = $brand;
			}
		}

		return $product_brands_shown;
	}

	/**
	 * @return shopBrandBrand[]
	 */
	public static function getAllBrands()
	{
		if (!self::isEnabled())
		{
			return array();
		}

		$settings_storage = new shopBrandSettingsStorage();

		$brands_collection = shopBrandBrandsCollectionFactory::getBrandsCollection($settings_storage->getSettings());

		return $brands_collection
			->withImagesOnly(false)
			->getBrands();
	}

	public static function getAllBrandsWithImages()
	{
		if (!self::isEnabled())
		{
			return array();
		}

		$settings_storage = new shopBrandSettingsStorage();

		$brands_collection = shopBrandBrandsCollectionFactory::getBrandsCollection($settings_storage->getSettings());

		return $brands_collection
			->withImagesOnly(true)
			->getBrands();
	}

	public static function getCountryHtml($iso3_code)
	{
		if (!self::isEnabled())
		{
			return '';
		}

		$iso3_code = strtolower(trim($iso3_code));

		$country_model = waCountryModel::getInstance();
		$country = $country_model->get($iso3_code);

		if (!$country)
		{
			return '';
		}

		$path = wa()->getDataPath('countries/' . $iso3_code . '.png', true, 'shop', false);
		if (file_exists($path))
		{
			$image_url = wa()->getDataUrl('countries/' . $iso3_code . '.png', true, 'shop');
		}
		else
		{
			$image_url = wa()->getRootUrl() . "wa-content/img/country/{$iso3_code}.gif";
		}

		$country_name = $country['name'];
		if ($iso3_code == 'usa')
		{
			$country_name = 'США';
		}
		elseif ($iso3_code == 'rus')
		{
			$country_name = 'Россия';
		}
		else
		{
			$country_name = preg_replace('/\s*\([^()]+\)($|\s*)/', '$1', $country_name);
		}

		return "
<div class=\"seobrand-country brand-country\">
	<div class=\"title\">Страна производитель</div>
	<img class=\"image\" src=\"{$image_url}\" title=\"{$country_name}\">{$country_name}
</div>
";
	}

	public static function toTree($categories)
	{
		if (!self::isEnabled())
		{
			return array();
		}

		$stack = array();
		$result = array();
		foreach ($categories as $c) {
			$c['children'] = array();

			// Number of stack items
			$l = count($stack);

			// Check if we're dealing with different levels
			while ($l > 0 && $stack[$l - 1]['depth'] >= $c['depth']) {
				array_pop($stack);
				$l--;
			}

			// Stack is empty (we are inspecting the root)
			if ($l == 0) {
				// Assigning the root node
				$i = count($result);
				$result[$i] = $c;
				$stack[] = &$result[$i];
			} else {
				// Add node to parent
				$i = count($stack[$l - 1]['children']);
				$stack[$l - 1]['children'][$i] = $c;
				$stack[] = &$stack[$l - 1]['children'][$i];
			}
		}
		return $result;
	}

	public static function groupCategoriesPlainTreeByColumns($category_plain_tree, $columns_count = 2)
	{
		if (!self::isEnabled())
		{
			return array();
		}

		$limit = ceil(count($category_plain_tree) / $columns_count - 1e-6) - 1;

		$column_categories = array();
		for ($i = 0; $i < $columns_count; $i++)
		{
			$column_categories[$i] = array();
		}

		$current_column = 0;
		$current_count = 0;
		$root_element_depth = -1;

		foreach ($category_plain_tree as $category)
		{
			if ($root_element_depth < 0)
			{
				$root_element_depth = $category['depth'];
			}

			if ($current_count > $limit && $category['depth'] == $root_element_depth)
			{
				$current_column++;
				$root_element_depth = -1;
				$current_count = 0;
			}

			$column_categories[$current_column][] = $category;
			$current_count++;
		}

		return $column_categories;
	}

	public static function getBrandPages($brand_id)
	{
		if (!self::isEnabled())
		{
			return array();
		}

		$page_storage = new shopBrandPageStorage();
		$brand_page_storage = new shopBrandBrandPageStorage();
		$settings_storage = new shopBrandSettingsStorage();
		$page_status_options = new shopBrandPageStatusEnumOptions();
		$page_type_options = new shopBrandPageTypeEnumOptions();

		$settings = $settings_storage->getSettings();

		$pages = array();
		foreach ($page_storage->getAll() as $page)
		{
			if (!$page->isMain())
			{
				if ($page->status != $page_status_options->PUBLISHED)
				{
					continue;
				}

				if ($page->type == $page_type_options->PAGE)
				{
					$brand_page = $brand_page_storage->getPage($brand_id, $page->id);
					if (!$brand_page || strlen(trim($brand_page->content)) == 0)
					{
						continue;
					}
				}
				elseif ($page->type == $page_type_options->CATALOG)
				{
				}
				elseif ($page->type == $page_type_options->REVIEWS)
				{
					if ($settings->hide_reviews_tab_if_empty)
					{
						$reviews_collection = new shopBrandBrandReviewSmartCollection($brand_id);
						$reviews_count = $reviews_collection->count();

						if ($reviews_count == 0)
						{
							continue;
						}
					}
				}
			}

			$page->is_reviews_page = $page->type == $page_type_options->REVIEWS;

			$pages[] = $page;
		}

		return $pages;
	}

	private static function isEnabled()
	{
		if (!shopBrandHelper::isBrandInstalled())
		{
			return false;
		}

		$settings = self::getPluginSettings();

		return $settings->is_enabled;
	}

	private static function getPluginSettings()
	{
		if (self::$plugin_settings === null)
		{
			$settings_storage = new shopBrandSettingsStorage();

			self::$plugin_settings = $settings_storage->getSettings();
		}

		return self::$plugin_settings;
	}

    private static function getBrands()
    {
        $settings_storage = new shopBrandSettingsStorage();
        $settings = $settings_storage->getSettings();

        try
        {
            $collection = shopBrandBrandsCollectionFactory::getBrandsCollection($settings);
        }
        catch (Exception $e)
        {
            return array();
        }

        self::setSort($collection);

        return $collection->getBrands();
    }

    private static function setSort(shopBrandBrandsCollection $c)
    {
        $sort = waRequest::request('sort');
        $order = waRequest::request('order');

        if ($sort)
        {
            $c->sort($sort, $order);
        }
    }

    /**
     * @return string[]
     */
    public static function getStorefronts()
    {
        if (!is_array(self::$domains))
        {
            self::$domains = wa()->getRouting()->getByApp('shop');
        }

        if (!isset(self::$storefronts))
        {
            self::$storefronts = array();

            foreach (self::$domains as $_domain => $domain_routes)
            {
                foreach ($domain_routes as $_route)
                {
                    self::$storefronts[] = $_domain . '/' . $_route['url'];
                }
            }
        }

        return self::$storefronts;
    }

    /**
     * @return string
     */
    public static function getCurrentStorefront()
    {
        $storefronts = self::getStorefronts();
        $routing = wa()->getRouting();
        $domain = $routing->getDomain();
        $route = $routing->getRoute();
        $storefront = $domain . '/' . $route['url'];

        return in_array($storefront, $storefronts) ? $storefront : null;
    }

    public static function showGroupedBrands()
    {
        if (!self::isEnabled())
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
        $template = new shopBrandGroupedBrandsTemplate($theme);

        wa()->getResponse()->addCss($template->getActionCssUrl());

        $groups = self::getGroupedBrands();


        if (!is_array($groups) || count($groups) == 0)
        {
            return null;
        }


        $view = wa()->getView();
        $view->assign('groups', $groups);

        $template_path = $template->isThemeTemplate()
            ? $theme->getPath() . '/' . $template->getActionThemeTemplate()
            : $template->getActionTemplate();

        $html = $view->fetch($template_path);

        return trim($html) == '' ? null : $html;

    }

    public static function initGroupedAssets()
    {
        if (!self::isEnabled())
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
        $template = new shopBrandGroupedBrandsTemplate($theme);

        wa()->getResponse()->addCss($template->getActionCssUrl());
    }
}

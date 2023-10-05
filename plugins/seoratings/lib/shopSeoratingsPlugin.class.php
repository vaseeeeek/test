<?php

class shopSeoratingsPlugin extends shopPlugin
{
  const SLUG_SETTINGS_KEY = 'slug';
  const DEFAULT_SLUG = 'seoratings';
  const PLUGIN_NAME = 'seoratings';
  /**
   * @var array Current category.
   */
  protected $category;

  /**
   * @var array Default plugin parameters.
   */
  protected $defaults = [
    'enabled' => false,
  ];

  /**
   * @var shopCategoryModel Category model
   */
  protected $categoryModel;

  public function __construct($info)
  {
    parent::__construct($info);
    $this->categoryModel = new shopCategoryModel();
  }

  /**
   * Add link in the left sidebar on the shop products page.
   *
   * @return array
   * @throws SmartyException
   * @throws waException
   */
  public function backendProducts()
  {
    $this->addCss("css/backend/seoratings.css");
    $this->addJs("js/backend/hooks/backend-products.js");

    $view = wa()->getView();

    $ratingModel = new shopSeoratingsRatingModel();
    $ratings = $ratingModel->findRatingsByType('product');

    $view->assign('ratings', $ratings);

    return [
      'sidebar_top_li' => $view->fetch($this->path . '/templates/actions/backend/BackendProducts.html'),
      'toolbar_section' => $view->fetch($this->path . '/templates/actions/backend/BackendToolbar.html'),
    ];
  }

  /**
   * Find and return .hbs templates.
   *
   * @param $path
   *
   * @return array
   */
  public function getJsTemplates($path)
  {
    $recursiveDirectoryIterator = new RecursiveDirectoryIterator($path);
    $iterator = new RecursiveIteratorIterator($recursiveDirectoryIterator);
    $templates = [];

    /** @var SplFileInfo $item */
    foreach ($iterator as $item) {
      if ($item->isFile() && $item->getExtension() == 'hbs') {
        $templates[$item->getBasename('.hbs') . '-tmpl'] = file_get_contents($item->getPathname());
      }
    }

    return $templates;
  }

  /**
   * Create Seoratings routing rules.
   * /{dynamic_value=seoratings}/ – for list of ratings
   * /{dynamic_value=seoratings}/{ratings}/ – for single rating
   *
   * @param array $route
   *
   * @return array|mixed
   */
  public function routing($route = [])
  {
    $routing = parent::routing($route);

    $slug = $this->getSlug($this->getSettings());

    $routing["{$slug}/?"] = [
      'plugin' => 'seoratings',
      'module' => 'frontend',
      'action' => 'viewAllRatings',
    ];

    $routing["{$slug}/<category>/?"] = [
      'plugin' => 'seoratings',
      'module' => 'frontend',
      'action' => 'viewSingleRating',
    ];

    return $routing;
  }

  /**
   * Return current slug for all ratings page.
   *
   * @param array $settings
   *
   * @return string
   */
  public function getSlug(array $settings): string
  {
    $slug = self::DEFAULT_SLUG;

    if (array_key_exists(self::SLUG_SETTINGS_KEY, $settings) && !empty($settings[self::SLUG_SETTINGS_KEY])) {
      $slug = $settings[self::SLUG_SETTINGS_KEY];
    }

    return trim($slug, '/');
  }

  /**
   * @return string
   * @throws waException
   */
  public static function getRootFrontendUrl()
  {
    /** @var self $plugin */
    $plugin = wa('shop')->getPlugin(self::PLUGIN_NAME);

    return rtrim(wa()->getRouteUrl('shop/frontend') . $plugin->getSlug($plugin->getSettings()), '/') . '/';
  }

  /**
   * Return generated html for given rating.
   *
   * @param array $rating Rating to be displayed.
   * @param array $products Products for rating.
   * @param string|int|null $template_id Force template.
   * @param array $params Assign additional variables to the view.
   * @param bool $raw Display only rating content. Without descriptions, and other content.
   *
   * @return string
   * @throws SmartyException
   * @throws waException
   */
  public static function display(array $rating, array $products, $template_id = null, array $params = [], $raw = false)
  {
    $templatesModel = new shopSeoratingsTemplatesModel();
    $seoratingsRatingModel = new shopSeoratingsRatingModel();
    $seoratingsRatingProductsModel = new shopSeoratingsRatingProductsModel();

    if ($raw) {
      $rating['raw'] = $raw;
    }

    if ($_request_template = waRequest::get('seoratings_template')) {
      if (is_numeric($_request_template)) {
        $template_id = intval($_request_template);
      } else {
        $template_id = $_request_template;
      }
    }

    if ($template_id) {
      if (is_string($template_id)) {
        $template = $templatesModel->getByField('name', $template_id);
      } else {
        $template = $templatesModel->getById($template_id);
      }
    } else {
      $template = $templatesModel->getById($rating['template_id']);
    }

    $view = wa()->getView();
    foreach ($params as $variable_name => $value) {
      $view->assign($variable_name, $value);
    }

    if ($rating['filter'] && !is_null($rating['filter_codes'])) {
      $filters = self::getFilters($rating, $products);
      $view->assign('seoratings_filters', $filters);
    }

    $rating['related_ratings'] = $seoratingsRatingModel->getRelatedRatings($rating['related_ratings']);

    if ($rating['sibling_positions']) {
      $seoratingsRatingProductsModel->findPositionsInOtherRatings($rating, $products);
    }

    if ($rating['position_shift'] && (int)$rating['base_rating']) {
      $baseRating = $seoratingsRatingModel->getById($rating['base_rating']);
      $rating['base_rating'] = $baseRating;

      if ($baseRating['type'] === 'set') {
        $baseRatingProducts = $seoratingsRatingProductsModel->getProductsFromSet($baseRating['set_id']);
      } else {
        $baseRatingProducts = $seoratingsRatingProductsModel->getRatingProducts($baseRating['id']);
      }

      if ($baseRatingProducts) {
        $view->assign('base_rating_products', $baseRatingProducts);
      }
    }

    $view->assign('products', $products);
    $view->assign('rating', $rating);
    $view->assign('template', $template);
    $view->assign('theme_id', waRequest::getTheme());

    if ($template === null) {
      if ($template_id) {
        $rating['template_id'] = $template_id;
      }

      $template_path = wa()->getAppPath("plugins/seoratings/templates/actions/frontend/templates/{$rating['template_id']}.html", 'shop');

      if (file_exists($template_path)) {
        wa()->getResponse()->addCss(wa()->getAppStaticUrl('shop', true) . "plugins/seoratings/css/frontend/{$rating['template_id']}.css");

        return $view->fetch($template_path);
      }
      wa()->getResponse()->addCss(wa()->getAppStaticUrl('shop', true) . "plugins/seoratings/css/frontend/standard.css");

      return $view->fetch(wa()->getAppPath("plugins/seoratings/templates/actions/frontend/templates/standard.html", 'shop'));
    } else {
      $html = 'string:';
      $html .= '{literal}<style>' . $template['css'] . '</style>{/literal}';
      $html .= $template['html'];

      return $view->fetch($html);
    }
  }

  /**
   * @param array $rating
   * @param array $products
   *
   * @return array
   * @throws waException
   */
  public static function getFilters($rating, $products)
  {
    $feature_model = new shopFeatureModel();

//    TODO: filter selection
//    $filter_ids = explode(',', $category['filter']);
//    $feature_model = new shopFeatureModel();
//    $features = $feature_model->getById(array_filter($filter_ids, 'is_numeric'));

    $features = $feature_model->getFilterFeatures([
      'code' => $rating['filter_codes'],
      'frontend' => true,
    ]);

    $collection = new shopProductsCollection(array_map(function ($product) {
      return (int)$product['id'];
    }, $products));

    $filters = [];
    $feature_map = [];

    $filter_ids = array_map(function ($filter) {
      return $filter['id'];
    }, $features);

    $feature_model = new shopFeatureModel();
    $features = $feature_model->getById(array_filter($filter_ids, 'is_numeric'));

    if ($features) {
      $features = $feature_model->getValues($features);
    }

    $category_value_ids = $collection->getFeatureValueIds(false);

    foreach ($filter_ids as $fid) {
      $range = $collection->getPriceRange();
      if ($range['min'] != $range['max']) {
        $filters['price'] = [
          'name' => 'Цена',
          'type' => 'range',
          'code' => 'price',
          'min' => shop_currency($range['min'], null, null, false),
          'max' => shop_currency($range['max'], null, null, false),
        ];
      }

      if (isset($features[$fid]) && isset($category_value_ids[$fid])) {
        //set existing feature code with saved filter id
        $feature_map[$features[$fid]['code']] = $fid;
        $filter_code = $features[$fid]['code'];
        //set feature data
        $filters[$filter_code] = $features[$fid];
        $min = $max = $unit = null;

        foreach ($filters[$filter_code]['values'] as $v_id => $v) {

          //remove unused
          if (!in_array($v_id, $category_value_ids[$fid])) {
            unset($filters[$filter_code]['values'][$v_id]);
          } else {
            if ($v instanceof shopRangeValue) {
              $begin = self::getFeatureValue($v->begin);
              if (is_numeric($begin) && ($min === null || (float)$begin < (float)$min)) {
                $min = $begin;
              }
              $end = self::getFeatureValue($v->end);
              if (is_numeric($end) && ($max === null || (float)$end > (float)$max)) {
                $max = $end;
                if ($v->end instanceof shopDimensionValue) {
                  $unit = $v->end->unit;
                }
              }
            } else {
              $tmp_v = self::getFeatureValue($v);
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
        if (!$filters[$filter_code]['selectable'] && ($filters[$filter_code]['type'] == 'double' ||
            substr($filters[$filter_code]['type'], 0, 6) == 'range.' ||
            substr($filters[$filter_code]['type'], 0, 10) == 'dimension.')
        ) {
          if ($min == $max) {
            unset($filters[$filter_code]);
          } else {
            $type = preg_replace('/^[^\.]*\./', '', $filters[$filter_code]['type']);
            if ($type != 'double') {
              $filters[$filter_code]['base_unit'] = shopDimension::getBaseUnit($type);
              $filters[$filter_code]['unit'] = shopDimension::getUnit($type, $unit);
              if ($filters[$filter_code]['base_unit']['value'] != $filters[$filter_code]['unit']['value']) {
                $dimension = shopDimension::getInstance();
                $min = $dimension->convert($min, $type, $filters[$filter_code]['unit']['value']);
                $max = $dimension->convert($max, $type, $filters[$filter_code]['unit']['value']);
              }
            }
            $filters[$filter_code]['min'] = $min;
            $filters[$filter_code]['max'] = $max;
          }
        }
      }
    }

    if ($filters) {
      foreach ($filters as $field => $filter) {
        if ((isset($filters[$field]['values']) && (!count($filters[$field]['values']))) || $filter['type'] === 'text') {
          unset($filters[$field]);
        }
      }
    }

    $filters = array_map(function ($filter) {
      if (isset($filter['values'])) {
        foreach ($filter['values'] as $k => $v) {
          if (isset($filter['min'])) {
            $filter['type'] = 'range';
          } else {
            if ($filter['type'] === 'boolean') {
              $filter['type'] = 'radio';
            } elseif ($filter['type'] === 'color') {
              $filter['type'] = 'color';
            } else {
              $filter['type'] = 'checkbox';
            }
          }
          if (is_object($v) && $v instanceof shopColorValue) {
            $matches = [];
            preg_match_all('/style="background:(#[A-Za-x0-9]{3,6});"/', (string)$v, $matches);
            $color = $matches[1];
            $filter['values'][$k] = $color[0];
          } else {
            $filter['values'][$k] = (string)$v;
          }
        }
      }

      return $filter;
    }, $filters);

    return $filters;
  }

  /**
   * Return feature value.
   *
   * @param $v
   *
   * @return string
   */
  protected static function getFeatureValue($v)
  {
    if ($v instanceof shopDimensionValue) {
      return $v->value_base_unit;
    }
    if (is_object($v)) {
      return $v->value;
    }

    return $v;
  }

  /**
   * Display any rating.
   *
   * @param string|int $rating Rating to be displayed.
   * @param string|int|null $template_id Force template.
   * @param array $params Assign additional variables to the view.
   * @param bool $raw Display only rating content. Without descriptions, and other content.
   *
   * @return string
   * @throws SmartyException
   * @throws waException
   */
  public function displaySingleRating($rating, $template_id = 'table', $params = [], $raw = true)
  {
    $seoratingsRatingModel = new shopSeoratingsRatingModel();
    $seoratingsRatingProductsModel = new shopSeoratingsRatingProductsModel();
    if (is_string($rating)) {
      $rating = $seoratingsRatingModel->getByField('url', $rating);
    } else {
      $rating = $seoratingsRatingModel->getById($rating);
    }
    if (!$rating) {
      return '';
    }
    $products = $seoratingsRatingProductsModel->getRatingProducts($rating['id']);

    return self::display($rating, $products, $template_id, $params, $raw);
  }

  /**
   * Get rating for product.
   * Rating marked as "primary" takes precedence.
   *
   * @param $product
   * @param string $template_id
   * @param array $params
   * @param bool $raw
   *
   * @return array|string
   * @throws SmartyException
   * @throws waException
   */
  public static function displayRatingForSingleProduct($product, $template_id = 'table', array $params = [], bool $raw = true)
  {
    $rating = null;
    $seoratingsRatingModel = new shopSeoratingsRatingModel();
    $seoratingsRatingProductsModel = new shopSeoratingsRatingProductsModel();
    $productRatings = $seoratingsRatingModel->getProductRatings($product['id']);

    if (!count($productRatings)) {
      return '';
    }

    foreach ($productRatings as $_rating) {
      if ($_rating['primary_rating']) {
        $rating = $_rating;
      }
    }
    if (!$rating) {
      $rating = $productRatings[0];
    }

    $rating['rating_for_product_id'] = $product['id'];
    $products = $seoratingsRatingProductsModel->getRatingProducts($rating['id']);

    return shopSeoratingsPlugin::display($rating, $products, $template_id, $params, $raw);
  }

  /**
   * Display seoratings on product page through the hooks.
   *
   * @param array $product
   *
   * @return array
   */
  public function frontendProduct(shopProduct $product): array
  {
    $settings = $this->getSettings();

    if ($settings['hooks'] === null) {
      return [];
    }
    $ratingModel = new shopSeoratingsRatingModel();
    $productRatings = $ratingModel->getProductRatings($product['id']);

    if (!count($productRatings)) {
      return [];
    }

    $hooks = [];

    try {
      $view = wa()->getView();
      $view->assign('ratings', $productRatings);
      $view->assign('seoratings', $this);
      $result = $view->fetch('wa-apps/shop/plugins/seoratings/templates/actions/frontend/shop/product.html');
      if (in_array('frontend_product.block_aux', $settings['hooks'])) {
        $hooks['block_aux'] = $result;
      }
      if (in_array('frontend_product.block', $settings['hooks'])) {
        $hooks['block'] = $result;
      }
    } catch (SmartyException | waException $e) {
      shopSeoratingsLogger::log($e->getMessage());
    }

    if (count($hooks)) {
      $this->addCss("css/frontend/seoratings-widget.css");
    }

    return $hooks;
  }

  /**
   * Assign boolean key seoratings_in_rating if product is in any rating.
   *
   * @param array $params Hook params
   */
  public function frontendProducts(array $params): void
  {
    static $stylesIsLoaded = false;

    $settings = $this->getSettings();
    if (!empty($settings['category_badge']) && !empty($params['products']) && is_array($params['products'])) {
      $ids = array_keys($params['products']);
      $ratingProductsModel = new shopSeoratingsRatingProductsModel();
      $products = $ratingProductsModel->getProductsByIds($ids);
      if (count($products)) {
        if (!$stylesIsLoaded) {
//          $this->addCss("css/standard.css");
          $stylesIsLoaded = true;
        }

        foreach ($params['products'] as &$product) {
          $product['seoratings_in_rating'] = false;
          foreach ($products as $key => $item) {
            if ($product['id'] === $item['product_id']) {
              $products[$key];
              $product['seoratings_in_rating'] = true;
              break;
            }
          }
        }
        unset($product);
      }
    }
  }

  /**
   * Display random rating list in category.
   *
   * @return string
   * @throws SmartyException
   */
  public function frontendNav(): ?string
  {
    $settings = $this->getSettings();

    if ($settings['hooks'] === null || !in_array('frontend_nav', $settings['hooks'])) {
      return null;
    }

    $ratingModel = new shopSeoratingsRatingModel();
    $ratingId = waRequest::param('category_id', null, waRequest::TYPE_INT);
    $ratings = [];

    if ($ratingId) {
      $ratings = $ratingModel->getCategoryRatings($ratingId);
    }

    if (!$ratings) {
      $ratings = $ratingModel->getRandomRatings();
    }

    try {
      $view = wa()->getView();
      $view->assign('ratings', $ratings);

      return $view->fetch('wa-apps/shop/plugins/seoratings/templates/actions/frontend/shop/sidebar.html');
    } catch (waException $e) {
      return '';
    }
  }

  /**
   * Compile rating url.
   *
   * @param array $rating
   *
   * @return string
   */
  public function getRatingUrl(array $rating): string
  {
    $slug = $this->getSlug($this->getSettings());
    $url = ltrim($rating['url'], '/');

    return "{$slug}/{$url}";
  }

  /**
   * Return price range for product's SKUs.
   *
   * @param array $skus
   *
   * @return array [
     *  'min' => Minimum price
     *  'max' => Maximum price
     *  'single_price' => If min === max -> min
   *  ]
   */
  public static function getSkusPriceRage(array $skus): array
  {
    $single_price = false;
    $total = 0;
    $min = PHP_INT_MAX;
    $max = PHP_INT_MIN;
    foreach ($skus as $sku) {
      $total += floatval($sku['price']);
      if (floatval($sku['price']) < $min) {
        $min = floatval($sku['price']);
      }
      if (floatval($sku['price']) > $max) {
        $max = floatval($sku['price']);
      }
    }
    if ($min == $max) {
      $single_price = $min;
    }

    return compact('min', 'max', 'single_price');
  }

  /**
   * Get breadcrumbs for given action.
   *
   * @param array|null $rating
   * @param string $action
   *
   * @return string
   * @throws SmartyException
   * @throws waException
   */
  public static function getBreadcrumbs(?array $rating = null, string $action = 'rating'): string
  {
    $plugin = wa('shop')->getPlugin('seoratings');
    $frontendUrl = self::getRootFrontendUrl();
    $breadcrumbs = [
      ['name' => $plugin->getSettings('breadcrumbs_category_title'), 'url' => $frontendUrl],
    ];
    if ($rating) {
      $breadcrumbs[] = ['name' => $rating['title'], 'url' => $frontendUrl . rtrim($rating['url'], '/') . '/'];
    }
    $view = wa()->getView();
    $view->assign('container_class', $action === 'rating' ? 'seoratings__standard' : 'seoratings-list-ratings');
    $view->assign('plugin_breadcrumbs', $breadcrumbs);

    return $view->fetch('wa-apps/shop/plugins/seoratings/templates/actions/frontend/partials/breadcrumbs.html');
  }

  /**
   * Display ratings for given category id.
   *
   * @param int $categoryId
   *
   * @return string|void
   * @throws SmartyException
   */
  public static function displayCategoryRatings(int $categoryId): string
  {
    $ratings = self::getCategoryRatings($categoryId);
    if (!$ratings) {
      return '';
    }
    try {
      $view = wa()->getView();
      $view->assign('ratings', $ratings);

      return $view->fetch('wa-apps/shop/plugins/seoratings/templates/actions/frontend/shop/category.html');
    } catch (waException $e) {
      return '';
    }
  }

  /**
   * Display random ratings limited by param $limit = 3;
   *
   * @param int $limit
   *
   * @return string|void
   * @throws SmartyException
   */
  public static function displayRandomRatings(int $limit = 3): string
  {
    $ratingModel = new shopSeoratingsRatingModel();
    $ratings = $ratingModel->getRandomRatings($limit);
    if (!$ratings) {
      return '';
    }
    try {
      $view = wa()->getView();
      $view->assign('ratings', $ratings);

      return $view->fetch('wa-apps/shop/plugins/seoratings/templates/actions/frontend/shop/category.html');
    } catch (waException $e) {
      return '';
    }
  }

  /**
   * Get ratings for given category.
   *
   * @param int $categoryId
   *
   * @return array Array of ratings.
   */
  public static function getCategoryRatings(int $categoryId): array
  {
    $ratingModel = new shopSeoratingsRatingModel();

    return $ratingModel->getCategoryRatings($categoryId);
  }
}

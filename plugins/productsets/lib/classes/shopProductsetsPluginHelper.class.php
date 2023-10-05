<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopProductsetsPluginHelper
{
    private static $models = array();

    /**
     * Helper for displaying sets.
     * Available variants:
     *     {shopProductsetsPluginHelper::show(2)} - set ID equals 2
     *     {shopProductsetsPluginHelper::show('hey')} - demand ID equals hey. Check tab "Display"
     *     {shopProductsetsPluginHelper::show($product)} - show all sets for product
     *     {shopProductsetsPluginHelper::show($product, $params)} - show all sets for product. Add params.
     *     {shopProductsetsPluginHelper::show($category)} - show all sets for category
     *     {shopProductsetsPluginHelper::show($category, $params)} - show all sets for category. Add params.
     *     {shopProductsetsPluginHelper::show(2, $product)} - set ID equals 2 and product included
     *     {shopProductsetsPluginHelper::show(2, $product, $params)} - set ID equals 2 and product included. Add params.
     *     {shopProductsetsPluginHelper::show(2, $category)} - set ID equals 2. Also check, if set available for category
     *     {shopProductsetsPluginHelper::show(2, $category, $params)} - set ID equals 2. Also check, if set available for category. Add params.
     *     {shopProductsetsPluginHelper::show(2, $product, $category)} - set ID equals 2 and product included. Also check, if set available for category
     *     {shopProductsetsPluginHelper::show(2, $product, $category, $params)} - set ID equals 2 and product included. Also check, if set available for category. Add params.
     *
     * @param $id
     * @param null $product
     * @param array $category
     * @param array $params
     * @return string
     */
    public static function show($id = null, $product = null, $category = [], $params = [])
    {
        // Разрешаем вывод только из шаблона
        if (!waConfig::get('is_template')) {
            return '';
        }

        $clean_params = [];

        // Первый параметр
        if (!is_array($id) && !is_object($id)) {
            // Если нет id набора и не указан товар, пытаемся получить товар из шаблона
            if (!$id && !$product) {
                $storefront_view = wa()->getView();
                $product = $storefront_view->getVars('product');
            } // Если ID строчный, значит используется отображение по требованию
            elseif (!intval($id)) {
                $clean_params['ondemand'] = waLocale::transliterate($id);
            }  // Отображение конкретного набора
            elseif (intval($id)) {
                $clean_params['set_id'] = $id;
            }
        } elseif ($id) {
            if (is_array($product)) {
                $clean_params += $product;
            }
            $category = $params = [];
            if (isset($id['left_key'])) {
                $clean_params['category'] = $id;
                $product = null;
            } else {
                $product = $id;
            }
        }

        // Если вторым параметром передана категория
        if ($product && isset($product['left_key'])) {
            $clean_params['category'] = $product;
            $product = null;

            if (is_array($category) && !isset($category['left_key'])) {
                $params = $category;
            }
        } elseif ($product) {
            if (isset($category['left_key'])) {
                $clean_params['category'] = $category;
            } elseif (is_array($category)) {
                $params = $category;
            }
        } elseif ($product === null && isset($category['left_key'])) {
            $clean_params['category'] = $category;
        }

        if ($params && is_array($params)) {
            $clean_params += $params;
        }

        return (new shopProductsetsPluginDisplay())->show($product, $clean_params);
    }

    /**
     * Get storefronts with routes
     *
     * @return array
     * @throws waException
     */
    public function getStorefronts()
    {
        wa('site');
        $routes = array();
        $domains = (new siteDomainModel())->getAll('id');
        foreach ($domains as $domain) {
            $domain_routes = $this->getRoutes($domain['name']);
            if ($domain_routes) {
                $routes[$domain['id']] = array(
                    "name" => $domain['name'],
                    "routes" => $domain_routes
                );
            }
        }
        return $routes;
    }

    /**
     * Get domain routes
     *
     * @param string $domain
     * @return array
     */
    private function getRoutes($domain)
    {
        $storefronts = array();
        $routing = wa()->getRouting();

        $routes = $routing->getRoutes($domain);

        foreach ($routes as $route) {
            if (!isset($route['app']) || $route['app'] !== 'shop') {
                continue;
            }
            $route_name = $domain . '/' . $route['url'];
            $route_hash = $this->getRouteHash($route_name);
            $storefronts[$route_hash] = $route_name;
        }

        return $storefronts;
    }

    private function getRouteHash($route)
    {
        return md5($route);
    }

    /**
     * Get active storefront
     *
     * @return string
     */
    public function getActiveStorefront()
    {
        static $storefront;

        if ($storefront === null) {
            $domain = wa()->getRouting()->getDomain(null, true);
            $route_url = wa()->getRouting()->getRoute();
            $storefront = $this->getRouteHash($domain . '/' . $route_url['url']);
        }

        return $storefront;
    }

    /**
     * Create hash from parameters. Uses for saving request values by hash
     *
     * @return string
     */
    public function getRequestHash()
    {
        $args = func_get_args();
        if ($args) {
            $string = '';
            foreach ($args as $arg) {
                if (is_array($arg)) {
                    sort($arg);
                    $string .= json_encode($arg);
                } elseif (is_bool($arg)) {
                    $string .= !!$arg;
                } else {
                    $string .= $arg;
                }
            }

            return md5($string);
        }
        return '';
    }

    protected static function getModel($name)
    {
        if (!isset(self::$models[$name])) {
            if (in_array($name, array('category', 'product'))) {
                $class_name = 'shop' . ucfirst($name) . 'Model';
                self::$models[$name] = new $class_name();
            }
        }
        return self::$models[$name];
    }

    /**
     * Get plugin settings
     *
     * @return array
     */
    public function getSettings()
    {
        static $settings = null;
        if ($settings === null) {
            $settings = wa('shop')->getPlugin('productsets')->getSettings();
        }
        return $settings;
    }

    /**
     * Get currency information
     *
     * @return array
     */
    public function getCurrencyInfo()
    {
        $currency = waCurrency::getInfo(wa('shop')->getConfig()->getCurrency(false));
        $locale = waLocale::getInfo(wa()->getLocale());
        return array(
            'code' => $currency['code'],
            'sign' => $currency['sign'],
            'sign_html' => !empty($currency['sign_html']) ? $currency['sign_html'] : $currency['sign'],
            'sign_position' => isset($currency['sign_position']) ? $currency['sign_position'] : 1,
            'sign_delim' => isset($currency['sign_delim']) ? $currency['sign_delim'] : ' ',
            'decimal_point' => $locale['decimal_point'],
            'frac_digits' => $locale['frac_digits'],
            'thousands_sep' => $locale['thousands_sep'],
        );
    }

    /**
     * Get locale string for JS backend or frontend
     *
     * @param bool $backend
     * @return array|false|string
     */
    public function getJsLocaleStrings($backend = true)
    {
        // JS Локализция
        $name = 'productsets';
        $js_locale_path = wa()->getAppPath("plugins/{$name}/locale/" . wa()->getLocale() . "/LC_MESSAGES/shop_{$name}_js_" . ($backend ? 'backend' : 'frontend') . ".json", 'shop');
        $js_locale_strings = json_encode('{}');
        if (file_exists($js_locale_path)) {
            $js_locale_strings = file_get_contents($js_locale_path);
        }
        return $js_locale_strings;
    }

    /**
     * Get limitations for sets by storefront
     *
     * @param array|int $set_ids
     * @return array
     */
    public function getSetStorefrontLimitations($set_ids)
    {
        $result = [];
        $storefront_model = new shopProductsetsStorefrontPluginModel();

        $sql = "SELECT * FROM {$storefront_model->getTableName()} WHERE productsets_id IN (?)";
        foreach ($storefront_model->query($sql, [(array) $set_ids]) as $r) {
            if (!isset($result[$r['productsets_id']])) {
                $result[$r['productsets_id']] = ['storefront' => [], 'operator' => $r['operator']];
            }
            $result[$r['productsets_id']]['storefront'][] = $r['storefront'];
        }
        return $result;
    }

    /**
     * Get frontend templates, which user can change
     *
     * @param bool $with_system_templates
     * @return array|mixed
     */
    public function getTemplates($with_system_templates = false)
    {
        $config = include(wa()->getAppPath('plugins/productsets/lib/config/config.php'));
        $templates = [];
        if (isset($config['templates'])) {
            $templates = $config['templates'];
            foreach ($templates as $k => $template) {
                $copy_template_path = wa()->getDataPath('plugins/productsets/templates/frontend/' . $k . '.html', false, 'shop', false);
                $templates[$k]['frontend_path'] = $template['path'];
                if (file_exists($copy_template_path)) {
                    $templates[$k]['changed'] = $templates[$k]['frontend_path'] = $copy_template_path;
                }
            }
        }
        if ($with_system_templates && isset($config['system_templates'])) {
            foreach ($config['system_templates'] as $k => $template) {
                $templates[$k]['frontend_path'] = $template['path'];
            }
        }

        return $templates;
    }

    public static function shop_currency($n, $in_currency = null, $out_currency = null, $format = true)
    {
        static $config;
        static $primary;
        static $currency;
        static $wa;

        if (is_array($in_currency)) {
            $options = $in_currency;
            $in_currency = ifset($options, 'in_currency', null);
            $out_currency = ifset($options, 'out_currency', null);
            if (array_key_exists('format', $options)) {
                $format = $options['format']; // can't use ifset because null is a valid value
            } else {
                $format = true;
            }
        }

        if ($config === null) {
            $wa = wa('shop');
            /**
             * @var shopConfig $config
             */
            $config = $wa->getConfig();

            // primary currency
            $primary = $config->getCurrency(true);

            // current currency (in backend - it's primary, in frontend - currency of storefront)
            $currency = $config->getCurrency(false);
        }

        if (!$in_currency) {
            $in_currency = $primary;
        }
        if ($in_currency === true || $in_currency === 1) {
            $in_currency = $currency;
        }
        if (!$out_currency) {
            $out_currency = $currency;
        }

        if ($in_currency != $out_currency) {
            $currencies = $wa->getConfig()->getCurrencies(array($in_currency, $out_currency));
            if (isset($currencies[$in_currency]) && $in_currency != $primary) {
                $n = $n * $currencies[$in_currency]['rate'];
            }
            if ($out_currency != $primary) {
                $n = $n / ifempty($currencies[$out_currency]['rate'], 1.0);
            }
        }

        if (($format !== null) && ($info = waCurrency::getInfo($out_currency)) && isset($info['precision'])) {
            $n = round($n, $info['precision']);
        }

        if ($format === 'h') {
            return wa_currency_html($n, $out_currency);
        } elseif ($format) {
            if (empty($options['extended_format'])) {
                return wa_currency($n, $out_currency);
            } else {
                return waCurrency::format($options['extended_format'], $n, $currency);
            }
        } else {
            return str_replace(',', '.', $n);
        }
    }

    public static function shop_currency_html($n, $in_currency = null, $out_currency = null, $format = 'h')
    {
        if (is_array($in_currency)) {
            $in_currency += array(
                'format' => $format,
            );
        }
        return self::shop_currency($n, $in_currency, $out_currency, $format);
    }

    /**
     * Round function
     *
     * @param float $amount
     * @param string $rounding
     * @return float
     */
    public static function round($amount, $rounding = 'not')
    {
        switch ($rounding) {
            case 'ceil':
                return ceil($amount);
            case 'floor':
                return floor($amount);
            case 'round':
                return round($amount);
            case 'tens':
                return round($amount, -1);
            case 'hund':
                return round($amount, -2);
            case 'dec1':
                return round($amount, 1);
            case 'dec2':
                return round($amount, 2);
            default:
                return $amount;
        }
    }

}

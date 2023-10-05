<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopQuickorderPluginHelper
{

    private static $_instance = null;

    /**
     * Decode JSON object
     *
     * @param string|array $json
     * @return array
     */
    public static function decode($json)
    {
        return is_string($json) ? json_decode($json) : $json;
    }

    /**
     * Decode JSON object to array
     *
     * @param string|array $json
     * @return array
     */
    public static function decodeToArray($json)
    {
        return is_string($json) ? json_decode($json, true) : $json;
    }

    /**
     * Returns a singleton of the shopQuickorderPluginHelper.
     *
     * @return shopQuickorderPluginHelper
     */
    private static function getInstance()
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Get all payment methods
     *
     * @return array
     */
    public function getPaymentMethods()
    {
        // Плагины оплаты
        $plugins = shopPayment::getList();
        $instances = (new shopPluginModel())->listPlugins(shopPluginModel::TYPE_PAYMENT, array('all' => true));
        foreach ($instances as $k => $instance) {
            if (!isset($plugins[$instance['plugin']])) {
                unset($instances[$k]);
                continue;
            }
        }
        return $instances;
    }

    /**
     * Get all shiping methods
     *
     * @return array
     */
    public function getShippingMethods()
    {
        // Плагины доставки
        $plugins = shopShipping::getList();
        $instances = (new shopPluginModel())->listPlugins(shopPluginModel::TYPE_SHIPPING, array('all' => true));
        foreach ($instances as $k => $instance) {
            if (!isset($plugins[$instance['plugin']])) {
                unset($instances[$k]);
                continue;
            }
        }
        return $instances;
    }

    /**
     * Get storefronts with routes
     *
     * @return array
     */
    public function getStorefronts()
    {
        wa('site');
        $routes = array();
        $domains = (new siteDomainModel())->getAll('id');
        foreach ($domains as $domain) {
            $domain_routes = $this->getRoutes($domain);
            if ($domain_routes) {
                $routes[$domain['id']] = array(
                    "name" => ifempty($domain, 'title', $domain['name']),
                    "routes" => $domain_routes
                );
            }
        }
        return $routes;
    }

    /**
     * Get domain routes
     *
     * @param array $domain
     * @return array
     */
    private function getRoutes($domain)
    {
        $storefronts = array();
        $routing = wa()->getRouting();

        $routes = $routing->getRoutes($domain['name']);

        foreach ($routes as $route) {
            if (!isset($route['app']) || $route['app'] !== 'shop') {
                continue;
            }
            $route_hash = $this->getRouteHash($domain['name'] . '/' . $route['url']);
            $route_name = ifempty($domain, 'title', $domain['name']) . '/' . $route['url'];
            $storefronts[$route_hash] = $route_name;
        }

        return $storefronts;
    }

    private function getRouteHash($route)
    {
        return md5($route);
    }

    /**
     * Remove port from domain
     *
     * @return string
     */
    public function getDomain()
    {
        $domain = wa()->getConfig()->getDomain();
        if (strpos($domain, ":") !== false) {
            $domain = substr($domain, 0, strpos($domain, ":"));
        }
        return $domain;
    }

    /**
     * Get active storefront
     *
     * @return string
     */
    public function getActiveStorefront()
    {
        $domain = wa()->getRouting()->getDomain(null, true);
        $route_url = wa()->getRouting()->getRoute();

        return $this->getRouteHash($domain . '/' . $route_url['url']);
    }

    /**
     * Get plugin settings
     *
     * @param string $name
     * @return array|string
     */
    public static function getSettings($name = '')
    {
        static $settings = null;
        if ($settings === null) {
            // Если плагин не доступен, не даем информацию о его настройках
            if (!shopQuickorderPlugin::isEnable()) {
                $settings = array();
                return $settings;
            }

            $instance = self::getInstance();
            $model = new shopQuickorderPluginSettingsModel();
            // Получаем настройки плагина для текущей витрины
            $settings = $model->getSettings($instance->getActiveStorefront());
            // Если настройки для витрины отключены, тогда используем общие настройки
            if (empty($settings['status'])) {
                $settings = $model->getSettings();
            }
        }
        return $name ? (isset($settings[$name]) ? $settings[$name] : '') : $settings;
    }

    /**
     * Get frontend templates, which user can change
     *
     * @return array|mixed
     */
    public function getTemplates($settings = [])
    {
        $config = include(wa()->getAppPath('plugins/quickorder/lib/config/templates.php'));
        $templates = [];
        if (isset($config['templates'])) {
            $templates = $config['templates'];
            foreach ($templates as $k => $template) {
                $copy_template = ifempty($settings, $k . '_tmpl', '');
                if ($copy_template) {
                    $templates[$k]['changed'] = $templates[$k]['frontend_template'] = $copy_template;
                } else {
                    $templates[$k]['frontend_template'] = file_get_contents($template['path']);
                }
            }
        }

        return $templates;
    }

    /**
     * Get product information
     *
     * @param int|array|shopProduct $p
     * @return array
     */
    public function getProduct($p)
    {
        if (is_int($p)) {
            $product = (new shopProduct($p))->getData();
        } else {
            $product = ($p instanceof shopProduct) ? $p->getData() : $p;
        }
        return $product;
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
     * Check if flexdiscount or delpayfilter plugins are  using
     */
    public function usingPlugins()
    {
        // Список всех плагинов
        $plugins = wa()->getConfig()->getPlugins();
        $settings = self::getSettings();

        if (
            (isset($plugins['flexdiscount']) && method_exists('shopFlexdiscountPlugin', 'isEnabled') && shopFlexdiscountPlugin::isEnabled())
            || (isset($plugins['delpayfilter']) && !empty($settings['use_delpayfilter']))
        ) {
            return 1;
        }
        return 0;
    }

    /**
     * Get affiliate data
     *
     * @param shopQuickorderPluginCart $cart
     * @param array $form_settings
     * @param null|array $workflow
     * @return array
     */
    public function getAffiliate($cart, &$form_settings, $workflow = null)
    {
        $affiliate = $cart->getAffiliateVars();
        // Интеграция с Гибкими скидками
        if (method_exists('shopFlexdiscountPlugin', 'isEnabled') && shopFlexdiscountPlugin::isEnabled()) {
            if ($workflow === null) {
                $workflow = shopFlexdiscountData::getOrderCalculateDiscount();
            }
            $affiliate['add_affiliate_bonus'] = $workflow['affiliate'] + (float) $affiliate['add_affiliate_bonus'];
        }
        if (!empty($form_settings['affiliate_text'])) {
            $form_settings['affiliate_text'] = str_replace(array('$bonus', '$discount'), array($affiliate['affiliate_bonus'], $affiliate['affiliate_discount']), $form_settings['affiliate_text']);
        }
        if (!empty($form_settings['affiliate_info']) && !empty($affiliate['add_affiliate_bonus'])) {
            $form_settings['affiliate_info'] = str_replace('$points', $affiliate['add_affiliate_bonus'], $form_settings['affiliate_info']);
        }
        return $affiliate;
    }

    /**
     * Get float value from string
     *
     * @param string $value
     * @return float
     */
    public static function floatVal($value)
    {
        return floatval(str_replace(',', '.', $value));
    }

    /**
     * Add slash at the end of url
     *
     * @param string $url
     * @return string
     */
    public function cleanUrl($url)
    {
        if (substr($url, -1) !== '/') {
            $url .= '/';
        }
        return $url;
    }

    public static function getDefaultSuccessMsg()
    {
        return '<h2>' . _w('Thank you!') . '</h2>' . '<p>' . _w('We successfully accepted your order, and will contact you asap.') . '</p>' . '<p>' . _w('Your order number is ') . ' <strong>{$order.id}</strong>.' . '</p>';
    }

    /**
     * Get analytics params
     *
     * @param array $settings
     * @param string|null $type - product|cart
     * @return array
     */
    public function getAnalytics($settings, $type = null)
    {
        $vars = array('ga_counter' => '', 'ya_counter' => '', 'yaecom' => '', 'yaecom_goal_id' => '', 'yaecom_container' => '', 'ya_fopen' => '', 'ya_submit' => '', 'ya_submit_error' => '', 'ga_category_fopen' => '', 'ga_action_fopen' => '', 'ga_category_submit' => '', 'ga_action_submit' => '', 'ga_category_submit_error' => '', 'ga_action_submit_error' => '');
        $analytics = array('cart' => $vars, 'product' => $vars);

        foreach ($vars as $k => $var) {
            if (isset($settings['product'][$k])) {
                $analytics['product'][$k] = $settings['product'][$k];
            }
            if (!empty($settings['shared_analytics_settings'])) {
                $analytics['cart'][$k] = $analytics['product'][$k];
            } elseif (isset($settings['cart'][$k])) {
                $analytics['cart'][$k] = $settings['cart'][$k];
            }
        }
        return $type ? $analytics[$type] : $analytics;
    }

    /**
     * Check, if product is availible
     * @param array $product
     * @return boolean
     */
    public function isAvailable($product)
    {
        $product_available = false;
        $ignore_stock_count = wa('shop')->getConfig()->getGeneralSettings('ignore_stock_count');
        if (isset($product['skus'])) {
            if (count($product['skus']) > 1) {
                foreach ($product['skus'] as $ps) {
                    $product_status = isset($product['status']) ? $product['status'] : (isset($product['product']['status']) ? $product['product']['status'] : 0);
                    $is_available = $product_status && $ps['available'] && ($ignore_stock_count || $ps['count'] === null || $ps['count'] > 0);
                    if ($is_available) {
                        return true;
                    }
                }
                return $product_available;
            } else {
                $sku = $product['skus'][$product['sku_id']];
                $product_available = $product['status'] && $sku['available'] && ($ignore_stock_count || $sku['count'] === null || $sku['count'] > 0);
            }
        } else {
            $product_available = $ignore_stock_count || $product['count'] === null || $product['count'] > 0;
        }
        return $product_available;
    }

    /**
     * Locale strings for JS
     *
     * @return string
     */
    public function getLocaleMessages()
    {
        waSystem::pushActivePlugin('quickorder', 'shop');

        $messages = array(
            'Select product sku' => _wp('Select product sku'),
            'Product with the selected option combination is not available for purchase' => _wp('Product with the selected option combination is not available for purchase'),
            'This product is already selected' => _wp('This product is already selected'),
            'Fix the errors above' => _wp('Fix the errors above'),
            'The shopping cart is empty' => _wp('The shopping cart is empty'),
            'Wait, please... Redirecting' => _wp('Wait, please... Redirecting'),
            'Field is required' => _wp('Field is required'),
            'Fill in required fields' => _wp('Fill in required fields'),
            'Your order is empty' => _wp('Your order is empty'),
            'Fill in captcha field' => _wp('Fill in captcha field'),
            'Terms and agreement' => _wp('Terms and agreement'),
            'Phone format is not correct.<br>Use this one:' => _wp('Phone format is not correct.<br>Use this one:'),
            'Shipping method has errors. Please, fix them.' => _wp('Shipping method has errors. Please, fix them.'),
            'Payment method has errors. Please, fix them.' => _wp('Payment method has errors. Please, fix them.'),
            'Minimal sum of order is %s' => _wp('Minimal sum of order is %s'),
            'Minimal sum of each product is' => _wp('Minimal sum of each product is'),
            'Minimal quantity of products is' => _wp('Minimal quantity of products is'),
            'Minimal quantity of each product is' => _wp('Minimal quantity of each product is'),
            'Product with the selected option combination is not available for purchase' => _wp('Product with the selected option combination is not available for purchase'),
            'Wait, please..' => _wp('Wait, please..')
        );

        waSystem::popActivePlugin();

        return json_encode($messages);
    }

}

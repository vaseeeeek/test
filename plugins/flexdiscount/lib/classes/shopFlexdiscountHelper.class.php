<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopFlexdiscountHelper extends shopFlexdiscountData
{

    /**
     * Workup products
     *
     * @param array $products
     */
    public static function workupProducts(&$products)
    {
        static $skus = null;

        $app = new shopFlexdiscountApp();
        $functions = $app::getFunction();

        $primary_curr = $app::get('system')['primary_currency'];
        $currencyr = $app::get('order.currency');

        // Массив товаров, для которых нужно получить доп информацию о ценах
        $find_skus = array();
        // Массив товаров, для которых необходимо получить общие данные из таблицы shopProduct
        $find_product_info = array();
        // Массив товар, в котором будут храниться ключи товаров.
        // В бекенде любимый Вебасист не передает для Услуг значение 'parent_id', в котором хранится ключ родительского товара текущей услуги.
        $product_k_item = array();

        // Массив дополнительных данных в товаре от плагина
        $item_flexdiscount_fields = $app::get('runtime.item_flexdiscount_fields', []);
        foreach ($products as $k => &$p) {
            if (!isset($p['sku_id']) && $p['type'] == 'product') {
                unset($products[$k]);
                continue;
            }
            // Обрабатываем услуги
            if ($p['type'] == 'service') {
                $parent_id = isset($p['parent_id']) ? $p['parent_id'] : (isset($product_k_item[$p['sku_id']]) ? $product_k_item[$p['sku_id']] : 0);
                if (isset($products[$parent_id])) {
                    if (!isset($products[$parent_id]['product_services'])) {
                        $products[$parent_id]['product_services'] = array();
                    }
                    $products[$parent_id]['product_services'][$p['service_id']][$p['service_variant_id']] = $p;
                    $products[$parent_id]['product_services'][$p['service_id']][$p['service_variant_id']]['price'] = $functions->shop_currency($p['price'], $p['currency'], $primary_curr, false);
                    $products[$parent_id]['product_services'][$p['service_id']][$p['service_variant_id']]['quantity'] = $products[$parent_id]['quantity'];
                }
                unset($products[$k]);
                continue;
            }
            $product_k_item[$p['sku_id']] = $k;
            // Цена товара в основной валюте
            if (!isset($p['primary_price'])) {
                $p['primary_price'] = $functions->shop_currency($p['price'], $p['currency'], $primary_curr, false);
            }

            // Цена товара в текущей валюте
            if (!isset($p['price_workuped'])) {
                $p['price'] = $functions->shop_currency($p['price'], $p['currency'], $currencyr, false);
                $p['price_workuped'] = 1;
            }

            // Валюта для зачеркнутой и закупочной цены
            $item_currency = $p['currency'];
            if (isset($p['unconverted_currency']) && isset($skus[$p['sku_id']])) {
                $item_currency = $p['unconverted_currency'];
            } elseif (isset($p['product']['unconverted_currency'])) {
                $item_currency = $p['product']['unconverted_currency'];
            } elseif (isset($skus[$p['sku_id']]) && isset($p['product']['currency'])) {
                $item_currency = $p['product']['currency'];
            }
            if (!isset($p['purchase_price_workuped'])) {
                // Закупочная цена товара в текущей валюте
                if (isset($skus[$p['sku_id']])) {
                    $purchase_price = (float) $skus[$p['sku_id']]['purchase_price'];
                    $p['purchase_price'] = $functions->shop_currency($purchase_price, $item_currency, $currencyr, false);
                    $p['primary_purchase_price'] = $functions->shop_currency($p['purchase_price'], $currencyr, $primary_curr, false);
                    $p['purchase_price_workuped'] = 1;
                } elseif ($skus === null) {
                    $find_skus[$p['sku_id']] = $p['sku_id'];
                }
            }
            if (!isset($p['compare_price_workuped'])) {
                // Зачеркнутая цена товара в текущей валюте
                if (isset($skus[$p['sku_id']])) {
                    $compare_price = (float) $skus[$p['sku_id']]['compare_price'];
                    $p['compare_price'] = $functions->shop_currency($compare_price, $item_currency, $currencyr, false);
                    $p['primary_compare_price'] = $functions->shop_currency($p['compare_price'], $currencyr, $primary_curr, false);
                    $p['compare_price_workuped'] = 1;
                } elseif ($skus === null) {
                    $find_skus[$p['sku_id']] = $p['sku_id'];
                }
            }
            if (!isset($p['properties_workuped'])) {
                $p['id'] = isset($p['id']) ? $p['id'] : 0;
                $p['product_id'] = isset($p['product_id']) ? (int) $p['product_id'] : (isset($p['product']['id']) ? (int) $p['product']['id'] : (int) $p['id']);
                $p['sku_code'] = isset($p['sku_code']) ? $p['sku_code'] : (isset($p['sku']) ? $p['sku'] : (isset($p['skus'][$p['sku_id']]['sku']) ? $p['skus'][$p['sku_id']]['sku'] : (isset($skus[$p['sku_id']]) ? $skus[$p['sku_id']]['sku'] : null)));
                $p['sku_name'] = isset($p['sku_name']) ? $p['sku_name'] : ((isset($p['skus'][$p['sku_id']]['name']) ? $p['skus'][$p['sku_id']]['name'] : (isset($p['name']) ? $p['name'] : '')));
                if (!isset($p['product'])) {
                    $find_product_info[$k] = $p['product_id'];
                }
                if ($p['sku_code'] === null && $skus === null) {
                    $find_skus[$p['sku_id']] = $p['sku_id'];
                } else {
                    $p['properties_workuped'] = 1;
                }
            }
            if ($skus !== null) {
                // Общее кол-во остатков для артикула
                $p['count'] = isset($skus[$p['sku_id']]['count']) ? $skus[$p['sku_id']]['count'] : 2147483647;
                $p['flexdiscount_minimal_discount_price'] = (float) ifset($p, 'flexdiscount_minimal_discount_price', ifset($p, 'product', 'flexdiscount_minimal_discount_price', 0.0));
                $p['flexdiscount_minimal_discount_currency'] = ifset($p, 'flexdiscount_minimal_discount_currency', ifset($p, 'product', 'flexdiscount_minimal_discount_currency', ''));

                $p['flexdiscount_item_discount'] = (float) ifset($p, 'product', 'flexdiscount_item_discount', ifset($p, 'flexdiscount_item_discount', 0.0));
                $p['flexdiscount_discount_currency'] = ifset($p, 'product', 'flexdiscount_discount_currency', ifset($p, 'flexdiscount_discount_currency', '%'));
                $p['flexdiscount_item_affiliate'] = (float) ifset($p, 'product', 'flexdiscount_item_affiliate', ifset($p, 'flexdiscount_item_affiliate', 0.0));
                $p['flexdiscount_affiliate_currency'] = ifset($p, 'product', 'flexdiscount_affiliate_currency', ifset($p, 'flexdiscount_affiliate_currency', '%'));
                // Минимальные цены товара
                if ($p['flexdiscount_minimal_discount_price'] === 0.0 && isset($skus[$p['sku_id']]['flexdiscount_minimal_discount_price'])) {
                    $p['flexdiscount_minimal_discount_price'] = $skus[$p['sku_id']]['flexdiscount_minimal_discount_price'];
                    $p['flexdiscount_minimal_discount_currency'] = $skus[$p['sku_id']]['flexdiscount_minimal_discount_currency'];
                }
                // Индивидуальная скидка
                if (isset($skus[$p['sku_id']]['flexdiscount_item_discount']) && $skus[$p['sku_id']]['flexdiscount_item_discount'] !== '') {
                    $p['flexdiscount_item_discount'] = (float) $skus[$p['sku_id']]['flexdiscount_item_discount'];
                    $p['flexdiscount_discount_currency'] = $skus[$p['sku_id']]['flexdiscount_discount_currency'];
                    if (isset($p['product']) && $p['product']['sku_id'] == $p['sku_id']) {
                        $p['product']['flexdiscount_item_discount'] = $p['flexdiscount_item_discount'];
                        $p['product']['flexdiscount_discount_currency'] = $p['flexdiscount_discount_currency'];
                    }
                }
                // Индивидуальные бонусы
                if (isset($skus[$p['sku_id']]['flexdiscount_item_affiliate']) && $skus[$p['sku_id']]['flexdiscount_item_affiliate'] !== '') {
                    $p['flexdiscount_item_affiliate'] = (float) $skus[$p['sku_id']]['flexdiscount_item_affiliate'];
                    $p['flexdiscount_affiliate_currency'] = $skus[$p['sku_id']]['flexdiscount_affiliate_currency'];
                    if (isset($p['product']) && $p['product']['sku_id'] == $p['sku_id']) {
                        $p['product']['flexdiscount_item_affiliate'] = $p['flexdiscount_item_affiliate'];
                        $p['product']['flexdiscount_affiliate_currency'] = $p['flexdiscount_affiliate_currency'];
                    }
                }
                $item_flexdiscount_fields[$p['sku_id']] = [
                    'flexdiscount_item_discount' => $p['flexdiscount_item_discount'],
                    'flexdiscount_discount_currency' => $p['flexdiscount_discount_currency'],
                    'flexdiscount_item_affiliate' => $p['flexdiscount_item_affiliate'],
                    'flexdiscount_affiliate_currency' => $p['flexdiscount_affiliate_currency'],
                ];
                $app->set('runtime.item_flexdiscount_fields', $item_flexdiscount_fields);
            } else {
                $find_skus[$p['sku_id']] = $p['sku_id'];
            }
            unset($p);
        }
        if ($find_product_info) {
            $collection = new shopFlexdiscountProductsCollection('id/' . implode(",", $find_product_info));
            $collection_products = $collection->getProducts('*', 0, $collection->count());
            foreach ($find_product_info as $k => $p_id) {
                $products[$k]['product'] = isset($collection_products[$p_id]) ? $collection_products[$p_id] : $products[$k];
            }
        }
        if ($find_skus) {
            $skus = (new shopProductSkusModel())->getByField('id', array_keys($find_skus), 'id');
            self::workupProducts($products);
        } else {
            $skus = null;
        }
        shopRounding::roundProducts($products);
    }

    /**
     * Get domain routes
     *
     * @param string $domain
     * @return array
     */
    public function getRoutes($domain)
    {
        $storefronts = array();
        $routing = shopFlexdiscountApp::get('system')['wa']->getRouting();

        $routes = $routing->getRoutes($domain);

        foreach ($routes as $route) {
            if (ifset($route, 'app', '') == 'shop') {
                $storefronts[] = $domain . '/' . $route['url'];
            }
        }

        return $storefronts;
    }

    /**
     * Remove port from domain
     *
     * @return string
     */
    public static function getDomain()
    {
        $domain = shopFlexdiscountApp::get('system')['config']->getDomain();
        if (strpos($domain, ":") !== false) {
            $domain = substr($domain, 0, strpos($domain, ":"));
        }
        return $domain;
    }

    public static function getCategoriesTree($cats)
    {
        $stack = array();
        $result = array();
        foreach ($cats as $c) {
            $c['childs'] = array();
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
                $i = count($stack[$l - 1]['childs']);
                $stack[$l - 1]['childs'][$i] = $c;
                $stack[] = &$stack[$l - 1]['childs'][$i];
            }
        }
        return $result;
    }

    public static function getCategoriesTreeOptionsHtml($cats, $level = 0, $selected = '')
    {
        $html = "";
        foreach ($cats as $c) {
            $html .= "<option value='" . $c['id'] . "'" . ($selected == $c['id'] ? " selected" : "") . ">";
            for ($i = 0; $i < $level; $i++) {
                $html .= "&nbsp;&nbsp;&nbsp;&nbsp;";
            }
            $html .= waString::escapeAll($c['name']);
            $html .= "</option>";
            if (!empty($c['childs'])) {
                $html .= self::getCategoriesTreeOptionsHtml($c['childs'], $level + 1, $selected);
            }
        }
        return $html;
    }

    public static function getSelectOptionsHtml($options, $selected = '')
    {
        $html = "";
        foreach ($options as $o) {
            $html .= "<option value='" . $o['id'] . "'" . ($selected == $o['id'] ? " selected" : "") . (!empty($o['class']) ? ' class="' . $o['class'] . '"' : '') . ">" . waString::escapeAll($o['name']) . "</option>";
        }
        return $html;
    }

    public static function getServicesHtml($data, $selected = '')
    {
        $html = "";
        foreach ($data['services'] as $s) {
            $html .= "<option" . ($s['selectable'] ? ' class="selectable"' : "") . " value='" . $s['id'] . "'" . ($selected == $s['id'] ? " selected" : "") . ">" . waString::escapeAll($s['name']) . "</option>";
        }
        return $html;
    }

    public static function getServicesVariantsHtml($data, $selected = '')
    {
        $html = "";
        foreach ($data['variants'] as $v) {
            if (!empty($data['services'][$v['service_id']]) && $data['services'][$v['service_id']]['selectable']) {
                $html .= "<option class='feature-" . $v['service_id'] . "' value='" . $v['id'] . "'" . ($selected == $v['id'] ? " selected" : "") . ">" . waString::escapeAll($v['name']) . "</option>";
            }
        }
        return $html;
    }

    public static function getFeaturesHtml($features, $selected = '')
    {
        $html = "";
        foreach ($features as $f) {
            if ($f['type'] == 'divider' || $f['type'] == '2d.double' || $f['type'] == '3d.double') {
                continue;
            }
            $base_unit = shopDimension::getBaseUnit($f['type']);
            $html .= "<option" . ($base_unit ? " data-base-unit = '" . $base_unit['title'] . "'" : "") . ' class="' . ($f['selectable'] ? 'selectable' : "") . ($base_unit ? ' dimension' : "") . '"' . " value='" . $f['id'] . "'" . ($selected == $f['id'] ? " selected" : "") . ">" . waString::escapeAll($f['name']) . ($f['code'] ? " (" . $f['code'] . ")" : "") . "</option>";
        }
        return $html;
    }

    public static function getFeaturesValuesHtml($values, $feature_id, $selected = '')
    {
        $html = "";
        if (!empty($values)) {
            foreach ($values as $val_id => $val) {
                $html .= "<option class='feature-" . $feature_id . "' value='" . $feature_id . '-' . $val_id . "'" . ($selected == ($feature_id . '-' . $val_id) ? " selected" : "") . ">" . waString::escapeAll($val) . "</option>";
            }
        }
        return $html;
    }

    public static function getStorefrontRoutesHtml($routes, $selected = '')
    {
        $html = "";
        foreach ($routes as $domain => $r) {
            foreach ($r as $route) {
                $html .= "<option class='domain-" . waString::escapeAll($domain) . "' value='" . waString::escapeAll($route) . "'" . ($selected == $route ? " selected" : "") . ">" . waString::escapeAll($route) . "</option>";
            }
        }
        return $html;
    }

    public static function getDynamicValuesHtml($values, $dynamic_id, $selected = '')
    {
        $html = "";
        if (!empty($values)) {
            foreach ($values as $val_id => $val) {
                $html .= "<option class='dynamic-" . $dynamic_id . "' value='" . $dynamic_id . '-' . $val_id . "'" . ($selected == ($dynamic_id . '-' . $val_id) ? " selected" : "") . ">" . waString::escapeAll($val) . "</option>";
            }
        }
        return $html;
    }

    public static function object_to_array($obj)
    {
        if (is_object($obj)) {
            $obj = (array) $obj;
        }
        if (is_array($obj)) {
            $new = array();
            foreach ($obj as $key => $val) {
                $new[$key] = self::object_to_array($val);
            }
        } else {
            $new = $obj;
        }
        return $new;
    }

    public static function getCouponStatus($coupon)
    {
        $today = time();
        $order_params = shopFlexdiscountApp::get('order.full');
        // Если начало действия купона еще не наступило
        if (!empty($coupon['start']) && ($today < strtotime($coupon['start']))) {
            return -1;
        }
        // Если срок действия купона истек
        if (!empty($coupon['end']) && ($today > strtotime($coupon['end']))) {
            return -2;
        }
        // Если достигнут предел по количеству использований купона
        if ($coupon['limit'] > 0 && $coupon['used'] >= $coupon['limit']) {
            return -3;
        }
        $user_limit = (new shopFlexdiscountCouponPluginModel())->getUserLimit($coupon['id'], $order_params);
        // Если достигнут предел по количеству использований купона для пользователя
        if ($coupon['user_limit'] > 0 && (int) $coupon['user_limit'] <= (int) $user_limit) {
            return -4;
        }

        return 1;
    }

    /**
     * Format product workflow
     *
     * @param array $workflow
     * @param int $sku_id
     * @param string $currency - product currency
     * @param array $product
     * @param array $order_params
     * @return array
     */
    public static function prepareProductWorkflow($workflow, $sku_id, $currency, $product, $order_params)
    {
        $product_workflow = array(
            'discount' => 0,
            'affiliate' => 0,
            'items' => array(),
            'product' => $product,
        );

        $settings = shopFlexdiscountApp::get('settings');
        $functions = shopFlexdiscountApp::getFunction();

        /* Создаем массив из ключей sku_id. Сейчас это item_id */
        $workflow_products = [];

        $item_flexdiscount_fields = shopFlexdiscountApp::get('runtime.item_flexdiscount_fields', []);
        if ($item_flexdiscount_fields[$product['sku_id']]) {
            $product = array_merge($product, $item_flexdiscount_fields[$product['sku_id']]);
        }

        foreach ($workflow['products'] as $workflow_product) {
            $workflow_products[$workflow_product['sku_id']] = $workflow_product;
        }

        if (!empty($workflow_products[$sku_id])) {
            $workflow_data = $workflow_products[$sku_id];

            $rules = array();

            $product_discount = $workflow_data['discount'];

            // Если обрабатываются Действующие скидки
            if ($workflow_data['rules']) {
                $rules = self::prepareDiscountRuleData('pdiscount', $workflow_data['rules'], $currency, array("rules" => $workflow['active_rules'], 'product' => $product));
                if (!empty($order_params['order']['items'][$workflow_data['item_id']]['quantity'])) {
                    foreach ($workflow_data['rules'] as $r) {
                        // Если количество товаров, на которые распространяется скидка по данному правилу меньше, чем общее количество товара в корзине,
                        // тогда считаем, что на данный товар скидка не должна распространяться
                        if ($r['quantity'] < $order_params['order']['items'][$workflow_data['item_id']]['quantity']) {
                            $workflow_data['total_discount'] -= $r['discount'];
                            $workflow_data['discount'] -= $r['discount'] / $r['quantity'];
                        }
                    }
                }

                $frontend_prices = ifempty($settings, 'frontend_prices', '');
                foreach ($workflow_data['rules'] as $rule_id => $rule) {
                    // Если у правила установлена настройка не изменять цены товаров, тогда отменяем скидку на товар.
                    // Это касается только отображения. Расчеты не изменятся
                    if ($frontend_prices && isset($workflow['active_rules'][$rule_id]['full_info']['change_price']) && !$workflow['active_rules'][$rule_id]['full_info']['change_price']) {
                        $product_discount -= $rule['quantity'] > 0 ? $rule['discount'] / $rule['quantity'] : 0;
                    }
                }
            }

            if (shopFlexdiscountApp::get('env')['is_frontend']) {
                // Устанавливаем наклейки для товаров
                (new shopFlexdiscountHelper())->getBadges($product, $workflow_data, $workflow['active_rules']);
            }

            $currency_rounding = ifempty($settings, 'currency_rounding', '');
            $product_price = $product['price'];

            /* Цена товара 45. Скидка 6%. При этой функции результат цены получается 50 - 3 = 47. Цена товара превращается в 50 */
            if (!empty($currency_rounding)) {
                $product_price = shopRounding::roundCurrency($functions->shop_currency($product_price, $product['currency'], $currency, false), $currency) - $product_discount;
            } else {
                $product_price = $functions->shop_currency($product_price, $product['currency'], $currency, false) - $product_discount;
            }

            if ($product_price < 0) {
                $product_price = 0;
            }

            $product_workflow = array(
                'discount' => $product_discount ? $functions->shop_currency($product_discount, $currency, $currency) : 0, // общая скидка для товара
                'discount_html' => $product_discount ? $functions->shop_currency_html($product_discount, $currency, $currency) : 0, // общая скидка для товара с символом рубля
                'clear_discount' => $product_discount, // общая скидка без валют
                'affiliate' => $workflow_data['affiliate'], // количество бонусов
                'currency' => $currency, // валюта товара
                'price' => $functions->shop_currency($product_price, $currency, $currency), // цена товара со скидкой
                'price_html' => $functions->shop_currency_html($product_price, $currency, $currency), // цена товара со скидкой с символом рубля
                'clear_price' => $product_price, // чистая цена без валют
                'real_price' => shopRounding::roundCurrency($functions->shop_currency($product['price'], $product['currency'], $currency, false), $currency),
                'product' => $product,
                'items' => shopFlexdiscountHelper::sortRules($rules),
            );
        }
        return $product_workflow;
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
        $wa = shopFlexdiscountApp::get('system')['wa'];
        $js_locale_path = $wa->getAppPath('plugins/flexdiscount/locale/' . $wa->getLocale() . '/LC_MESSAGES/shop_flexdiscount_js_backend.json', 'shop');
        $js_locale_strings = [];
        if (file_exists($js_locale_path)) {
            $js_locale_strings = file_get_contents($js_locale_path);
        }
        return $js_locale_strings;
    }

    private static function sortFunction($a, $b)
    {
        $a_val = (int) $a['sort'];
        $b_val = (int) $b['sort'];

        if ($a_val > $b_val)
            return 1;
        if ($a_val < $b_val)
            return -1;
        return 0;
    }

    /**
     * Sort rules by field `sort` by ascending
     *
     * @param array $rules
     * @return array
     */
    public static function sortRules($rules)
    {
        if ($rules) {
            uasort($rules, array('shopFlexdiscountHelper', 'sortFunction'));
        }
        return $rules;
    }

    /**
     * Get date interval for time periods
     *
     * @param string $type
     * @param array $interval
     * @return array
     */
    public static function getPeriod($type, $interval)
    {
        $period = array("start" => '', "end" => '');

        $interval['field1'] = isset($interval['field1']) ? ($type == 'period' ? $interval['field1'] : (int) $interval['field1']) : '';
        $interval['ext1'] = isset($interval['ext1']) ? ($type == 'period' ? $interval['ext1'] : (int) $interval['ext1']) : '';

        switch ($type) {
            case "ndays":
                $period['start'] = $interval['field1'] ? date("Y-m-d", strtotime("-" . $interval['field1'] . " days")) : '';
                $period['end'] = date("Y-m-d");
                break;
            case "pweek":
                $previous_week = strtotime("-1 week +1 day");
                $start_week = strtotime("last monday", $previous_week);
                $end_week = strtotime("next sunday", $start_week);

                $period['start'] = date("Y-m-d", $start_week);
                $period['end'] = date("Y-m-d", $end_week);
                break;
            case "pmonth":
                $period['start'] = date("Y-m-d", strtotime("first day of previous month"));
                $period['end'] = date("Y-m-d", strtotime("last day of previous month"));
                break;
            case "pquarter":
                $current_month = date('n');
                $current_year = date('Y');
                if ($current_month >= 1 && $current_month <= 3) {
                    $start_date = strtotime('1-October-' . ($current_year - 1));
                    $end_date = strtotime('31-December-' . ($current_year - 1));
                } else if ($current_month >= 4 && $current_month <= 6) {
                    $start_date = strtotime('1-January-' . $current_year);
                    $end_date = strtotime('31-March-' . $current_year);
                } else if ($current_month >= 7 && $current_month <= 9) {
                    $start_date = strtotime('1-April-' . $current_year);
                    $end_date = strtotime('30-July-' . $current_year);
                } else if ($current_month >= 10 && $current_month <= 12) {
                    $start_date = strtotime('1-July-' . $current_year);
                    $end_date = strtotime('30-September-' . $current_year);
                }

                $period['start'] = date("Y-m-d", $start_date);
                $period['end'] = date("Y-m-d", $end_date);
                break;
            case "p6m":
                $current_month = date('n');
                $current_year = date('Y');
                if ($current_month >= 1 && $current_month <= 6) {
                    $start_date = strtotime('1-July-' . ($current_year - 1));
                    $end_date = strtotime('31-December-' . ($current_year - 1));
                } else if ($current_month >= 7) {
                    $start_date = strtotime('1-January-' . $current_year);
                    $end_date = strtotime('30-June-' . $current_year);
                }

                $period['start'] = date("Y-m-d", $start_date);
                $period['end'] = date("Y-m-d", $end_date);
                break;
            case "p9m":
                $current_month = date('n');
                $current_year = date('Y');
                if ($current_month >= 1 && $current_month <= 9) {
                    $start_date = strtotime('1-April-' . ($current_year - 1));
                    $end_date = strtotime('31-December-' . ($current_year - 1));
                } else if ($current_month >= 10) {
                    $start_date = strtotime('1-January-' . $current_year);
                    $end_date = strtotime('30-September-' . $current_year);
                }

                $period['start'] = date("Y-m-d", $start_date);
                $period['end'] = date("Y-m-d", $end_date);
                break;
            case "p12m":
                $previous_year = date('Y') - 1;
                $period['start'] = date("Y-m-d", strtotime('first day of January ' . $previous_year));
                $period['end'] = date("Y-m-d", strtotime('last day of December ' . $previous_year));
                break;
            case "today":
                $period['start'] = date("Y-m-d");
                break;
            case "cweek":
                $period['start'] = date("Y-m-d", strtotime('monday this week'));
                break;
            case "cmonth":
                $period['start'] = date("Y-m-d", strtotime('first day of this month'));
                break;
            case "cquarter":
                $current_month = date('n');
                $current_year = date('Y');
                if ($current_month >= 1 && $current_month <= 3) {
                    $start_date = strtotime('1-January-' . $current_year);
                } else if ($current_month >= 4 && $current_month <= 6) {
                    $start_date = strtotime('1-April-' . $current_year);
                } else if ($current_month >= 7 && $current_month <= 9) {
                    $start_date = strtotime('1-July-' . $current_year);
                } else if ($current_month >= 10 && $current_month <= 12) {
                    $start_date = strtotime('1-October-' . $current_year);
                }

                $period['start'] = date("Y-m-d", $start_date);
                break;
            case "c6m":
                $current_year = date('Y');
                if (date('n') <= 6) {
                    $start_date = strtotime('1-January-' . $current_year);
                } else {
                    $start_date = strtotime('1-July-' . $current_year);
                }

                $period['start'] = date("Y-m-d", $start_date);
                break;
            case "c9m":
                $current_year = date('Y');
                $period['start'] = strtotime('1-January-' . $current_year);
                if (date('n') > 9) {
                    $period['end'] = strtotime('30-September-' . $current_year);
                }
                break;
            case "c12m":
                $period['start'] = strtotime('1-January-' . date('Y'));
                break;
            default:
                $period['start'] = $interval['field1'];
                $period['end'] = $interval['ext1'];
        }
        return $period;
    }

    /**
     * Create HTML for discount rule
     *
     * @param array $discount
     * @return string
     */
    public static function buildRuleHTMLCode($discount)
    {
        $currencies = shopFlexdiscountApp::get('system')['config']->getCurrencies();
        $html = '<div class="discount-row' . ($discount['deny'] ? " deny-row" : "") . '" data-id="' . $discount['id'] . '"' . ($discount['group_id'] ? " data-group-id='" . $discount['group_id'] . "'" : "") . '>
                            <div class="discount-name">
                                <input type="checkbox" value="' . $discount['id'] . '" class="f-checker">
                                <i class="icon16 sort" style="cursor: pointer;"></i>
                                <a href="#/discount/split/' . $discount['id'] . '" class="js-action s-split" title="' . _wp('Delete from group') . '"><i class="icon16 split"></i></a>
                                <a href="#/discount/copy/' . $discount['id'] . '" class="js-action" title="' . _wp('Copy discount') . '"><i class="icon16 ss orders-all"></i></a>
                                <a href="#/discount/status/' . $discount['id'] . '" class="js-action" title="' . _wp('Change status') . '"><i class="icon16-custom lightbulb' . (!$discount['status'] ? "-off" : "") . '"></i></a>
                                <a href="#/discount/' . $discount['id'] . '" title="' . _wp('Open discount') . '">';
        if (!empty($discount['enable_coupon'])) {
            $html .= "<i class=\"icon16-custom coupon\"></i> ";
        }
        if (!empty($discount['description'])) {
            $html .= waString::escapeAll($discount['description']) . ($discount['name'] ? ", " : "");
        }
        if (!empty($discount['name'])) {
            $html .= waString::escapeAll($discount['name']);
        }
        if (empty($discount['description']) && empty($discount['name'])) {
            $html .= _wp('No name discount');
        }
        $html .= '</a>
                            </div>';
        if (!$discount['deny']) {
            $html .= ' <div class="discount-coupons">
                          <span onclick="$.flexdiscount.discountCouponListAction(null, ' . $discount['id'] . ')">' . ($discount['coupons']['coupons'] + $discount['coupons']['generators']) . '</span>
                       </div>';
            $html .= ' <div class="discount-discount">';
            $html .= '<span class="editable discount-value' . (empty($discount['discount_percentage']) && empty($discount['discount']) ? " hidden" : "") . '">';
            if (empty($discount['discount_percentage']) && empty($discount['discount'])) {
                $html .= '&nbsp';
            }
            if (!empty($discount['discount_percentage'])) {
                $html .= '<span class="f-percentage-value">' . $discount['discount_percentage'] . ' %</span>';
            }
            if (!empty($discount['discount'])) {
                $html .= '<span class="f-fixed-value"> + ' . $discount['discount'] . '</span>';
                if (!empty($discount['discount_currency'])) {
                    $html .= '<span class="f-currency-value">' . $discount['discount_currency'] . '</span>';
                }
            }
            $html .= '<span class="edit-block">
                                    <input type="text" style="width: 30px;" maxlength="3" class="f-perc-val" value="' . (!empty($discount['discount_percentage']) ? $discount['discount_percentage'] : '') . '" /> %
                                    <span class="margin-block semi" style="display: block">
                                        <input type="text" style="width: 50px;" class="f-fixed-val" value="' . (!empty($discount['discount']) ? $discount['discount'] : '') . '" />
                                        <select class="f-cur-val" style="width: 70px;">
                                            <option ' . (!empty($discount['discount_currency']) ? 'selected' : '') . ' value="">' . _wp('Select currency') . '</option>';
            foreach ($currencies as $c) {
                $html .= '<option ' . (!empty($discount['discount_currency']) && $discount['discount_currency'] == $c['code'] ? "selected" : "") . ' value="' . $c['code'] . '">' . $c['sign'] . ' ' . $c['code'] . '</option>';
            }
            $html .= '</select>
                                    </span>
                                    <input type="submit" value="' . _wp("Save") . '" style="width: 100%" />
                                </span>
                            </span>';
            $html .= '
                            </div>
                            <div class="discount-affil">';
            $html .= '<span class="editable affiliate-value' . (empty($discount['affiliate_percentage']) && empty($discount['affiliate']) ? " hidden" : "") . '">';
            if (empty($discount['affiliate_percentage']) && empty($discount['affiliate'])) {
                $html .= '&nbsp';
            }
            if (!empty($discount['affiliate_percentage']) && $discount['affiliate_percentage'] !== '0.00') {
                $html .= '<span class="f-percentage-value">' . $discount['affiliate_percentage'] . ' %</span>';
            }
            if (!empty($discount['affiliate']) && $discount['affiliate'] !== '0.00') {
                $html .= '<span class="f-fixed-value"> + ' . $discount['affiliate'] . '</span>';
            }
            $html .= '<span class="edit-block">
                                    <input type="text" style="width: 30px;" maxlength="3" class="f-perc-val" value="' . (!empty($discount['affiliate_percentage']) ? $discount['affiliate_percentage'] : '') . '" /> %
                                    <span class="margin-block semi" style="display: block">
                                        <input type="text" style="width: 50px;" class="f-fixed-val" value="' . (!empty($discount['affiliate']) ? $discount['affiliate'] : '') . '" />
                                    </span>
                                    <input type="submit" value="' . _wp("Save") . '" style="width: 100%" />
                                </span>
                            </span>';
            $html .= '
                            </div>';
        }
        $html .= ' <div class="discount-sort"><input type="text" onchange="$.flexdiscount.discountFrontendSortAction($(this))" value="' . (isset($discount['frontend_sort']) ? $discount['frontend_sort'] : '') . '"/></div>';
        $html .= '<div class="discount-icon"><a href="#/discount/delete/' . $discount['id'] . '" class="js-action" title="' . _wp('Delete') . '"><i class="icon16 delete"></i></a></div>
                        </div>';
        return $html;
    }

    public static function prepareDiscountRuleData($block_name, $discount_rule, $currency, $params)
    {
        $data = array();
        $max_discount = $max_affiliate = array('id' => 0, 'value' => 0);
        $primary_currency = shopFlexdiscountApp::get('system')['primary_currency'];
        $product = $params['product'];
        $function = shopFlexdiscountApp::getFunction();

        $item_flexdiscount_fields = shopFlexdiscountApp::get('runtime.item_flexdiscount_fields', []);

        $product_discount = ifempty($item_flexdiscount_fields, $product['sku_id'], 'flexdiscount_item_discount', ifset($product, 'flexdiscount_item_discount', 0));
        $product_discount_currency = ifempty($item_flexdiscount_fields, $product['sku_id'], 'flexdiscount_discount_currency', ifset($product, 'flexdiscount_discount_currency', '%'));
        $product_affiliate = ifempty($item_flexdiscount_fields, $product['sku_id'], 'flexdiscount_item_affiliate', ifset($product, 'flexdiscount_item_affiliate', 0));
        $product_affiliate_currency = ifempty($item_flexdiscount_fields, $product['sku_id'], 'flexdiscount_affiliate_currency', ifset($product, 'flexdiscount_affiliate_currency', '%'));

        foreach ($discount_rule as $k => $dr) {
            if (!$dr || ($block_name == 'available' && empty($dr['rule']))) {
                continue;
            }

            $rule = $block_name == 'available' ? $dr['rule'] : (!empty($params['rules'][$k]) ? $params['rules'][$k] : array());
            $rule_params = $block_name == 'available' ? $dr['rule'] : (!empty($rule['full_info']) ? $rule['full_info'] : array());
            $rule_id = $block_name == 'available' ? $rule['id'] : $k;

            $data[$rule_id] = array(
                'name' => ifempty($rule, 'name', _wp('Discount #' . $rule_id)),
                'description' => ifempty($rule, 'description', ''),
                'sort' => ifset($rule, 'sort', 0),
                'discount' => $dr['discount'] ? $function->shop_currency($dr['discount'] / $dr['quantity'], $currency, null) : 0,
                'discount_html' => $dr['discount'] ? $function->shop_currency_html($dr['discount'] / $dr['quantity'], $currency, null) : 0,
                'clear_discount' => $dr['discount'] ? $dr['discount'] / $dr['quantity'] : 0,
                'affiliate' => $dr['quantity'] ? $dr['affiliate'] / $dr['quantity'] : 0,
                'quantity' => $dr['quantity'],
                'without_discount' => !empty($dr['without_discount']) ? 1 : 0,
                'params' => shopFlexdiscountApp::getHelper()->getRuleDiscountAffiliateParams($rule, [
                    'flexdiscount_item_discount' => $product_discount,
                    'flexdiscount_discount_currency' => $product_discount_currency,
                    'flexdiscount_item_affiliate' => $product_affiliate,
                    'flexdiscount_affiliate_currency' => $product_affiliate_currency
                ], $currency)
            );
            if ($block_name == 'available') {
                $product_discount_price = $product['price'] - ($dr['quantity'] ? $dr['discount'] / $dr['quantity'] : 0);
                $data[$rule_id]['price'] = shopFlexdiscountApp::getFunction()->shop_currency($product_discount_price, $currency, null);
                $data[$rule_id]['price_html'] = shopFlexdiscountApp::getFunction()->shop_currency_html($product_discount_price, $currency, null);
                $data[$rule_id]['clear_price'] = ($product_discount_price > 0) ? shopFlexdiscountApp::getFunction()->shop_currency($product_discount_price, $currency, null, 0) : 0;
                $data[$rule_id]['currency'] = $product['currency'];
                $data[$rule_id]['sku_id'] = $product['sku_id'];
            } else {
                $data[$rule_id]['params']['discount_currency'] = !empty($rule_params['discount_currency']) ? $rule_params['discount_currency'] : $primary_currency;
                $data[$rule_id]['params']['discounteachitem'] = !empty($rule_params['discounteachitem']) ? 1 : 0;
                $data[$rule_id]['params']['affiliateeachitem'] = !empty($rule_params['affiliateeachitem']) ? 1 : 0;
            }

            // Определяем какое правило возвращает максимальную скидку или максимальное количество бонусов
            $max_discount['id'] = $max_discount['value'] < $dr['discount'] ? $rule_id : $max_discount['id'];
            $max_discount['value'] = $max_discount['value'] < $dr['discount'] ? $dr['discount'] : $max_discount['value'];
            $max_affiliate['id'] = $max_affiliate['value'] < $dr['affiliate'] ? $rule_id : $max_affiliate['id'];
            $max_affiliate['value'] = $max_affiliate['value'] < $dr['discount'] ? $dr['discount'] : $max_affiliate['value'];
        }

        if ($max_discount['id'] && isset($data[$max_discount['id']])) {
            $data[$max_discount['id']]['max_discount'] = 1;
        }
        if ($max_affiliate['id'] && isset($data[$max_affiliate['id']])) {
            $data[$max_affiliate['id']]['max_affiliate'] = 1;
        }
        return $data;
    }

    /**
     * Detect time period and convert it to url string
     *
     * @param array $params
     * @return string
     */
    public static function convertPeriodToUrl($params)
    {
        $hash = "";
        if (!empty($params['period_type'])) {
            $period = self::getPeriod($params['period_type'], $params);
            if ($period['start']) {
                $hash .= "&paid_date>=" . $period['start'];
            }
            if ($period['end']) {
                $hash .= "&paid_date<=" . $period['end'];
            }
        }
        return $hash;
    }

    /**
     * Get shop stocks.
     *
     * @return array
     */
    public static function getStocks()
    {
        // Shop-Script >= 7
        if (method_exists('shopHelper', 'getStocks') && is_callable(array('shopHelper', 'getStocks'))) {
            return shopHelper::getStocks();
        } // Shop-Script < 7
        else {
            static $cache_all = null;
            if ($cache_all === null) {
                $cache_all = array();

                $stock_model = new shopStockModel();
                $cache_all = (array) $stock_model->getAll('id');

                uasort($cache_all, wa_lambda('$a, $b', 'return ((int) ($a["sort"] > $b["sort"])) - ((int) ($a["sort"] < $b["sort"]));'));
            }
            return $cache_all;
        }
    }

    public static function getStockCounts($sku_id)
    {
        if (!$sku_id) {
            return array();
        }

        $stock_model = new shopProductStocksModel();
        // Shop-Script >= 7
        if (method_exists($stock_model, 'getCounts') && is_callable(array($stock_model, 'getCounts'))) {
            return $stock_model->getCounts($sku_id);
        } // Shop-Script < 7
        else {
            $rows = $stock_model->select('sku_id, stock_id, count')
                ->where('sku_id IN (:skus)', array('skus' => (array) $sku_id))
                ->fetchAll();
            $result = array();
            foreach ($rows as $row) {
                $result[$row['sku_id']][$row['stock_id']] = $row['count'];
            }
            if (!is_array($sku_id)) {
                return ifset($result[$sku_id], array());
            } else {
                return $result;
            }
        }
    }

    /**
     * Get badges for product
     *
     * @param array $product
     * @param array $workflow_product
     * @param array $active_rules
     */
    public function getBadges(&$product, $workflow_product, $active_rules)
    {
        static $default_badges;
        if ($default_badges === null) {
            // Наклейки ВА
            $default_badges = shopProductModel::badges();
        }

        if (!empty($workflow_product['rules'])) {
            $badge = '';
            $overwrite_badge = false;
            foreach (array_keys($workflow_product['rules']) as $rule_id) {
                if (!empty($active_rules[$rule_id]['full_info']['use_badge']) && !empty($active_rules[$rule_id]['full_info']['badge'])) {
                    $badge .= '<div class="flexdiscount-badge">' . $active_rules[$rule_id]['full_info']['badge'] . '</div>';
                    if (!empty($active_rules[$rule_id]['full_info']['overwrite_default_badge'])) {
                        $overwrite_badge = true;
                    }
                }
            }
            if ($badge) {
                $product['badge'] = ifset($product, 'badge', '');
                // Проверяем наличие наклеек от ВА
                if (!empty($default_badges[$product['badge']])) {
                    $product['badge'] = (!$overwrite_badge ? $default_badges[$product['badge']]['code'] : '') . $badge;
                } else {
                    $product['badge'] .= $badge;
                }
                $product['flexdiscount-badge'] = $badge;
            }
        }
    }

    /**
     * Get url to settings page
     *
     * @return string
     */
    public function getPluginSettingsPageUrl()
    {
        $url = '?action=settings#/discounts/flexdiscount/';
        if (version_compare(shopFlexdiscountApp::get('system')['wa']->getVersion(), '8.5', '>=')) {
            $url = shopFlexdiscountApp::get('system')['config']->getBackendUrl(true) . 'shop/marketing/discounts/flexdiscount/';
        }
        return $url;
    }

    /**
     * Get CSS styles
     *
     * @return string
     * @throws waException
     */
    public function getCssStyles()
    {
        $css = '<style>';
        $settings = shopFlexdiscountApp::get('settings');
        $wa = shopFlexdiscountApp::get('system')['wa'];
        $file = $wa->getAppPath('plugins/flexdiscount/css/flexdiscountFrontend.css', 'shop');
        if (file_exists($file)) {
            $css .= 'i.icon16-flexdiscount.loading{background-image:url(' . $wa->getAppStaticUrl(null, true) . 'plugins/flexdiscount/img/loading16.gif)}i.flexdiscount-big-loading{background:url(' . $wa->getAppStaticUrl(null, true) . 'plugins/flexdiscount/img/loading.gif) no-repeat}';
            $css .= '.fl-is-loading > * { opacity: 0.3; }.fl-is-loading { position:relative }.fl-is-loading:after{ position:absolute; top:0;left:0;content:"";width:100%;height:100%; background:url(' . $wa->getAppStaticUrl(null, true) . 'plugins/flexdiscount/img/loader2.gif) center center no-repeat}';
            $css .= '.fl-loader-2:after{ position:absolute; top:0;left:0;content:"";width:100%;height:100%; background:url(' . $wa->getCdn($wa->getRootUrl()) . 'wa-content/img/loading16.gif) center center no-repeat}';
            $css .= file_get_contents($file);
        }
        if (!empty($settings['styles'])) {
            $css .= $settings['styles'];
        }
        $css .= '</style>';
        return $css;
    }

    /**
     * Add slash at the end of string
     *
     * @param $string
     * @return string
     */
    public function cleanUrl($string)
    {
        if (substr($string, -1) !== '/') {
            $string .= '/';
        }
        return $string;
    }

    public function prepareJsSettings($settings)
    {
        $js_settings = [
            'enable_frontend_cart_hook' => !empty($settings['enable_frontend_cart_hook']) ? 1 : 0
        ];
        // SS8 onestep checkout
        if (shopFlexdiscountApp::get('env')['is_onestep_checkout']) {
            // Вывод формы для ввода купонов
            if (!empty($settings['enable_frontend_cart_hook'])) {
                $js_settings['coupon_form'] = shopFlexdiscountPluginHelper::getCouponForm();
            }
        }
        return $js_settings;
    }

    /**
     * Get backend attention text about plugin
     *
     * @return string
     */
    public function getBackendAttention()
    {
        $attentions = array();
        $result = '';

        // Если плагин отключен
        if (!shopFlexdiscountPlugin::isEnabled()) {
            $attentions[] = sprintf(_wp('Plugin is disabled. <a href="%s">Enable it</a>'), $this->getPluginSettingsPageUrl());
        }

        if ($attentions) {
            $attention = implode('<br>', $attentions);
            $plugin_url = shopFlexdiscountApp::get('system')['wa']->getPlugin('flexdiscount')->getPluginStaticUrl();
            $result = <<<HTML
        <div class="attention-block">
            <svg viewBox="0 0 451.74 451.74" class="width50">
                <use xlink:href="{$plugin_url}img/svg/warning.svg#hey"></use>
            </svg>
            <div>{$attention}</div>
        </div>
HTML;
        }

        return $result;
    }

    /**
     * Prepare coupon for using in JS.
     * Uses in marketing
     *
     * @param array $coupon
     * @return mixed
     * @throws waException
     */
    public function prepareCouponForJS($coupon)
    {
        $coupon['status'] = shopFlexdiscountHelper::getCouponStatus($coupon);
        if (!empty($coupon['end']) && $coupon['status'] !== -2) {
            $coupon['expire_datetime_string'] = waDateTime::format('date', $coupon['end']);
        }
        if ($coupon['status'] < 0) {
            $coupon['status_string'] = '';
            switch ($coupon['status']) {
                case -1:
                    $coupon['status_string'] = "<i class='icon16 clock' title='" . _wp('Coupon start at') . ' ' . wa_date('humandate', $coupon['start']) . "'></i>";
                    break;
                case -2:
                    $coupon['status_string'] = "<i class='icon16 exclamation' title='" . _wp('Coupon time is expired') . "'></i>";
                    break;
                case -3:
                    $coupon['status_string'] = "<i class='icon16-custom exclamation' title='" . _wp('Coupon reached the limit') . "'></i>";
                    break;
            }
        }
        if (!empty($coupon['fl_id'])) {
            $coupon['fl_id'] = $coupon['fl_id'][0];
        }
        return $coupon;
    }

    public function getTemplatePath($path)
    {
        return shopFlexdiscountApp::get('system')['wa']->getAppPath('plugins/flexdiscount/templates/actions/' . $path);
    }

    public function getShippingParams($selected_variant)
    {
        $params = array('id' => 0, 'rate_id' => 0);
        if (!empty($selected_variant['selected_variant_id'])) {
            // У Яндекс.Доставки длинные rate_id с точками
            $parts = explode('.', $selected_variant['selected_variant_id'], 2);
            $params['id'] = (int) $parts[0];
            $params['rate_id'] = $parts[1];
        }
        return $params;
    }

    /**
     * If we have condition 'total_with_discount', then try to calculate discounts before any hooks and methods
     *
     * @return int
     */
    public function checkRulesForTotalPriceWithDiscount()
    {
        static $result;
        if ($result === null) {
            $discount_groups = shopFlexdiscountApp::get('core')['discounts'];
            foreach ($discount_groups as $group_id => $group) {
                $rules = $group_id === 0 ? $group : $group['items'];
                foreach ($rules as $rule) {
                    if (!empty($rule['conditions']) && strpos($rule['conditions'], 'total_with_discount') !== false) {
                        $result = 1;
                        waRequest::setParam('igaponov_force_calculate', 1);
                        shopFlexdiscountApp::getOrder()->getOrderCalculateDiscount();
                        break 2;
                    }
                }
            }
        }
        return $result;
    }

    public function getPluginShippingParams($plugin, $plugin_info, $items, $total_price)
    {
        static $shipping_params;
        $params = ['shipping_params' => null];
        if ($shipping_params === null) {
            $shipping_params = method_exists('shopShipping', 'getItemsTotal') ? shopShipping::getItemsTotal($items) : [];
        }
        if (method_exists('shopShipping', 'workupShippingParams')) {
            $params = shopShipping::workupShippingParams($shipping_params, $plugin, $plugin_info);
        }
        $params['total_price'] = $total_price;

        # extendShippingParams
        if ($extend_shipping_params = waRequest::request('shipping_' . $plugin_info['id'])) {
            $params['shipping_params'] = $extend_shipping_params;
        } else {
            $order_params = shopFlexdiscountApp::getOrder()->getCurrentOrderParams();
            $shipping = ifset($order_params, 'shipping', []);
            if (ifset($shipping['id']) == $plugin_info['id']) {
                $session_params = ifset($order_params, 'params', []);
                $params['shipping_params'] = ifset($session_params['shipping']);
            }
        }

        return $params;
    }

    /**
     * If conditions have payment and targets have delivery, then make second reload after each changing of payment on onestep checkout
     *
     * @return false
     */
    public function checkOnestepCartForceReloadForShipping()
    {
        $reload = false;
        if (shopFlexdiscountApp::get('env')['is_onestep_checkout']) {
            if ($discounts = shopFlexdiscountApp::get('core')['discounts']) {
                foreach ($discounts as $group_id => $group) {
                    $rules = $group_id === 0 ? $group : $group['items'];
                    foreach ($rules as $rule) {
                        if (strpos($rule['conditions'], '"type":"payment"') !== false && strpos($rule['target'], '"type":"shipping"') !== false) {
                            $reload = true;
                            break 2;
                        }
                    }
                }
            }
        }
        return $reload;
    }

    /**
     * @param string $name
     * @return mixed|null
     * @deprecated
     */
    public static function getSettings($name = '')
    {
        return shopFlexdiscountApp::get('settings' . ($name ? '.' . $name : ''));
    }

    /**
     * @param string $block
     * @return string
     * @deprecated
     */
    public static function getBlock($block)
    {
        if ($block == 'flexdiscount.form') {
            return shopFlexdiscountPluginHelper::getCouponForm();
        } else {
            return '';
        }
    }

}

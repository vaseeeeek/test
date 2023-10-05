<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopAutobadgeHelper
{

    /**
     * Workup product
     *
     * @param array $products
     * @param array $prod - current product
     * @throws waException
     */
    public static function workupProducts(&$products, $prod = array())
    {
        static $skus = null;
        $primary_curr = wa('shop')->getConfig()->getCurrency(true);
        $currencyr = shopAutobadgeData::getOrderInfo('currency');

        // Массив товаров, для которых нужно получить доп информацию о ценах
        $find_skus = array();
        // Массив товаров, для которых необходимо получить общие данные из таблицы shopProduct
        $find_product_info = array();
        // Массив товар, в котором будут храниться ключи товаров. 
        // В бекенде любимый Вебасист не передает для Услуг значение 'parent_id', в котором хранится ключ родительского товара текущей услуги.
        $product_k_item = array();
        foreach ($products as $k => &$p) {
            // Обрабатываем услуги
            if ($p['type'] == 'service') {
                $parent_id = isset($p['parent_id']) ? $p['parent_id'] : (isset($product_k_item[$p['sku_id']]) ? $product_k_item[$p['sku_id']] : 0);
                if (isset($products[$parent_id])) {
                    if (!isset($products[$parent_id]['product_services'])) {
                        $products[$parent_id]['product_services'] = array();
                    }
                    $products[$parent_id]['product_services'][$p['service_id']][$p['service_variant_id']] = $p;
                    $products[$parent_id]['product_services'][$p['service_id']][$p['service_variant_id']]['price'] = shop_currency($p['price'], $p['currency'], $primary_curr, false);
                    $products[$parent_id]['product_services'][$p['service_id']][$p['service_variant_id']]['quantity'] = $products[$parent_id]['quantity'];
                }
                unset($products[$k]);
                continue;
            }

            $product_k_item[$p['sku_id']] = $k;

            // Цена товара в текущей валюте
            if (!isset($p['price_workuped'])) {
                $p['price'] = (float) shop_currency($p['price'], $p['currency'], $currencyr, false);
                // Цена товара в основной валюте
                $p['primary_price'] = (float) shop_currency($p['price'], $p['currency'], $primary_curr, false);
                $p['price_workuped'] = 1;
            }

            if (!isset($p['purchase_price_workuped'])) {
                // Закупочная цена товара в текущей валюте
                if (isset($p['purchase_price']) || isset($skus[$p['sku_id']])) {
                    $purchase_price = isset($p['purchase_price']) ? $p['purchase_price'] : (float) $skus[$p['sku_id']]['purchase_price'];
                    $p['purchase_price'] = (float) shop_currency($purchase_price, isset($p['unconverted_currency']) && isset($skus[$p['sku_id']]) ? $p['unconverted_currency'] : $p['currency'], $currencyr, false);
                    $p['primary_purchase_price'] = (float) shop_currency($p['purchase_price'], $currencyr, $primary_curr, false);
                    $p['purchase_price_workuped'] = 1;
                } elseif ($skus === null) {
                    $find_skus[$p['sku_id']] = $p['sku_id'];
                }
            }
            if (!isset($p['compare_price_workuped'])) {
                // Зачеркнутая цена товара в текущей валюте
                if (isset($p['compare_price']) || isset($skus[$p['sku_id']])) {
                    $compare_price = isset($p['compare_price']) ? $p['compare_price'] : (float) $skus[$p['sku_id']]['compare_price'];
                    $p['compare_price'] = (float) shop_currency($compare_price, isset($p['unconverted_currency']) && isset($skus[$p['sku_id']]) ? $p['unconverted_currency'] : $p['currency'], $currencyr, false);
                    $p['primary_compare_price'] = (float) shop_currency($p['compare_price'], $currencyr, $primary_curr, false);
                    $p['compare_price_workuped'] = 1;
                } elseif ($skus === null) {
                    $find_skus[$p['sku_id']] = $p['sku_id'];
                }
            }
            if (!isset($p['properties_workuped'])) {
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
                // Для текущего товара сохраняем информацию о переданном типе и странице.
                // Необходимо для условий "Тип наклейки" и "Страница товара", чтобы можно было выводить разные наклейки для одного и того же товара
                if (!empty($prod) && $p['sku_id'] == $prod['sku_id']) {
                    $p['autobadge-type'] = $prod['autobadge-type'];
                    $p['autobadge-page'] = $prod['autobadge-page'];
                }
            }
            // Устанавливаем остатки для артикула
            if (isset($skus[$p['sku_id']])) {
                $p['count'] = isset($skus[$p['sku_id']]['count']) ? $skus[$p['sku_id']]['count'] : null;
                $p['skus_workuped'] = 1;
            }
//            if (!isset($p['skus_workuped'])) {
//                $find_skus[$p['sku_id']] = $p['sku_id'];
//            }
        }
        if ($find_product_info) {
            $collection = new shopAutobadgeProductsCollection('id/' . implode(",", $find_product_info));
            $collection_products = $collection->getProducts('*', 0, $collection->count());
            foreach ($find_product_info as $k => $p_id) {
                $products[$k]['product'] = isset($collection_products[$p_id]) ? $collection_products[$p_id] : $products[$k];
            }
        }
        if ($find_skus) {
            $skus = (new shopProductSkusModel())->getByField('id', array_keys($find_skus), 'id');
            if ($skus) {
                self::workupProducts($products);
            }
        } else {
            $skus = null;
        }
    }

    /**
     * Fix product prices
     *
     * @param array $product_data
     * @return array
     * @throws waException
     */
    public static function fixPrices($product_data)
    {
        $products = [];
        $sku_ids = [];
        $product_features_model = new shopProductFeaturesModel();
        foreach ($product_data as $key => $p) {
            $p_id = !empty($p['product_id']) ? $p['product_id'] : $p['id'];
            // Информация о товаре
            $product = new shopProduct($p_id, true);
            if (!$product) {
                continue;
            }
            $product = $product->getData();

            if (isset($p['autobadgePage'])) {
                $product['autobadge-page'] = $p['autobadgePage'];
            }
            if (isset($p['autobadgeType'])) {
                $product['autobadge-type'] = $p['autobadgeType'];
            }

            // Данные форм
            if (isset($p['params']) && is_string($p['params'])) {
                parse_str($p['params'], $p['params']);
                $product_data[$key]['params'] = $p['params'];
            }
            // Проверяем, передан ли артикул товара. Если нет, то пытаемся определить его через данные форм
            if (empty($p['sku_id'])) {
                if (isset($p['params']['sku_id'])) {
                    $product['sku_id'] = $p['params']['sku_id'];
                } else {
                    if (isset($p['params']['features'])) {
                        $product['sku_id'] = $product_features_model->getSkuByFeatures($p_id, $p['params']['features']);
                    }
                }
            } else {
                $product['sku_id'] = $p['sku_id'];
            }

            // Изменяем количество товара
            $product['quantity'] = (isset($p['quantity']) && (int) $p['quantity'] > 0) ? (int) $p['quantity'] : 1;

            $products[$key] = $product;

            $sku_ids[$product['sku_id']] = $product['sku_id'];
        }

        $products = shopAutobadgeWorkflow::prepareProducts($products, $sku_ids);

        if (waRequest::isXMLHttpRequest()) {
            foreach ($products as $key => $product) {
                $p = $product_data[$key];
                // Проверяем наличие услуг
                if (!empty($p['params']['services'])) {
                    $products[$key]['services'] = self::getProductServices($p['params'], $product);
                }
            }
        }

        return $products;
    }

    public static function prepareProduct(&$product)
    {
        $price = $product['price'];
        $compare_price = $product['compare_price'];

        $product['margin'] = $price - $product['purchase_price'];
        $product['margin_comp'] = $compare_price - $price;

        // Скидка в процентах
        $discount = 0;
        if ($compare_price > 0) {
            $discount = (100 * (1 - $price / $compare_price));
            if ($discount > 0) {
                $discount = round($discount, ($discount > 99 || $discount < 1 ? 2 : 0));
            }
        }
        $product['discount_percentage'] = $discount;

        $product['product'] = array_merge($product['product'], $product);
    }

    /**
     * Get domain routes
     *
     * @param string $domain
     * @return array
     */
    public static function getRoutes($domain)
    {
        $storefronts = array();
        $routing = wa('shop')->getRouting();

        $routes = $routing->getRoutes($domain);

        foreach ($routes as $route) {
            $storefronts[] = $domain . '/' . $route['url'];
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
        $domain = wa('shop')->getConfig()->getDomain();
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
            $html .= self::secureString($c['name']);
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
            $html .= "<option value='" . $o['id'] . "'" . ($selected == $o['id'] ? " selected" : "") . ">" . self::secureString($o['name']) . "</option>";
        }
        return $html;
    }

    public static function getServicesHtml($data, $selected = '')
    {
        $html = "";
        foreach ($data['services'] as $s) {
            $html .= "<option" . ($s['selectable'] ? ' class="selectable"' : "") . " value='" . $s['id'] . "'" . ($selected == $s['id'] ? " selected" : "") . ">" . self::secureString($s['name']) . "</option>";
        }
        return $html;
    }

    public static function getServicesVariantsHtml($data, $selected = '')
    {
        $html = "";
        foreach ($data['variants'] as $v) {
            if (!empty($data['services'][$v['service_id']]) && $data['services'][$v['service_id']]['selectable']) {
                $html .= "<option class='feature-" . $v['service_id'] . "' value='" . $v['id'] . "'" . ($selected == $v['id'] ? " selected" : "") . ">" . self::secureString($v['name']) . "</option>";
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
            $html .= "<option" . ($base_unit && !$f['selectable'] ? " data-base-unit = '" . $base_unit['title'] . "'" : "") . ($f['selectable'] ? ' class="selectable"' : "") . " value='" . $f['id'] . "'" . ($selected == $f['id'] ? " selected" : "") . ">" . self::secureString($f['name']) . ($f['code'] ? " (" . $f['code'] . ")" : "") . "</option>";
        }
        return $html;
    }

    public static function getFeaturesValuesHtml($values, $feature_id, $selected = '')
    {
        $html = "";
        if (!empty($values)) {
            foreach ($values as $val_id => $val) {
                $html .= "<option class='feature-" . $feature_id . "' value='" . $feature_id . '-' . $val_id . "'" . ($selected == ($feature_id . '-' . $val_id) ? " selected" : "") . ">" . self::secureString($val) . "</option>";
            }
        }
        return $html;
    }

    public static function getStorefrontRoutesHtml($routes, $selected = '')
    {
        $html = "";
        foreach ($routes as $domain => $r) {
            foreach ($r as $route) {
                $html .= "<option class='domain-" . self::secureString($domain) . "' value='" . self::secureString($route) . "'" . ($selected == $route ? " selected" : "") . ">" . self::secureString($route) . "</option>";
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

    public static function getFilters()
    {
        static $filters = null;
        if ($filters === null) {
            $filters = (new shopAutobadgePluginModel())->getFilters();
            foreach ($filters as &$f) {
                $f['target'] = str_replace('box-shadow', 'box_shadow', $f['target']);
            }
        }
        return $filters;
    }

    public static function secureString($str, $mode = ENT_QUOTES, $charset = 'UTF-8')
    {
        return htmlentities($str, $mode, $charset);
    }

    /**
     * Get plugin settings
     * @return array
     */
    public static function getSettings()
    {
        static $settings = array();
        if (!$settings) {
            // Настройки
            $settings = (new waAppSettingsModel())->get('shop.autobadge');
            if (!empty($settings['ignore_plugins'])) {
                $settings['ignore_plugins'] = @unserialize($settings['ignore_plugins']);
            }
            if (!empty($settings['ignore_methods'])) {
                $settings['ignore_methods'] = array_map('trim', explode(',', $settings['ignore_methods']));
            }
        }
        return $settings;
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

        $interval['field1'] = isset($interval['field1']) ? (int) $interval['field1'] : '';
        $interval['ext1'] = isset($interval['ext1']) ? (int) $interval['ext1'] : '';

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
     * Get badges for product
     *
     * @param array $product
     * @param array $product_data
     * @param array $filters
     * @return array
     */
    public static function getBadgesData($product, $product_data, $filters)
    {
        $settings = shopAutobadgeHelper::getSettings();
        // Если передан объект, значит происходит обработка продукта на странице товара
        if ($product instanceof shopProduct) {
            $p = $product_data ? $product_data : $product->getData();
            $page = 'product';
        } else {
            $p = $product_data ? $product_data : $product;
            $page = 'category';
        }
        $p['autobadge-page'] = empty($p['autobadge-page']) ? $page : $p['autobadge-page'];
        if (empty($p['sku_id'])) {
            return $product;
        }

        // Сохраняем тип наклейки для последующей обработки.
        // Чтобы сделать разные наклейки для одного товара, необходимо отключить frontend_products и перед вызовом наклейки
        // присвоить товару значение autobadge-type. Далее необходимо создать условие "Тип наклейки" и указать значение autobadge-type.
        $p['autobadge-type'] = !empty($p['autobadge-type']) ? $p['autobadge-type'] : 'default';

        $p['type'] = 'product';

        $delay_loading_ajax = !empty($settings['delay_loading_ajax']) && !waRequest::isXMLHttpRequest();
        // Если не используется отложенная загрузка, выполняем расчеты
        if (!$delay_loading_ajax) {
            // Добавляем товар к заказу
            $order_params = shopAutobadgeWorkflow::addToOrder($p);
            // Получаем наклейки товара
            $badges = shopAutobadgeCore::getBadges($order_params, $filters, $p);
        }

        // Если не используется отложенная загрузка, выполняем расчеты
        if (!$delay_loading_ajax) {
            $product['badge'] = '';
            if (isset($badges['default'])) {
                $product['badge'] .= str_replace("badge ", "badge autobadge-default product-id-" . $p['id'] . " ", shopHelper::getBadgeHtml($badges['default']));
                unset($badges['default']);
            }
            $product['autobadge'] = $badges ? implode('', $badges) : '<div class="autobadge-holder autobadge-pl product-id-' . $p['id'] . '" data-product-id="' . $p['id'] . '" data-page="' . $p['autobadge-page'] . '" data-type="' . $p['autobadge-type'] . '"></div>';
        } else {
            $product['badge'] = '<div class="autobadge-default product-id-' . $p['id'] . '" data-product-id="' . $p['id'] . '" data-page="' . $p['autobadge-page'] . '" data-type="' . $p['autobadge-type'] . '"></div>';
            $product['autobadge'] = '<div class="autobadge-holder autobadge-pl product-id-' . $p['id'] . '" data-product-id="' . $p['id'] . '" data-page="' . $p['autobadge-page'] . '" data-type="' . $p['autobadge-type'] . '"></div>';
        }

        // Наклейки Гибких скидок и Промоакций
        if (!waRequest::isXMLHttpRequest()) {
            if (!empty($product['flexdiscount-badge'])) {
                $product['badge'] .= $product['flexdiscount-badge'];
            }
            if (!empty($product['promos_badge'])) {
                $product['badge'] .= $product['promos_badge'];
            }
        }

        return $product;
    }

    public static function getView()
    {
        static $view = null;
        if ($view === null) {
            $view = wa('shop')->getView();

            function counter($params, $smarty)
            {
                $counter = '';
                if (!empty($params["date"])) {
                    $date_value = $params["date"];
                    if (!empty($params["time"])) {
                        $parts = explode(':', $params['time']);
                        switch (count($parts)) {
                            case '3':
                                $date_value .= " " . $params["time"];
                                break;
                            case '2':
                                $date_value .= " " . str_pad($parts[0], 2, 0, STR_PAD_LEFT) . ':' . str_pad($parts[1], 2, 0, STR_PAD_LEFT) . ':00';
                                break;
                            case '1':
                                $date_value .= " " . str_pad($parts[0], 2, 0, STR_PAD_LEFT) . ':00:00';
                                break;
                        }
                    }
                    $counter = '<i class="autobadge-countdown icon16-autobadge loading-icon"
                                  data-start="' . date('Y/m/d H:i:s') . '"
                                  data-end="' . date('Y/m/d H:i:s', strtotime($date_value)) . '">
                            </i>';
                }
                return $counter;
            }

            $view->smarty->registerPlugin("function", "autobadge_counter", "counter");
        }
        return $view;
    }

    /**
     * @param array $ids
     * @return array|mixed|null
     */
    public static function getStockCounts($ids)
    {
        if (!$ids) {
            return array();
        }

        $stock_model = new shopProductStocksModel();
        // Shop-Script >= 7
        if (method_exists($stock_model, 'getCounts') && is_callable(array($stock_model, 'getCounts'))) {
            return $stock_model->getCounts($ids);
        } // Shop-Script < 7
        else {
            $rows = $stock_model->select('sku_id, stock_id, count')
                ->where('sku_id IN (:skus)', array('skus' => (array) $ids))
                ->fetchAll();
            $result = array();
            foreach ($rows as $row) {
                $result[$row['sku_id']][$row['stock_id']] = $row['count'];
            }

            return $result;
        }
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

    /**
     * Get plugin instance
     *
     * @param null|waPlugin $plugin_instance - Set this instance to plugin
     * @return null|waPlugin
     */
    public static function getPlugin($plugin_instance = null)
    {
        static $plugin = null;
        if ($plugin_instance !== null) {
            $plugin = $plugin_instance;
        }
        if ($plugin === null) {
            $plugin = wa('shop')->getPlugin('autobadge');
        }
        return $plugin;
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

    public function destruct()
    {
        $view = self::getView();
        $view->clearAssign('autobadge_product');
        $view->smarty->unregisterPlugin("function", "counter");
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
        $name = 'autobadge';
        $js_locale_path = wa('shop')->getAppPath("plugins/{$name}/locale/" . wa('shop')->getLocale() . "/LC_MESSAGES/shop_{$name}_js_" . ($backend ? 'backend' : 'frontend') . ".json", 'shop');
        $js_locale_strings = [];
        if (file_exists($js_locale_path)) {
            $js_locale_strings = file_get_contents($js_locale_path);
        }
        return $js_locale_strings;
    }

    public static function removeLastChar($string, $char)
    {
        if ($string && substr($string, -1) == $char) {
            $string = substr($string, 0, strlen($string) - 1);
        }
        return $string;
    }

    /**
     * Get product services by POST data
     *
     * @param array $params
     * @param array $product
     * @return array
     * @throws waException
     */
    private static function getProductServices($params, $product)
    {
        // Текущая валюта
        $current_cur = wa('shop')->getConfig()->getCurrency(false);
        // Основная валюта
        $primary_cur = wa('shop')->getConfig()->getCurrency(true);
        $services = $params['services'];
        $variants = !empty($params['service_variant']) ? $params['service_variant'] : array();
        $temp = array();
        $service_ids = array();
        foreach ($services as $service_id) {
            $temp[$service_id] = isset($variants[$service_id]) ? $variants[$service_id] : 0;
            $service_ids[] = $service_id;
        }
        $temp_services = (new shopServiceModel())->getById($service_ids);
        $service_stubs = array();
        foreach ($temp_services as $row) {
            if (!$temp[$row['id']]) {
                $temp[$row['id']] = $row['variant_id'];
            }
            $service_stubs[$row['id']] = array(
                'id' => $row['id'],
                'currency' => $row['currency'],
            );
        }
        $services = $temp;

        $variant_ids = array_values($services);

        $rounding_enabled = shopRounding::isEnabled();
        $variants = (new shopServiceVariantsModel())->getWithPrice($variant_ids);
        $rounding_enabled && shopRounding::roundServiceVariants($variants, $service_stubs);

        $product_services_model = new shopProductServicesModel();
        // Fetch service prices for specific products and skus
        $rows = $product_services_model->getByField(array('product_id' => $product['id'], 'service_id' => $service_ids, 'service_variant_id' => $variant_ids), true);
        shopRounding::roundServiceVariants($rows, $service_stubs);
        $skus_services = array();
        foreach ($rows as $row) {
            if (!$row['sku_id']) {
                if (!$row['status']) {
                    continue;
                } elseif ($row['price'] !== null && isset($variants[$row['service_variant_id']])) {
                    $variants[$row['service_variant_id']]['price'] = $row['price'];
                }
            } else {
                if ($row['status'] && $row['price'] !== null && isset($variants[$row['service_variant_id']]) && $product['sku_id'] == $row['sku_id']) {
                    $skus_services[$row['service_variant_id']] = $row['price'];
                }
            }
        }

        foreach ($variants as &$v) {
            $variant_price = isset($skus_services[$v['id']]) ? $skus_services[$v['id']] : $v['price'];
            if ($v['currency'] == '%') {
                $v['price'] = shop_currency($variant_price * $product['price'] / 100, $primary_cur, $current_cur, false);
            } else {
                $v['price'] = shop_currency($variant_price, $v['currency'], $current_cur, false);
            }
            $v['currency'] = $current_cur;
        }

        return $variants;
    }

}

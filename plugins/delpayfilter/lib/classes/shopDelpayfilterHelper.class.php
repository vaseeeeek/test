<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopDelpayfilterHelper
{

    /**
     * Workup product
     *
     * @param array $products
     */
    public static function workupProducts(&$products)
    {
        static $skus = null;
        $primary_curr = wa('shop')->getConfig()->getCurrency(true);
        $current_curr = wa('shop')->getConfig()->getCurrency(false);
        // Массив товаров, для которых нужно получить доп информацию о ценах
        $find_skus = array();
        foreach ($products as $k => &$p) {
            // Пропускаем услуги
            if ($p['type'] == 'service') {
                shopDelpayfilterData::addToServices($p['service_id'], $p['service_variant_id']);
                unset($products[$k]);
                continue;
            }
            // Цена товара в основной валюте
            if (!isset($p['primary_price'])) {
                $p['primary_price'] = (float) shop_currency($p['price'], $p['currency'], $primary_curr, false);
            }

            // Цена товара в текущей валюте
            if (!isset($p['price_workuped'])) {
                $p['price'] = (float) shop_currency($p['price'], $p['currency'], $current_curr, false);
                $p['price'] = shopRounding::roundCurrency($p['price'], $current_curr);
                $p['price_workuped'] = 1;
            }

            if (!isset($p['purchase_price_workuped'])) {
                // Закупочная цена товара в текущей валюте
                if (isset($p['purchase_price']) || isset($skus[$p['sku_id']])) {
                    $purchase_price = isset($p['purchase_price']) ? $p['purchase_price'] : (float) $skus[$p['sku_id']]['purchase_price'];
                    $p['purchase_price'] = (float) shop_currency($purchase_price, $p['currency'], $current_curr, false);
                    $p['purchase_price'] = shopRounding::roundCurrency($purchase_price, $current_curr);
                    $p['purchase_price_workuped'] = 1;
                }
            }
            if (!isset($p['compare_price_workuped'])) {
                // Зачеркнутая цена товара в текущей валюте
                if (isset($p['compare_price']) || isset($skus[$p['sku_id']])) {
                    $compare_price = isset($p['compare_price']) ? $p['compare_price'] : (float) $skus[$p['sku_id']]['compare_price'];
                    $p['compare_price'] = (float) shop_currency($compare_price, $p['currency'], $current_curr, false);
                    $p['compare_price'] = shopRounding::roundCurrency($compare_price, $current_curr);
                    $p['compare_price_workuped'] = 1;
                }
            }
            if ($skus !== null) {
                // Общее кол-во остатков для артикула
                $p['count'] = isset($skus[$p['sku_id']]['count']) ? $skus[$p['sku_id']]['count'] : 2147483647;
            } else {
                $find_skus[$p['sku_id']] = $p['sku_id'];
            }
        }
        if ($find_skus) {
            $sku_model = new shopProductSkusModel();
            $skus = $sku_model->getByField('id', array_keys($find_skus), 'id');
            self::workupProducts($products);
        }
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
        $routing = wa()->getRouting();

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
        $domain = wa()->getConfig()->getDomain();
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
            $html .= "<option value='" . $o['id'] . "'" . ($selected == $o['id'] ? " selected" : "") . (!empty($o['class']) ? ' class="' . $o['class'] . '"' : '') . ">" . self::secureString($o['name']) . "</option>";
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
            $html .= "<option" . ($base_unit ? " data-base-unit = '" . $base_unit['title'] . "'" : "") . ' class="' . ($f['selectable'] ? 'selectable' : "") . ($base_unit ? ' dimension' : "") . '"' . " value='" . $f['id'] . "'" . ($selected == $f['id'] ? " selected" : "") . ">" . self::secureString($f['name']) . ($f['code'] ? " (" . $f['code'] . ")" : "") . "</option>";
        }
        return $html;
    }

    public static function getFeaturesValuesHtml($features, $selected = '')
    {
        $html = "";
        foreach ($features as $f) {
            if ($f['selectable'] && !empty($f['values'])) {
                foreach ($f['values'] as $val_id => $val) {
                    $html .= "<option class='feature-" . $f['id'] . "' value='" . $f['id'] . '-' . $val_id . "'" . ($selected == ($f['id'] . '-' . $val_id) ? " selected" : "") . ">" . self::secureString($val) . "</option>";
                }
            }
        }
        return $html;
    }

    public static function getDynamicValuesHtml($values, $dynamic_id, $selected = '')
    {
        $html = "";
        if (!empty($values)) {
            foreach ($values as $val_id => $val) {
                $html .= "<option class='dynamic-" . $dynamic_id . "' value='" . $dynamic_id . '-' . $val_id . "'" . ($selected == ($dynamic_id . '-' . $val_id) ? " selected" : "") . ">" . self::secureString($val) . "</option>";
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

    public static function secureString($str, $mode = ENT_QUOTES, $charset = 'UTF-8')
    {
        return htmlentities($str, $mode, $charset);
    }

    public static function getFilters()
    {
        static $filters = null;
        if ($filters === null) {
            $model = new shopDelpayfilterPluginModel();
            $filters = $model->getFilters();
        }
        return $filters;
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

    public function updateContact($fields)
    {
        $contact = $this->getContact();
        foreach ($fields as $field_id => $field) {
            $contact->set($field_id, isset($field['value']) ? $field['value'] : $field);
        }
        $cache = new waRuntimeCache('igaponov_contact');
        $cache->set($contact);
    }

    public function getContact()
    {
        $cache = new waRuntimeCache('igaponov_contact');
        if ($cache->isCached()) {
            $contact = $cache->get();
        } else {
            $checkout_params = wa()->getStorage()->get('shop/checkout', array());
            $contact = !empty($checkout_params['contact']) ? $checkout_params['contact'] : wa()->getUser();
        }
        return $contact;
    }
}

<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

namespace Igaponov\flexdiscount;

class Helper
{

    /**
     * Check if onestep checkout is using or not
     *
     * @return bool
     */
    public function isOnestepCheckout()
    {
        $app = new \shopFlexdiscountApp();
        if (version_compare($app::get('system')['wa']->getVersion(), '8', '>=')
            && '2' == ifset(ref($app::get('system')['wa']->getRouting()->getRoute()), 'checkout_version', null)
        ) {
            return true;
        }
        return false;
    }

    /**
     * Is import/export enabled
     *
     * @return bool
     */
    public function isImportExport()
    {
        $importexport_plugins = $this->getImportExportPlugins();
        return $this->issetPluginsInCalls($importexport_plugins);
    }

    /**
     * Get list of import/export plugins
     *
     * @return array
     */
    public function getImportExportPlugins()
    {
        static $importexport_plugins = null;
        // Получаем плагины экспорта/импорта. Запрещаем работу с корзиной для таких плагинов
        if ($importexport_plugins === null) {
            $importexport_plugins = array_keys(\waUtils::getFieldValues(\shopFlexdiscountApp::get('system')['config']->getPlugins(), 'importexport', true));
        }
        return $importexport_plugins;
    }

    /**
     * Validate plugins. If found plugins, which should be ignored, then failed the validate
     *
     * @param bool $ignore_importexport
     * @return bool
     */
    public function validatePluginCalls($ignore_importexport = false)
    {
        $settings = \shopFlexdiscountApp::get('settings');
        $invalid_plugins = ifset($settings, 'ignore_plugins', []);

        $importexport_plugins = $this->getImportExportPlugins();
        if ($ignore_importexport) {
            $invalid_plugins = array_merge($importexport_plugins, $invalid_plugins);
        }

        return !$this->issetPluginsInCalls($invalid_plugins);
    }

    /**
     * Check general discount combiner. Compare discounts, if combiner set to max.
     * Returns true|array, if plugin has maximum discount or combiner is set to sum.
     *
     * @param array $product_discount_info
     * @param array $workflow
     * @return bool|array
     */
    public function checkGeneralDiscountCombiner($product_discount_info = [], &$workflow = [])
    {
        static $result = [];
        static $plugins;

        $app = new \shopFlexdiscountApp();

        if (!$app::get('env')['is_frontend'] && !$app::get('env')['is_importexport']) {
            return true;
        }

        if ($plugins === null) {
            $plugins = $app::get('system')['config']->getPlugins();
        }

        $id = ($product_discount_info && !empty($product_discount_info['product']['id'])) ? $product_discount_info['product']['id'] : 0;

        if (!isset($result[$id])) {
            $plugin_discount = 0;
            if ($product_discount_info) {
                $plugin_discount = $product_discount_info['discount'];
            }

            $result[$id] = true;
            if ($this->getDiscountCombineType() == 'max') {
                $wa = $app::get('system')['wa'];
                $discount_plugins = [];
                if (\shopDiscounts::isEnabled('promos')) {
                    $discount_plugins[] = 'promos';
                }
                if (!$id && isset($plugins['productsets']) && !$app::get('env')['is_importexport']) {
                    $discount_plugins[] = 'productsets';
                }

                if (!$discount_plugins) {
                    return true;
                }

                if ($app::get('env')['is_frontend']) {
                    \waRequest::setParam('igaponov_skip_frontend_products', 1);
                    $shop_cart = new \shopCart();
                    // Инициируем вызов order_calculate_discount, чтобы кешишровать результаты во всех плагинах
                    $order = [
                        'currency' => $app::get('system')['current_currency'],
                        'total' => $shop_cart->total(false),
                        'items' => $shop_cart->items(false)
                    ];
                    \waRequest::setParam('igaponov_skip_frontend_products', 0);

                    $contact = $wa->getUser();
                } else {
                    $order = $app::getOrder()->getOrder(true);
                    $contact = $order['contact'];
                }

                $event_params = array(
                    'order' => &$order,
                    'contact' => $contact,
                    'apply' => false,
                );

                foreach ($discount_plugins as $plugin_id) {
                    try {
                        $plugin = $wa->getPlugin($plugin_id);
                        $external_plugin_discount = 0;

                        // Если проверяется определенный товар, получаем его скидки от Промоакций и суммируем с общими
                        if ($plugin_id === 'promos' && $product_discount_info) {
                            $product = (new \shopPromosPluginHelper())->addCartQuantityToProduct($product_discount_info['product']);
                            \waRequest::setParam('igaponov_skip_frontend_products', 1);
                            $workflow = (new \shopPromosPluginCore())->calculateDiscount($product, array());
                            \waRequest::setParam('igaponov_skip_frontend_products', 0);

                            if (!empty($workflow['discount'])) {
                                // Если проверяется скидка для конкретного товара
                                if ($id) {
                                    $promo_product = reset(ref(array_filter($workflow['products'], function ($product) use ($id) {
                                        return $product['id'] == $id;
                                    })));
                                    if ($promo_product) {
                                        $external_plugin_discount += $promo_product['total_discount'] / $promo_product['quantity'];
                                    }
                                } else {
                                    $external_plugin_discount += $workflow['discount'];
                                }
                            }
                        } else {
                            $discounts = $plugin->orderCalculateDiscount($event_params);
                            // Скидки на товары
                            if (!empty($workflow['products']) && !empty($discounts['items'])) {
                                foreach ($workflow['products'] as $item_id => $product) {
                                    $external_item_discount = ifempty($discounts, 'items', $item_id, 'discount', 0);
                                    // Если скидка на конкретный товар меньше, чем у стороннего плагина, уменьшаем значение скидки у активного правила
                                    if ($external_item_discount > $product['total_discount']) {
                                        foreach ($product['rules'] as $product_rule_id => $product_rule) {
                                            $workflow['active_rules'][$product_rule_id]['discount'] -= $product_rule['discount'];
                                        }
                                    }
                                }
                            }
                            // Оставшиеся скидки
                            if (!empty($discounts['items']) || $discounts['discount'] > 0) {
                                foreach ($workflow['active_rules'] as $k => $active_rule) {
                                    // Удаляем пустые правила
                                    if (($active_rule['discount'] <= 0 && $active_rule['affiliate'] <= 0) || $discounts['discount'] > $active_rule['discount']) {
                                        unset($workflow['active_rules'][$k]);
                                    }
                                }
                            }
                            // Если скидок не осталось, завершаем проверки
                            if (empty($workflow['active_rules'])) {
                                return false;
                            }
                        }
                        if ($product_discount_info && $external_plugin_discount > $plugin_discount) {
                            $result[$id] = false;
                            break;
                        }
                    } catch (\Exception $e) {

                    }
                }

                // Проверяем стандартные купоны
                if (\shopDiscounts::isEnabled('coupons') && !$product_discount_info) {
                    \shopDiscounts::calculate($order, false);
                    if (!empty($order['params']['coupon_discount']) && $order['params']['coupon_discount'] > $plugin_discount) {
                        $result[$id] = false;
                    }
                }
            }
        }
        return $result[$id];
    }

    /**
     * Get frontend rules
     *
     * @return array
     */
    public function getFrontendDiscounts()
    {
        $discounts = (new \shopFlexdiscountPluginModel())->getDiscounts(array("id_as_key" => 1, 'status' => 1));
        foreach ($discounts as $k => $group) {
            if (isset($group['items']) && !$group['items']) {
                unset($discounts[$k]);
            }
        }

        return $discounts;
    }

    /**
     * Get product or sku ID
     *
     * @param array $item
     * @param string $product_type - sku or product
     * @param int|null $default - default value, if id equals zero
     * @return int
     */
    public function getProductId($item, $product_type = 'product', $default = null)
    {
        if ($product_type == 'sku') {
            $id = isset($item['sku_id']) ? (int) $item['sku_id'] : (isset($item['product']['sku_id']) ? (int) $item['product']['sku_id'] : 0);
        } else {
            $id = isset($item['product_id']) ? (int) $item['product_id'] : (isset($item['product']['id']) ? (int) $item['product']['id'] : (isset($item['id']) ? $item['id'] : 0));
        }
        if (!$id && $default !== null) {
            $id = (int) $default;
        }
        return $id;
    }

    public function prepareShopProducts($products, $shop_products = [])
    {
        if (!empty($products)) {
            $shop_products = $shop_products ? $shop_products : \shopFlexdiscountApp::get('runtime.shop/products', []);
            $shop_product_sku_ids = \shopFlexdiscountApp::get('runtime.shop/product_sku_ids', []);
            $sku_ids = [];
            foreach ($products as $p) {
                $product = ($p instanceof \shopProduct) ? $p->getData() : $p;
                $product_id = $this->getProductId($product);
                $sku_id = $this->getProductId($product, 'sku');
                if (!isset($shop_products[$product_id]) && $product_id !== 0) {
                    $shop_products[$product_id] = [
                        'id' => $product_id,
                        'sku_id' => $sku_id,
                        'sku_type' => !empty($p['sku_type']) ? $p['sku_type'] : (!empty($p['product']['sku_type']) ? $p['product']['sku_type'] : 0),
                        'type_id' => isset($p['type_id']) ? $p['type_id'] : (isset($p['product']['type_id']) ? $p['product']['type_id'] : 0)
                    ];
                }
                if (!isset($shop_product_sku_ids[$sku_id]) && $sku_id !== 0) {
                    $sku_ids[$sku_id] = $product_id;
                }
            }
            if ($sku_ids) {
                (new \shopFlexdiscountApp())->set('runtime.shop/product_sku_ids', $shop_product_sku_ids + $sku_ids);
            }
            return $shop_products;
        }
        return [];
    }

    /**
     * Prepare data for 'runtime.shop/product_sku_ids' variable. This is product sku ids
     *
     * @param array $data
     * @return array|mixed|null
     */
    public function prepareShopProductSkuIds($data)
    {
        if (!empty($data)) {
            $shop_product_sku_ids = \shopFlexdiscountApp::get('runtime.shop/product_sku_ids', []);
            $sku_ids = [];
            foreach ($data as $sku_id => $product_id) {
                if (!isset($shop_product_sku_ids[$sku_id])) {
                    $sku_ids[$sku_id] = $product_id;
                }
            }
            if ($sku_ids) {
                $shop_product_sku_ids = $shop_product_sku_ids + $sku_ids;
            }
            return $shop_product_sku_ids;
        }
        return [];
    }

    /**
     * Clear runtime cache
     * @param bool $force
     */
    public function clearCache($force = false)
    {
        static $max_cached_products;

        if ($max_cached_products === null) {
            $max_cached_products = (new \waAppSettingsModel())->get(array('shop', 'flexdiscount'), \shopFlexdiscountProfile::SETTINGS['CACHED_PRODUCTS'], \shopFlexdiscountProfile::DEFAULT_SETTINGS['CACHED_PRODUCTS']);
        }

        $app = new \shopFlexdiscountApp();

        $shop_products = \shopFlexdiscountApp::get('runtime.shop/products', []);
        $is_importexport = \shopFlexdiscountApp::get('env')['is_importexport'];

        if (count($shop_products) > $max_cached_products || $force || $is_importexport) {
            $app->set('runtime.features/all', null);
            $app->set('runtime.features/product_features', []);
            $app->set('runtime.features/processed', null);
            $app->set('runtime.shop/products', []);
            $app->set('runtime.shop/product_sku_ids', []);
            $app->set('runtime.shop/product_params', []);
            $app->set('runtime.shop/product_total_sales', []);
            $app->set('runtime.shop/product_total_number_sales', []);
            $app->set('runtime.shop/product_sku_stocks', []);
            $app->set('runtime.shop/product_stock_change', []);
            $app->set('runtime.conditions/order_prod_int', []);
            $app->set('runtime.conditions/order_prod_cat', []);
            $app->set('runtime.product_workflow', []);
            $app->set('runtime.frontend_products/products', []);
            $app->set('runtime.item_flexdiscount_fields', []);
        }

        if ($is_importexport) {
            $app->set('runtime.shop/category_products', []);
            $app->set('runtime.shop/set_products', []);
            $app->set('runtime.shop/product_params', []);
            $app->set('runtime.shop/product_total_sales', []);
            $app->set('runtime.shop/product_total_number_sales', []);
            $app->set('runtime.shop/product_sku_stocks', []);
            $app->set('runtime.shop/condition_results', []);
            $app->set('runtime.shop/product_stock_change', []);
            $app->set('runtime.conditions/order_prod_int', []);
            $app->set('runtime.conditions/order_prod_cat', []);
            $app->set('runtime.product_workflow', []);
            $app->set('runtime.frontend_products/products', []);
        }
    }

    /**
     * Get coupons from 2 forms and check their lives
     *
     * @param int $order_id
     *
     * @return array
     */
    public function getActiveCouponsInfo($order_id = 0)
    {
        $app = new \shopFlexdiscountApp();
        $coupons = $app::get('runtime.coupons_info');

        if ($coupons === null || \waRequest::param('plugin', '') === 'quickorder') {
            $coupons = [];
            $codes = $app::getOrder()->getSubmittedCouponCodes($order_id);
            if ($codes) {
                // Удаляем пустые купоны
                foreach ($codes as $k => $v) {
                    if (!trim($v)) {
                        unset($codes[$k]);
                    }
                }
                $scm = new \shopFlexdiscountCouponPluginModel();
                if ($order_id) {
                    $com = new \shopFlexdiscountCouponOrderPluginModel();
                    $coupons = $scm->query("SELECT c.*, co.reduced FROM {$scm->getTableName()} c LEFT JOIN {$com->getTableName()} co ON co.code = c.code WHERE co.order_id = '" . (int) $order_id . "'")->fetchAll('id');
                } else {
                    $coupons = $scm->getByField("code", $codes, "id");
                }
                if ($coupons) {
                    if ($app->get('env')['is_frontend']) {
                        foreach ($coupons as $c_id => $c) {
                            if (\shopFlexdiscountHelper::getCouponStatus($c) <= 0) {
                                unset($coupons[$c_id]);
                            }
                        }
                    }
                    // Купоны, прошедшие все проверки.
                    if ($coupons) {
                        // Получаем список всех правил скидок, у которых имеются данные купоны
                        $sfcdm = new \shopFlexdiscountCouponDiscountPluginModel();
                        $sfm = new \shopFlexdiscountPluginModel();
                        $sql = "SELECT sfcdm.* FROM {$sfcdm->getTableName()} sfcdm 
                                LEFT JOIN {$sfm->getTableName()} sfm ON sfm.id = sfcdm.fl_id
                                WHERE sfm.status = '1' AND sfcdm.coupon_id IN ('" . implode("','", array_keys($coupons)) . "')";
                        foreach ($sfcdm->query($sql) as $r) {
                            if (!isset($coupons[$r['coupon_id']]['coupon_rules'])) {
                                $coupons[$r['coupon_id']]['coupon_rules'] = array();
                            }
                            $coupons[$r['coupon_id']]['coupon_rules'][$r['fl_id']] = $r['fl_id'];
                        }
                    }
                }
            }
            $app->set('runtime.coupons_info', $coupons);
        }
        return $coupons;
    }

    /**
     * Add discount in currency, round product discount
     *
     * @param float $discount
     * @param array $rule
     * @param float $quantity
     * @return float|int
     */
    public function preciseProductDiscount($discount, $rule, $quantity)
    {
        // Устанавливаем скидку на каждый товар
        if ($rule['discount'] > 0 && !empty($rule['discounteachitem'])) {
            $discount += $rule['discount'];
        }

        // Округление
        return \shopFlexdiscountApp::getFunction()->round($discount) * $quantity;
    }

    /**
     * Add bonus, round product affiliate
     *
     * @param float $affiliate
     * @param array $rule
     * @param float $quantity
     * @return float|int
     */
    public function preciseProductAffiliate($affiliate, $rule, $quantity)
    {
        // Начисляем бонусы на каждый товар
        if ($rule['affiliate'] > 0 && !empty($rule['affiliateeachitem'])) {
            $affiliate += $rule['affiliate'];
        }

        // Округление
        return \shopFlexdiscountApp::getFunction()->round($affiliate, '', 'affiliate') * $quantity;
    }

    /**
     * Get product minimal price according to limit settings (formula)
     *
     * @param array $limit
     * @param array $item
     * @return int|float
     */
    public function getProductLimitPriceByEquation($limit, $item)
    {
        $product_price_minimum = 0;
        switch ($limit['price1']) {
            case "purchase":
                $product_price_minimum += $item['purchase_price'];
                break;
            case "compare_price":
                $product_price_minimum += $item['compare_price'];
                break;
        }
        if ($limit['currency'] == '%') {
            $price2 = 0;
            switch ($limit['price2']) {
                case "purchase":
                    $price2 += $item['purchase_price'];
                    break;
                case "price":
                    $price2 += $item['price'];
                    break;
                case "compare_price":
                    $price2 += $item['compare_price'];
                    break;
            }
            $product_price_minimum += max(0.0, min(100.0, (float) $limit['value'])) * $price2 / 100;
        } else {
            $product_price_minimum += \shopFlexdiscountApp::getFunction()->shop_currency((float) $limit['value'], $limit['currency'], \shopFlexdiscountApp::get('order.currency'), false);
        }
        return $product_price_minimum;
    }

    /**
     * Calculate product discount
     *
     * @param array|\shopProduct $p
     * @param array $prepared_product
     * @param string $type
     * @param array $params
     * @return array|bool
     */
    public function calculateProductDiscount($p, $prepared_product, $type = 'product', $params = [])
    {
        $app = new \shopFlexdiscountApp();
        $functions = $app::getFunction();
        $current_cur = $app::get('system')['current_currency'];
        $primary_cur = $app::get('system')['primary_currency'];
        $settings = $app::get('settings');

        $product_workflow = $app::get("runtime.product_workflow." . $prepared_product['sku_id'], []);
        if (!$product_workflow) {
            // Добавляем товар к заказу
            $order_params = $app::getOrder()->addToVirtualOrder($prepared_product, $type == 'product' ? $primary_cur : $params['product']['currency']);

            // Вычисляем размер скидки и бонусов
            $workflow = (new \shopFlexdiscountCore())->calculate_discount($order_params, $app::get('core')['discounts']);

            if ($type == 'product') {
                $prepared_product['currency'] = $primary_cur;
            }

            // Сохраняем результат обработки товара
            $product_workflow = \shopFlexdiscountHelper::prepareProductWorkflow($workflow, $prepared_product['sku_id'], $current_cur, $prepared_product, $order_params);
            $app->set("runtime.product_workflow." . $prepared_product['sku_id'], $product_workflow);
        }

        // Проверяем расчет скидок для плагинов. Если установлен максимум и скидка плагина меньше, чем у остальных, прерываем обработку
        if (!$app::getHelper()->checkGeneralDiscountCombiner([
            'product' => $type == 'product' ? $prepared_product : $params['product'],
            'discount' => ifempty($product_workflow, 'clear_discount', 0)
        ])) {
            return false;
        }

        // Если имеется скидка, то заменяем цены у товаров
        if (!empty($product_workflow['clear_discount'])) {
            $p['old_compare_price'] = !empty($prepared_product['compare_price']) ? ($type == 'product' ? $functions->shop_currency($prepared_product['compare_price'], $primary_cur, $current_cur, false) : $prepared_product['compare_price']) : 0;
            $p['old_price'] = $type == 'product' ? $functions->shop_currency($prepared_product['price'], $primary_cur, $current_cur, false) : $prepared_product['price'];
            // Меняем зачеркнутую цену, если не указано иное
            if (empty($settings['use_original_compare_pr']) || (!empty($settings['use_original_compare_pr']) && $p['compare_price'] <= 0)) {
                $p['compare_price'] = $type == 'product' ? $prepared_product['price'] : $p['price'];
            }
            if ($type == 'product') {
                $p['price'] = $functions->shop_currency($product_workflow['clear_price'], $current_cur, $primary_cur, false);
                $p['frontend_compare_price'] = $functions->shop_currency($p['compare_price'], $p['currency'], $current_cur, false);
            } else {
                $p['price'] = $functions->shop_currency($product_workflow['clear_price'], $product_workflow['currency'], $prepared_product['currency'], false);
                $p['frontend_compare_price'] = $functions->shop_currency($p['compare_price'], $params['product']['currency'], $current_cur, false);
            }
            $p['frontend_price'] = $product_workflow['clear_price'];
        }

        // Добавляем переменную в данные о товаре
        if ($type == 'product') {
            $p['flexdiscount_price'] = isset($product_workflow['clear_price']) ? $functions->shop_currency($product_workflow['clear_price'], $current_cur, $primary_cur, false) : $prepared_product['price'];
        } else {
            $p['flexdiscount_price'] = isset($product_workflow['clear_price']) ? $functions->shop_currency($product_workflow['clear_price'], $product_workflow['currency'], $prepared_product['currency'], false) : $p['price'];
        }
        $p['flexdiscount_affiliate'] = ifempty($product_workflow, 'affiliate', 0);
        $p['flexdiscount_discount'] = ifset($product_workflow, 'clear_discount', 0);

        // Устанавливаем наклейку на товар
        if (isset($product_workflow['product']['badge'])) {
            $p['badge'] = $product_workflow['product']['badge'];
        }
        $p['flexdiscount-badge'] = ifset($product_workflow, 'product', 'flexdiscount-badge', '');

        $item_flexdiscount_fields = $app::get('runtime.item_flexdiscount_fields', []);
        $sku_id = $type == 'sku' ? $p['id'] : $p['sku_id'];
        if ($item_flexdiscount_fields[$sku_id]) {
            $p['flexdiscount_item_discount'] = $item_flexdiscount_fields[$sku_id]['flexdiscount_item_discount'];
            $p['flexdiscount_discount_currency'] = $item_flexdiscount_fields[$sku_id]['flexdiscount_discount_currency'];
            $p['flexdiscount_item_affiliate'] = $item_flexdiscount_fields[$sku_id]['flexdiscount_item_affiliate'];
            $p['flexdiscount_affiliate_currency'] = $item_flexdiscount_fields[$sku_id]['flexdiscount_affiliate_currency'];
        }

        return $p;
    }

    /**
     * @return array
     */
    public function getRulesForActiveDiscounts()
    {
        $app = new \shopFlexdiscountApp();
        $groups = $app::get('core')['discounts'];

        $result = [];
        foreach ($groups as $group_id => $group) {
            $rules = $group_id === 0 ? $group : $group['items'];
            foreach ($rules as $k => $rule) {
                if (!empty($rule['show_in_ad'])) {
                    $result[$k] = $rule;
                }
            }
        }

        return $result;
    }

    public function getRuleDiscountAffiliateParams($rule, $product_params, $currency = '')
    {
        $app = new \shopFlexdiscountApp();
        $primary_curr = $app::get('system')['primary_currency'];
        $function = $app::getFunction();
        $currency = $currency ? $currency : $app::get('system')['current_currency'];

        $product_discount = $product_params['flexdiscount_item_discount'];
        $product_discount_currency = $product_params['flexdiscount_discount_currency'];
        $product_affiliate = $product_params['flexdiscount_item_affiliate'];
        $product_affiliate_currency = $product_params['flexdiscount_affiliate_currency'];

        $rule_params = ifempty($rule, 'full_info', ifempty($rule, 'params', $rule));

        $product_discount_type = ifempty($rule_params, 'product_discount_type', ifempty($rule, 'product_discount_type', 'rule'));
        $product_affiliate_type = ifempty($rule_params, 'product_affiliate_type', ifempty($rule, 'product_affiliate_type', 'rule'));

        // Проверяем наличие индивидуальных скидок/бонусов
        if ($product_discount_type == 'field') {
            $rule_discount = 0;
            $rule_discount_percentage = 0;
            if ($product_discount_currency !== '%') {
                $rule_discount = $product_discount ? $function->shop_currency($product_discount, $product_discount_currency ? $product_discount_currency : $primary_curr, $currency, false) : 0;
            } else {
                $rule_discount_percentage = $function->shop_currency($product_discount, $currency, $currency, false);
            }
        } else {
            $rule_discount_currency = ifempty($rule_params, 'discount_currency', $primary_curr);
            $rule_discount_value = ifset($rule_params, 'discount', 0);
            $rule_discount = $function->shop_currency($rule_discount_value, $rule_discount_currency, $currency, false);
            $rule_discount_percentage = ifset($rule_params, 'discount_percentage', 0);
        }
        if ($product_affiliate_type == 'field') {
            $rule_affiliate = $product_affiliate_currency !== '%' ? $product_affiliate : 0;
            $rule_affiliate_percentage = $product_affiliate_currency == '%' ? $function->shop_currency($product_affiliate, $currency, $currency, false) : 0;
        } else {
            $rule_affiliate = ifset($rule_params, 'affiliate', 0);
            $rule_affiliate_percentage = ifset($rule_params, 'affiliate_percentage', 0);
        }

        return [
            'discount' => $rule_discount ? $rule_discount : 0,
            'discount_html' => $rule_discount ? $function->shop_currency_html($rule_discount, $currency, null) : 0,
            'discount_percentage' => $rule_discount_percentage,
            'discount_currency' => $currency,
            'affiliate' => $rule_affiliate,
            'affiliate_percentage' => $rule_affiliate_percentage,
            'product_discount_type' => $product_discount_type,
            'product_affiliate_type' => $product_affiliate_type,
            'code' => ifset($rule_params, 'code', '')
        ];
    }

    public function getServiceVars($product)
    {
        $app = new \shopFlexdiscountApp();
        $type_services_model = new \shopTypeServicesModel();
        $type_service_ids = $type_services_model->getServiceIds($product['type_id']);

        // Fetch services
        $service_model = new \shopServiceModel();
        $product_services_model = new \shopProductServicesModel();

        $product_service_ids = $product_services_model->getServiceIds($product['id'], array(
            'ignore' => array(
                'status' => \shopProductServicesModel::STATUS_FORBIDDEN
            )
        ));

        $services = array_merge($type_service_ids, $product_service_ids);
        $services = array_unique($services);

        $services = $service_model->getById($services);

        $need_round_services = $app::get('system')['wa']->getSetting('round_services');
        if ($need_round_services) {
            \shopRounding::roundServices($services);
        }

        // Convert service.price from default currency to service.currency
        $function = $app::getFunction();
        foreach ($services as &$s) {
            $s['price'] = $function->shop_currency($s['price'], null, $s['currency'], false);
        }
        unset($s);

        $enable_by_type = array_fill_keys($type_service_ids, true);

        // Fetch service variants
        $variants_model = new \shopServiceVariantsModel();
        $rows = $variants_model->getByField('service_id', array_keys($services), true);

        if ($need_round_services) {
            \shopRounding::roundServiceVariants($rows, $services);
        }

        foreach ($rows as $row) {
            if (!$row['price']) {
                $row['price'] = $services[$row['service_id']]['price'];
            } elseif ($services[$row['service_id']]['variant_id'] == $row['id']) {
                $services[$row['service_id']]['price'] = $row['price'];
            }
            $row['status'] = !empty($enable_by_type[$row['service_id']]);
            $services[$row['service_id']]['variants'][$row['id']] = $row;
        }

        // Fetch service prices for specific products and skus
        $rows = $product_services_model->getByField('product_id', $product['id'], true);

        if ($need_round_services) {
            \shopRounding::roundServiceVariants($rows, $services);
        }

        // re-define statuses of service variants for that product
        foreach ($rows as $row) {
            if (!$row['sku_id']) {
                $services[$row['service_id']]['variants'][$row['service_variant_id']]['status'] = $row['status'];
            }
        }

        // Remove disable service variants
        foreach ($services as $service_id => $service) {
            if (isset($service['variants'])) {
                foreach ($service['variants'] as $variant_id => $variant) {
                    if (!$variant['status']) {
                        unset($services[$service_id]['variants'][$variant_id]);
                    }
                }
            }
        }

        // sku_id => [service_id => price]
        $skus_services = array();
        foreach ($product['skus'] as $sku) {
            $skus_services[$sku['id']] = array();
        }

        foreach ($rows as $row) {
            if (!$row['sku_id']) {

                if ($row['status'] && $row['price'] !== null) {
                    // update price for service variant, when it is specified for this product
                    $services[$row['service_id']]['variants'][$row['service_variant_id']]['price'] = $row['price'];
                    // !!! also set other keys related to price
                }
                if ($row['status'] == \shopProductServicesModel::STATUS_DEFAULT) {
                    // default variant is different for this product
                    $services[$row['service_id']]['variant_id'] = $row['service_variant_id'];
                }
            } else {
                if (!$row['status']) {
                    $skus_services[$row['sku_id']][$row['service_id']][$row['service_variant_id']] = false;
                } else {
                    $skus_services[$row['sku_id']][$row['service_id']][$row['service_variant_id']] = $row['price'];
                }
            }
        }

        // Fill in gaps in $skus_services
        foreach ($skus_services as $sku_id => &$sku_services) {
            if (!isset($product['skus'][$sku_id])) {
                continue;
            }
            $sku_price = $product['skus'][$sku_id]['price'];
            foreach ($services as $service_id => $service) {
                if (isset($sku_services[$service_id])) {
                    if ($sku_services[$service_id]) {
                        foreach ($service['variants'] as $v) {
                            if (!isset($sku_services[$service_id][$v['id']]) || $sku_services[$service_id][$v['id']] === null) {
                                $sku_services[$service_id][$v['id']] = array(
                                    $v['name'],
                                    $this->getPrice($v['price'], $service['currency'], $sku_price, $product['currency']),
                                );
                            } elseif ($sku_services[$service_id][$v['id']]) {
                                $sku_services[$service_id][$v['id']] = array(
                                    $v['name'],
                                    $this->getPrice($sku_services[$service_id][$v['id']], $service['currency'], $sku_price, $product['currency']),
                                );
                            }
                        }
                    }
                } else {
                    foreach ($service['variants'] as $v) {
                        $sku_services[$service_id][$v['id']] = array(
                            $v['name'],
                            $this->getPrice($v['price'], $service['currency'], $sku_price, $product['currency']),
                        );
                    }
                }
            }
        }
        unset($sku_services);

        // disable service if all variants are disabled
        foreach ($skus_services as $sku_id => $sku_services) {
            foreach ($sku_services as $service_id => $service) {
                if (is_array($service)) {
                    $disabled = true;
                    foreach ($service as $v) {
                        if ($v !== false) {
                            $disabled = false;
                            break;
                        }
                    }
                    if ($disabled) {
                        $skus_services[$sku_id][$service_id] = false;
                    }
                }
            }
        }

        // Calculate prices for %-based services,
        // and disable variants selector when there's only one value available.
        foreach ($services as $s_id => &$s) {
            if (!$s['variants']) {
                unset($services[$s_id]);
                continue;
            }
            if ($s['currency'] == '%') {
                $item = array(
                    'price' => $product['skus'][$product['sku_id']]['price'],
                    'currency' => $product['currency'],
                );
                \shopProductServicesModel::workupItemServices($s, $item);
            }

            if (count($s['variants']) == 1) {
                $v = reset($s['variants']);
                if ($v['name']) {
                    $s['name'] .= ' ' . $v['name'];
                }
                $s['variant_id'] = $v['id'];
                $s['price'] = $v['price'];
                unset($s['variants']);
                foreach ($skus_services as $sku_id => $sku_services) {
                    if (isset($sku_services[$s_id]) && isset($sku_services[$s_id][$v['id']])) {
                        $skus_services[$sku_id][$s_id] = $sku_services[$s_id][$v['id']][1];
                    }
                }
            }
        }
        unset($s);

        uasort($services, array('shopServiceModel', 'sortServices'));

        return array($services, $skus_services);
    }

    private function getPrice($price, $currency, $product_price, $product_currency)
    {
        $app = new \shopFlexdiscountApp();
        $function = $app::getFunction();
        if ($currency == '%') {
            $round_services = $app::get('system')['wa']->getSetting('round_services');
            if ($round_services) {
                return \shopRounding::roundCurrency($price * $product_price / 100, $product_currency);
            } else {
                return $function->shop_currency($price * $product_price / 100, $product_currency, null, 0);
            }
        } else {
            return $function->shop_currency($price, $currency, null, 0);
        }
    }

    /**
     * @return string
     */
    private function getDiscountCombineType()
    {
        static $discount_combine_type = null;
        if ($discount_combine_type === null) {
            $discount_combine_type = \waSystem::getSetting('discounts_combine', null, 'shop');
        }
        return $discount_combine_type;
    }

    /**
     * Check, if plugin isset in backtrace
     *
     * @param string $plugin_id
     * @return bool
     */
    private function issetPluginCallerInBacktrace($plugin_id)
    {
        $backtrace = \shopFlexdiscountApp::get('debug')['backtrace'];
        if ($backtrace) {
            foreach ($backtrace as $b) {
                if (isset($b['file']) && strpos($b['file'], '/' . $plugin_id . '/') !== false) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Check if plugins isset in calls
     *
     * @param array $plugins
     * @return bool
     */
    private function issetPluginsInCalls($plugins)
    {
        if ($plugins) {
            $active_plugin = \waRequest::param('plugin', \waRequest::request('plugin', ''));
            if (in_array($active_plugin, $plugins)) {
                return true;
            } elseif (!$active_plugin) {
                // Для плагинов, которые генерируют выгрузки, необходимо анализировать адрес, откуда пришел запрос, потому что в коде
                // нигде не указывается, что запрос исходит от плагина
                $request_url = \shopFlexdiscountApp::get('system')['config']->getRequestUrl(false, true);
                // Для заданий по крону проверяем аргументы
                $cli_arg = \waRequest::server('argv');
                foreach ($plugins as $pl) {
                    if (strpos($request_url, $pl) !== false || (!empty($cli_arg[2]) && strpos($cli_arg[2], $pl) !== false) || $this->issetPluginCallerInBacktrace($pl)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
}
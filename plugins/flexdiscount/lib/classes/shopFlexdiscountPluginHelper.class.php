<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopFlexdiscountPluginHelper
{

    /**
     * Output coupon form
     *
     * @return string
     */
    public static function getCouponForm()
    {
        $html = '';
        if (shopDiscounts::isEnabled('flexdiscount')) {
            // Получаем настройки
            $settings = shopFlexdiscountApp::get('settings');

            if (!empty($settings['coupon_form'])) {
                $view = shopFlexdiscountApp::get('system')['wa']->getView();
                $html = $view->fetch('string:' . $settings['coupon_form']);
            }
        }
        return $html;
    }

    /**
     * Get active discounts
     *
     * @param null|int $view_type
     * @return string
     */
    public static function getUserDiscounts($view_type = null)
    {
        $html = '';
        if (shopDiscounts::isEnabled('flexdiscount')) {
            $app = new shopFlexdiscountApp();

            // Получаем настройки
            $settings = $app::get('settings');

            if (!empty($settings['user_discounts'])) {

                $shipping_rules = $app::getHelper()->getRulesForActiveDiscounts();

                // Проверяем расчет скидок для плагинов. Если установлен максимум и скидка плагина меньше, чем у остальных, прерываем обработку
                $workflow = $app::getOrder()->getOrderCalculateDiscount();
                if (!$app::getHelper()->checkGeneralDiscountCombiner([], $workflow) && !$shipping_rules) {
                    return '';
                }

                if ($shipping_rules) {
                    $workflow['active_rules'] += $shipping_rules;
                }

                $view = $app::get('system')['wa']->getView();
                $view->assign(array(
                    'workflow' => $workflow,
                    'fl_discounts' => $workflow['active_rules'],
                    'view_type' => $view_type
                ));
                $html .= "<div class='flexdiscount-user-discounts' data-view-type='" . ($view_type ? waString::escapeAll($view_type) : '0') . "'>";
                waSystem::pushActivePlugin('flexdiscount', 'shop');
                $html .= $view->fetch('string:' . $settings['user_discounts']);
                waSystem::popActivePlugin();
                $html .= "</div>";
                $view->clearAssign(array('workflow', 'fl_discounts', 'view_type'));
            }
        }
        return $html;
    }

    /**
     * Get user affiliate
     *
     * @param null|int $view_type
     * @return string - HTML
     */
    public static function getUserAffiliate($view_type = null)
    {
        $html = '';
        if (shopDiscounts::isEnabled('flexdiscount') && shopAffiliate::isEnabled()) {
            $app = new shopFlexdiscountApp();
            // Получаем настройки
            $settings = $app::get('settings');

            if (!empty($settings['affiliate_block'])) {
                $workflow = $app::getOrder()->getOrderCalculateDiscount();
                $view = $app::get('system')['wa']->getView();
                $view->assign(array(
                    'workflow' => $workflow,
                    'fl_affiliate' => $workflow['affiliate'],
                    'view_type' => $view_type
                ));
                $html .= "<div class='flexdiscount-user-affiliate wa-order-bonus' data-view-type='" . ($view_type ? waString::escapeAll($view_type) : '0') . "'>";
                waSystem::pushActivePlugin('flexdiscount', 'shop');
                $html .= $view->fetch('string:' . $settings['affiliate_block']);
                waSystem::popActivePlugin();
                $html .= "</div>";
                $view->clearAssign(array('workflow', 'fl_affiliate', 'view_type'));
            }
        }
        return $html;
    }

    /**
     * Get product discounts
     *
     * @param array|object|int $product
     * @param string $view_type - type of display
     * @param int $sku_id - product sku ID
     * @param bool $return_html
     * @return string|array
     */
    public static function getProductDiscounts($product, $view_type = null, $sku_id = 0, $return_html = true)
    {
        $html = '';
        if (shopDiscounts::isEnabled('flexdiscount') && !empty($product)) {

            $app = new shopFlexdiscountApp();

            // Получаем настройки
            $settings = $app::get('settings');
            if (empty($settings['product_discounts'])) {
                return $return_html ? $html : array();
            }

            // Получаем товар
            if (is_int($product)) {
                $product = new shopProduct($product);
            } else {
                $product = ($product instanceof shopProduct) ? $product->getData() : $product;
            }
            $html .= "<div class='flexdiscount-product-discount product-id-" . $product['id'] . (!$sku_id ? " f-update-sku" : "") . "' data-product-id='" . $product['id'] . "' data-view-type='" . ($view_type ? waString::escapeAll($view_type) : '1') . "'";

            if (!$sku_id) {
                $sku_id = $product['sku_id'];
            } else {
                $find_sku = $sku_id;
            }

            $workflow = $app::get("runtime.product_workflow.$sku_id", []);

            // Если товар уже обрабатывался, то возвращаем его данные
            if (!$workflow) {
                // Правила скидок
                $discount_groups = $app::get('core.discounts');
                if (!$discount_groups) {
                    $return_html ? $html : array();
                }

                $product = shopFlexdiscountWorkflow::prepareProduct($product, isset($find_sku) ? $find_sku : $sku_id);
                $sku_id = $product['sku_id'];

                // Добавляем товар к заказу
                $order_params = $app::getOrder()->addToVirtualOrder($product);

                // Вычисляем размер скидки и бонусов
                $workflow = (new shopFlexdiscountCore())->calculate_discount($order_params, $discount_groups);
                // Сохраняем результат обработки товара
                $workflow = shopFlexdiscountHelper::prepareProductWorkflow($workflow, $sku_id, $app::get('system.current_currency'), $product, $order_params);

                $app->set("runtime.product_workflow.$sku_id", $workflow);
            }

            // Проверяем расчет скидок для плагинов. Если установлен максимум и скидка плагина меньше, чем у остальных, прерываем обработку
            if (!$app::get('runtime.skip_check_discount_combiner') && !$app::getHelper()->checkGeneralDiscountCombiner(['product' => $product, 'discount' => !empty($workflow['clear_discount']) ? $workflow['clear_discount'] : 0])) {
                return $return_html ? '' : ['discount' => 0, 'product' => $workflow['product'], 'clear_discount' => 0, 'affiliate' => 0];
            }

            if (!$return_html) {
                return $workflow;
            }

            $view = $app::get('system')['wa']->getView();
            $view->assign(array(
                'workflow' => $workflow,
                'fl_discounts' => $workflow['items'],
                'view_type' => $view_type
            ));
            $html .= " data-sku-id='" . $sku_id . "'>";
            waSystem::pushActivePlugin('flexdiscount', 'shop');
            $html .= $view->fetch('string:' . $settings['product_discounts']);
            waSystem::popActivePlugin();
            $html .= "</div>";
            $view->clearAssign(array('workflow', 'fl_discounts', 'view_type'));
        }
        return $html;
    }

    /**
     * Get available discounts
     * If isset product, then get discounts only to it
     *
     * @param array|shopProduct|int $product
     * @param string $view_type - type of display
     * @param int $sku_id
     * @param array $filter_by
     * @param bool $return_html
     * @return string|array
     */
    public static function getAvailableDiscounts($product = null, $view_type = null, $sku_id = 0, $filter_by = array(), $return_html = true)
    {
        $html = '';
        if (shopDiscounts::isEnabled('flexdiscount')) {

            $data = array();

            // Получаем настройки
            $settings = shopFlexdiscountApp::get('settings');
            if (empty($settings['available_discounts'])) {
                return $return_html ? $html : $data;
            }
            // Фильтры скидок
            $filter_by = is_array($filter_by) ? $filter_by : (!empty($filter_by) ? (array) $filter_by : $filter_by);
            if (!empty($settings['flexdiscount_avail_discounts']['filter_by'])) {
                $filter_by = array_merge($filter_by, $settings['flexdiscount_avail_discounts']['filter_by']);
            }

            // Массив доступных скидок
            $available_discount = shopFlexdiscountWorkflow::getAvailableDiscounts($product, $sku_id, $filter_by);

            // Удаляем правила, которые повторяются в активных скидках
            if (!empty($settings['flexdiscount_avail_discounts']['hide_duplicate'])) {
                // Получаем значения скидок для корзины
                $workflow = self::getProductDiscounts($product, null, 0, false);
                foreach ($available_discount as $avail_id => $item) {
                    if (isset($workflow['items'][$avail_id])) {
                        unset($available_discount[$avail_id]);
                    }
                }
            }

            if ($available_discount) {
                // Подготовка данных для вывода
                $data = shopFlexdiscountHelper::prepareDiscountRuleData('available', $available_discount, shopFlexdiscountApp::get('system')['current_currency'], array('product' => $product));
                // Сортируем правила
                $data = shopFlexdiscountHelper::sortRules($data);
            }

            if (!$return_html) {
                return $data;
            }

            $html .= "<div class='flexdiscount-available-discount product-id-" . $product['id'] . (!$sku_id ? " f-update-sku" : "") . "' data-product-id='" . $product['id'] . "' data-view-type='" . ($view_type ? waString::escapeAll($view_type) : '') . "'";
            $html .= $filter_by ? " data-filter-by='" . implode(',', $filter_by) . "'" : "";
            $html .= " data-sku-id='" . $product['sku_id'] . "'>";

            $view = shopFlexdiscountApp::get('system')['wa']->getView();
            $view->assign(array(
                'fl_product' => $product,
                'fl_discounts' => $data,
                'view_type' => $view_type
            ));
            waSystem::pushActivePlugin('flexdiscount', 'shop');
            $html .= $view->fetch('string:' . $settings['available_discounts']);
            waSystem::popActivePlugin();
            $html .= "</div>";
            $view->clearAssign(array('fl_discounts', 'view_type', 'fl_product'));
        }
        return $html;
    }

    /**
     * @param null $product
     * @param null $view_type
     * @param int $sku_id
     * @param array $filter_by
     * @param bool $return_html
     * @return array|string
     * @deprecated
     */
    public static function getAvailibleDiscounts($product = null, $view_type = null, $sku_id = 0, $filter_by = array(), $return_html = true)
    {
        return self::getAvailableDiscounts($product, $view_type, $sku_id, $filter_by, $return_html);
    }

    /**
     * Get deny rules
     * If isset product, then get rules only to it
     *
     * @param array|shopProduct|int $product
     * @param string $view_type - type of display
     * @param int $sku_id
     * @param bool $return_html
     * @return string|array
     */
    public static function getDenyRules($product = null, $view_type = null, $sku_id = 0, $return_html = true)
    {
        $html = '';
        if (shopDiscounts::isEnabled('flexdiscount')) {

            $data = array();

            // Получаем настройки
            $settings = shopFlexdiscountApp::get('settings');
            if (empty($settings['deny_discounts'])) {
                return $return_html ? $html : $data;
            }

            // Массив правил запрета
            $deny_rules = shopFlexdiscountWorkflow::getAllDenyRules($product, $sku_id);

            if ($deny_rules) {
                // Подготовка данных для вывода
                foreach ($deny_rules as $dr) {
                    if (!$dr) {
                        continue;
                    }
                    $data[$dr['id']] = array(
                        'name' => $dr['name'],
                        'code' => $dr['code'],
                    );
                }
            }

            if (!$return_html) {
                return $data;
            }

            $html .= "<div class='flexdiscount-deny-discount product-id-" . $product['id'] . (!$sku_id ? " f-update-sku" : "") . "' data-product-id='" . $product['id'] . "' data-view-type='" . ($view_type ? waString::escapeAll($view_type) : '') . "'";
            $html .= " data-sku-id='" . $product['sku_id'] . "'>";

            $view = shopFlexdiscountApp::get('system')['wa']->getView();
            $view->assign(array(
                'fl_deny_rules' => $data,
                'view_type' => $view_type
            ));
            $html .= $view->fetch('string:' . $settings['deny_discounts']);
            $html .= "</div>";
            $view->clearAssign(array('fl_deny_rules', 'view_type'));
        }
        return $html;
    }

    /**
     * Get product price with discount
     *
     * @param array|int $product
     * @param int $sku_id
     * @param int $view_type
     * @return string
     */
    public static function price($product, $sku_id = 0, $view_type = 0)
    {
        $html = '';

        if (shopDiscounts::isEnabled('flexdiscount') && !empty($product)) {
            $settings = shopFlexdiscountApp::get('settings');

            if (empty($settings['price_discounts'])) {
                return '';
            }

            $workflow = self::getProductDiscounts($product, null, $sku_id, false);
            $html .= "<div";
            $show_block = 1;
            if (!$workflow['discount'] && empty($settings['enable_price_output']['not_hide'])) {
                $show_block = 0;
                $html .= " style='display:none'";
            }
            $html .= " class='flexdiscount-price-block flexdiscount-" . ($show_block ? 'show' : 'hide') . " product-id-" . $product['id'] . (!$sku_id ? " f-update-sku" : "") . "' data-product-id='" . $product['id'] . "' data-sku-id='" . (!empty($workflow['product']['sku_id']) ? $workflow['product']['sku_id'] : $product['sku_id']) . "' data-price='" . (!empty($workflow['clear_price']) ? $workflow['clear_price'] : $product['price']) . "' data-view-type='" . ($view_type ? waString::escapeAll($view_type) : '0') . "'><div>";
            if ($workflow['discount']) {
                $view = shopFlexdiscountApp::get('system')['wa']->getView();
                $view->assign(array(
                    'fl_price' => $workflow['clear_price'],
                    'workflow' => $workflow,
                    'view_type' => $view_type,
                ));
                $html .= $view->fetch('string:' . $settings['price_discounts']);
                $view->clearAssign(array('fl_price', 'workflow', 'view_type'));
            } elseif (!empty($settings['enable_price_output']['not_hide'])) {
                if (empty($settings['price_output_template'])) {
                    $settings['price_output_template'] = '$price';
                }
                $template_ruble_sign = strpos($settings['price_output_template'], '$price_html') !== false ? 1 : 0;
                $price_without_discount = $template_ruble_sign ? shopFlexdiscountApp::getFunction()->shop_currency_html($workflow['product']['price'], $workflow['product']['currency']) : shopFlexdiscountApp::getFunction()->shop_currency($workflow['product']['price'], $workflow['product']['currency']);
                $html .= str_replace(array('$price_html', '$price'), $price_without_discount, $settings['price_output_template']);
            }
            $html .= "</div></div>";
        }
        return $html;
    }

    /**
     * Generate coupon
     *
     * @param int $generator_id
     * @return string
     */
    public static function generateCoupon($generator_id)
    {
        $coupon_model = new shopFlexdiscountCouponPluginModel();
        $coupon = $coupon_model->getCoupon($generator_id);
        // Проверяем, существует ли генератор купонов
        if ($coupon['type'] == 'generator' && !empty($coupon['fl_id'])) {
            $repeat = 0;
            $symbols = $coupon['symbols'];
            do {
                if ($repeat > 30) {
                    $symbols .= '!@#$%^&*(){}[]:;<>,./?';
                } elseif ($repeat > 50) {
                    $symbols = false;
                    break;
                }

                $symbols = ($coupon['prefix'] ? $coupon['prefix'] : '') . shopFlexdiscountCouponPluginModel::generateCode($symbols, $coupon['length']);
                $repeat++;
            } while ($coupon_model->issetCoupon($symbols));

            if ($symbols === false) {
                return '';
            }

            // Время жизни купона
            if ($coupon['lifetime']) {
                $coupon['start'] = date("Y-m-d H:i:s", time());
                $coupon['end'] = date("Y-m-d H:i:s", time() + $coupon['lifetime']);
            }

            $new_coupon = array(
                "code" => $symbols,
                "limit" => $coupon['limit'],
                "user_limit" => $coupon['user_limit'],
                "start" => $coupon['start'] ? $coupon['start'] : null,
                "end" => $coupon['end'] ? $coupon['end'] : null,
                "create_datetime" => date("Y-m-d H:i:s"),
                "comment" => ''
            );
            if ($coupon_id = $coupon_model->save($new_coupon)) {
                (new shopFlexdiscountCouponDiscountPluginModel())->multipleInsert(array("coupon_id" => $coupon_id, "fl_id" => $coupon['fl_id']));
                return $symbols;
            }
        }
        return '';
    }

    /**
     * Get active rule coupons
     *
     * @param int $order_id
     * @return array
     * @throws waException
     */
    public static function getActiveCoupons($order_id = 0)
    {
        $coupons = array();
        if (shopDiscounts::isEnabled('flexdiscount')) {
            $workflow = shopFlexdiscountApp::getOrder()->getOrderCalculateDiscount($order_id);
            if (!empty($workflow['active_rules'])) {
                foreach ($workflow['active_rules'] as $ar) {
                    if (isset($ar['coupon_code'])) {
                        $coupons[$ar['coupon_id']] = array(
                            'coupon_code' => $ar['coupon_code'],
                            'coupon_discount' => (float) $ar['discount']
                        );
                    }
                }
            }
        }
        return $coupons;
    }

    /**
     * Get plugin affiliate bonus + default bonuses, if such exists
     *
     * @param int $default_bonuses
     * @param int|array $order_id
     * @return int
     */
    public static function calculateBonus($default_bonuses = 0, $order_id = 0)
    {
        if (is_array($order_id)) {
            if (isset($order_id['items'])) {
                $order_id = reset($order_id['items'])['order_id'];
            } else {
                $order_id = $order_id['id'];
            }
        }

        if (shopAffiliate::isEnabled()) {
            $workflow = shopFlexdiscountApp::getOrder()->getOrderCalculateDiscount($order_id);
            if ($workflow['affiliate']) {
                $default_bonuses += $workflow['affiliate'];
            }
        }
        return $default_bonuses;
    }

    /**
     * Check if product has discount
     *
     * @param array|int $product
     * @param int $sku_id
     * @return float
     */
    public static function hasDiscount($product, $sku_id = 0)
    {
        $workflow = self::getProductDiscounts($product, null, $sku_id, false);
        return !empty($workflow['clear_discount']) ? $workflow['clear_discount'] : 0;
    }

    /**
     * Get CSS styles
     *
     * @return string
     */
    public static function getCssStyles()
    {
        $css = '';
        if (shopDiscounts::isEnabled('flexdiscount')) {
            $settings = shopFlexdiscountApp::get('settings');
            if (!empty($settings['styles_output']) && $settings['styles_output'] == 'helper') {
                $helper = new shopFlexdiscountHelper();
                $css .= $helper->getCssStyles();
            }
        }
        return $css;
    }

    /**
     * Add products and skus to shopProducts array.
     * Use this function to add extra skus, so plugin can calculate discounts for them.
     * Many conditions use $shop_products array in calculations. If you want to extend it, feel free to do it
     * Using this function be careful with params
     * @param array $params array(
     *                          'products' => [(int) product_id => (object|array) $product],
     *                          'skus' => [(int) sku_id => (int) product_id]
     *                      )
     * E.g. in template:
     * {$params = ['products' => [], 'skus' => []]}
     * {foreach $products as $product}
     * {$params['products'][$product.id] = $product}
     * {if !empty($product.skus)}
     * {foreach $product.skus as $ps}
     * {$params['skus'][$ps.id] = $product.id}
     * {/foreach}
     * {/if}
     * {/foreach}
     * {shopFlexdiscountPluginHelper::prepareProducts($params)}
     *
     */
    public static function prepareProducts($params)
    {
        if (waSystemConfig::isDebug()) {
            waLog::log('shopFlexdiscountPluginHelper::prepareProducts() is using.', 'flexdiscount.log');
        }
        if (!empty($params['products'])) {
            (new shopFlexdiscountApp())->set('runtime.shop/products', shopFlexdiscountApp::getHelper()->prepareShopProducts($params['products']));
        }
        if (!empty($params['skus'])) {
            (new shopFlexdiscountApp())->set('runtime.shop/product_sku_ids', shopFlexdiscountApp::getHelper()->prepareShopProductSkuIds($params['skus']));
        }
    }

    /**
     * Get product price with discount on cart page
     *
     * @param array $product
     * @param bool $mult_quantity - should we show price for all quantity of products or just for single product
     * @param int $html - html format for ruble sign
     * @param bool $format - use currencies or return clear price
     * @param bool $update - use html wrap to update price
     * @return string
     * @deprecated
     */
    public static function cartPrice($product, $mult_quantity = true, $html = null, $format = true, $update = true)
    {
        // Массив обработанных товаров
        $cart_products = shopFlexdiscountApp::get("runtime.cart_products", []);

        // Если плагин будет отключен, то выведется обычная цена.
        // Это сделано на случай, если пользователь заменит стандартный вывод цены в шаблоне
        if (!empty($product)) {
            $functions = shopFlexdiscountApp::getFunction();
            $price = $product_price = $functions->shop_currency($product['price'], $product['currency'], null, false);
            $quantity = !empty($product['quantity']) ? $product['quantity'] : 1;
            $total_discount = 0;
            if (shopDiscounts::isEnabled('flexdiscount')) {

                $html = ($html !== null && $html) ? 1 : 0;

                if (!isset($cart_products[$product['sku_id']])) {
                    // Получаем значения скидок для корзины
                    waRequest::setParam('igaponov_skip_frontend_products', 1);
                    $workflow = shopFlexdiscountApp::getOrder()->getOrderCalculateDiscount();
                    waRequest::setParam('igaponov_skip_frontend_products', 0);
                    // Если имеется скидка
                    if ($workflow['discount']) {

                        /* Создаем массив из ключей sku_id. Сейчас это item_id */
                        $workflow_products = [];
                        foreach ($workflow['products'] as $workflow_product) {
                            $workflow_products[$workflow_product['sku_id']] = $workflow_product;
                        }

                        // Если искомый товар содержится среди скидочных
                        if (!empty($workflow_products[$product['sku_id']])) {
                            $workflow_product = $workflow_products[$product['sku_id']];
                            // Вычисляем цену со скидкой
                            $price_with_discount = $price - $workflow_product['discount'];
                            if ($price_with_discount < 0) {
                                $price_with_discount = 0;
                            }
                            // Запоминаем обработанный товар
                            $cart_products[$product['sku_id']] = array(
                                "price" => $price_with_discount,
                                "quantity" => $quantity,
                                "total_discount" => $workflow_product['total_discount']
                            );
                            $price = $cart_products[$product['sku_id']]['price'];
                            $quantity = $cart_products[$product['sku_id']]['quantity'];
                            $total_discount = $workflow_product['total_discount'];

                            (new shopFlexdiscountApp())->set('runtime.cart_products', $cart_products);
                        }
                    }
                } else {
                    $price = $cart_products[$product['sku_id']]['price'];
                    $quantity = $cart_products[$product['sku_id']]['quantity'];
                    $total_discount = $cart_products[$product['sku_id']]['total_discount'];
                }
            }
            $clear_price = $mult_quantity ? ($product_price * $quantity - $total_discount) : $price;
            $clear_product_price = $mult_quantity ? ($product_price * $quantity) : $product_price;
            $price = $format ? ($html ? $functions->shop_currency_html($clear_price, true) : $functions->shop_currency($clear_price, true)) : $functions->shop_currency($clear_price, true, null, false);
            $return = $update ? '<span class="flexdiscount-cart-price cart-id-' . $product['id'] . '" data-cart-id="' . $product['id'] . '"  data-mult="' . $mult_quantity . '" data-html="' . $html . '" data-format="' . $format . '" data-price="' . $clear_price . '" data-product-price="' . $clear_product_price . '">' . $price . '</span>' : $price;
            return $return;
        }
    }

    /**
     * @param $product
     * @param int $sku_id
     * @param array $params
     * @param int $quantity
     * @param bool $return_html
     * @return array|string
     * @deprecated
     */
    public static function getCurrentDiscount($product, $sku_id = 0, $params = array(), $quantity = 1, $return_html = true)
    {
        return self::getProductDiscounts($product, null, $sku_id, false);
    }

}

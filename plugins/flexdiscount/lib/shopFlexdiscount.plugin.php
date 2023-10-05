<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopFlexdiscountPlugin extends shopPlugin
{

    // Купоны, участвующие в скидках.
    private static $coupons = array();

    private static $profile;

    public function __construct($info)
    {
        parent::__construct($info);
        if (self::$profile === null && shopFlexdiscountProfile::isEnabled()) {
            self::$profile = new shopFlexdiscountProfile();
            ini_set('memory_limit', -1);
        }
    }

    public function backendMenu()
    {
        $wa = shopFlexdiscountApp::get('system')['wa'];
        $user = $wa->getUser();
        if ($user->isAdmin() || $user->getRights("shop", "flexdiscount_rules")) {
            $output = (new waSmarty3View($wa))->fetch((new shopFlexdiscountHelper())->getTemplatePath('backend/include.backend.menu.html'));
            return array("core_li" => $output);
        }
    }

    public function backendProductSkuSettings($params)
    {
        if (shopDiscounts::isEnabled('flexdiscount')) {
            $view = new waSmarty3View(shopFlexdiscountApp::get('system')['wa']);
            $view->assign('sku', $params['sku']);
            $view->assign('sku_id', $params['sku_id']);
            $view->assign('currencies', shopFlexdiscountApp::get('system')['config']->getCurrencies());
            return $view->fetch((new shopFlexdiscountHelper())->getTemplatePath('backend/include.backend.product.sku.settings.html'));
        }
        return null;
    }

    public function backendProductEdit($product)
    {
        if (shopDiscounts::isEnabled('flexdiscount')) {
            $view = new waSmarty3View(shopFlexdiscountApp::get('system')['wa']);
            $view->assign('currencies', shopFlexdiscountApp::get('system')['config']->getCurrencies());
            $view->assign('product', $product->getData());
            return ['basics' => $view->fetch((new shopFlexdiscountHelper())->getTemplatePath('backend/include.backend.product.edit.html'))];
        }
    }

    public function backendSettingsDiscounts()
    {
        $enabled = shopDiscounts::isEnabled('flexdiscount');
        $type = array(
            "id" => "flexdiscount",
            "name" => _wp("Flexdiscount"),
            "url" => "?plugin=flexdiscount&module=settings",
            "status" => ($enabled ? true : false)
        );
        return array('flexdiscount' => $type);
    }

    public function backendOrder($params)
    {
        if (shopDiscounts::isEnabled('flexdiscount')) {
            $output = array('title_suffix' => '', 'action_button' => '', 'action_link' => '', 'info_section' => '');
            $view = new waSmarty3View(shopFlexdiscountApp::get('system')['wa']);

            // Отображение скидок для каждого товара
            $settings = shopFlexdiscountApp::get('settings');
            if (!empty($settings['backend_product_discount']) && !empty($params['items'])) {
                foreach ($params['items'] as &$item) {
                    $item['total_discount_html'] = shopFlexdiscountApp::getFunction()->shop_currency_html($item['total_discount'], $params['currency'], $params['currency']);
                    $item['total_html'] = shopFlexdiscountApp::getFunction()->shop_currency_html($item['price'] * $item['quantity'] - $item['total_discount'], $params['currency'], $params['currency']);
                }
                $view->assign('items', $params['items']);
                $view->assign('section', 'info_section');
                $output['info_section'] = $view->fetch((new shopFlexdiscountHelper())->getTemplatePath('backend/include.backend.order.html'));
            }

            if ($coupons = (new shopFlexdiscountCouponPluginModel())->getCouponsByOrderId($params['id'])) {
                $view->assign('coupons', $coupons);
                $view->assign('section', 'action_link');
                $output['action_link'] = $view->fetch((new shopFlexdiscountHelper())->getTemplatePath('backend/include.backend.order.html'));
            }

            return $output;
        }
    }

    public function backendOrders()
    {
        // Выводим часть стилей, чтобы оформить Заказы
        if (shopDiscounts::isEnabled('flexdiscount')) {
            return ['sidebar_section' => (new waSmarty3View(shopFlexdiscountApp::get('system')['wa']))->fetch((new shopFlexdiscountHelper())->getTemplatePath('backend/include.backend.orders.html'))];
        }
    }

    public function backendOrderEdit($params)
    {
        if (shopDiscounts::isEnabled('flexdiscount')) {
            $settings = shopFlexdiscountApp::get('settings');
            $params['id'] = !isset($params['id']) ? 0 : $params['id'];
            $view = new waSmarty3View(shopFlexdiscountApp::get('system')['wa']);
            $view->assign('order_id', $params['id']);
            $view->assign('params', $params);
            $view->assign('forceRecalculate', !empty($settings['boe_force_calcultate']) ? 1 : 0);
            $view->assign('coupons', (new shopFlexdiscountCouponPluginModel())->getCouponsByOrderId($params['id']));
            return $view->fetch((new shopFlexdiscountHelper())->getTemplatePath('backend/include.backend.order.edit.html'));
        }
        return '';
    }

    public function checkoutBeforeShipping($params)
    {
        if (shopDiscounts::isEnabled('flexdiscount')) {
            $this->updateContactFieldsFromParams($params['result']);
        }
    }

    public function checkoutAfterShipping($params)
    {
        if (shopDiscounts::isEnabled('flexdiscount')) {
            if (!empty($params['process_result']['data']) && $params['process_result']['data']['origin'] == 'form') {

                // Сохраняем информацию о заказе
                $cache = new waRuntimeCache('flexdiscount_checkout_params');
                $checkout_params = [
                        'shipping' => (new shopFlexdiscountHelper())->getShippingParams($params['process_result']['result']),
                        'contact' => ''
                    ] + ($cache->isCached() ? $cache->get() : []);
                $cache->set($checkout_params);
            }
        }
    }

    public function checkoutAfterPayment($params)
    {
        if (shopDiscounts::isEnabled('flexdiscount')) {
            if (!empty($params['process_result']['data']) && $params['process_result']['data']['origin'] == 'form') {

                // Сохраняем информацию о заказе
                $cache = new waRuntimeCache('flexdiscount_checkout_params');
                $checkout_params = [
                        'payment' => ifempty($params, 'process_result', 'result', 'selected_method_id', 0),
                    ] + ($cache->isCached() ? $cache->get() : []);
                $cache->set($checkout_params);
            }
        }
    }

    public function checkoutRenderShipping($params)
    {
        if (shopDiscounts::isEnabled('flexdiscount')) {
            // Сохраняем информацию о заказе
            $cache = new waRuntimeCache('flexdiscount_checkout_params');
            $checkout_params = [
                'shipping' => ['id' => 0, 'rate_id' => 0],
                'contact' => ''
            ];
            if ($cache->isCached()) {
                $checkout_params = array_merge($checkout_params, $cache->get());
            }

            if (!empty($params['vars']['shipping'])) {
                if (!$cache->isCached()) {
                    waRequest::setParam('flexdiscount_ss8_force_update', 1);
                }
                $checkout_params['shipping'] = (new shopFlexdiscountHelper())->getShippingParams($params['vars']['shipping']);
            }

            // Обновляем контактные поля
            $this->updateContactFieldsFromParams($params['vars']);

            $cache->set($checkout_params);
        }
    }

    public function checkoutRenderPayment($params)
    {
        if (shopDiscounts::isEnabled('flexdiscount')) {
            $checkout_params = [
                'payment' => 0
            ];
            // Сохраняем информацию о заказе
            $cache = new waRuntimeCache('flexdiscount_checkout_params');
            if ($cache->isCached()) {
                $checkout_params = array_merge($checkout_params, $cache->get());
            }

            if (!empty($params['vars']['payment']['selected_method_id'])) {
                if (!$cache->isCached()) {
                    waRequest::setParam('flexdiscount_ss8_force_update', 1);
                }
                $checkout_params['payment'] = (int) $params['vars']['payment']['selected_method_id'];
            }
            $cache->set($checkout_params);
        }
    }

    public function checkoutBeforeConfirm($params)
    {
        if (shopDiscounts::isEnabled('flexdiscount')) {
            if (!empty($params['data']['order']['shipping']) && $params['data']['order']['shipping'] > 0) {
                $cache = new waRuntimeCache('flexdiscount_shipping_price');
                $cache->set(['rate' => $params['data']['order']['shipping'], 'currency' => $params['data']['order']['currency']]);
                waRequest::setParam('flexdiscount_force_calculate', 1);
            }

            if (waRequest::isXMLHttpRequest()) {
                // Запускаем вычисление скидок
                $params['data']['order']['discount'];
                $cache = new waRuntimeCache('flexdiscount_shipping_discount');
                $shipping_discount = $cache->isCached() ? $cache->get() : 0;

                // Изменяем цену доставки
                if ($params['data']['order']['shipping'] !== null) {
                    $params['data']['order']['shipping'] -= $shipping_discount;
                    if ($params['data']['order']['shipping'] < 0) {
                        $params['data']['order']['shipping'] = 0;
                    }
                }
            }
        }
    }

    public function checkoutRenderDetails($params)
    {
        if (shopDiscounts::isEnabled('flexdiscount')) {

            // Для динамического обновления способов доставки, необходимо инициировать пересчет скидок на доставку, чтобы кешировать актуальные значения
            if (waRequest::isXMLHttpRequest()) {
                $cache = new waRuntimeCache('flexdiscount_touched_targets');
                if ($cache->isCached()) {
                    $touched_targets = $cache->get();
                    if (isset($touched_targets['target_shipping'])) {
                        shopFlexdiscountApp::getOrder()->getOrderCalculateDiscount(0, true);
                    }
                }
            }

            $cache = new waRuntimeCache('flexdiscount_shipping_discount');
            $shipping_discount = $cache->isCached() ? $cache->get() : 0;
            if ($shipping_discount && !empty($params['vars']['details']['shipping_rate']['rate'])) {
                $params['vars']['details']['shipping_rate']['rate'] -= $shipping_discount;
                if ($params['vars']['details']['shipping_rate']['rate'] < 0) {
                    $params['vars']['details']['shipping_rate']['rate'] = 0;
                }
            }
        }
    }

    public function checkoutPreparedConfirm($params)
    {
        if (shopDiscounts::isEnabled('flexdiscount')) {
            // Запускаем вычисление скидок
            $params['data']['order']['discount'];
            $cache = new waRuntimeCache('flexdiscount_shipping_discount');
            $shipping_discount = $cache->isCached() ? $cache->get() : 0;

            if (!empty($params['prepare_result']['result']['shipping']) && $params['prepare_result']['result']['shipping'] !== null) {
                $params['prepare_result']['result']['shipping'] -= $shipping_discount;
                $params['prepare_result']['result']['total'] -= $shipping_discount;
                if ($params['prepare_result']['result']['shipping'] < 0) {
                    $params['prepare_result']['result']['total'] += (-1) * $params['prepare_result']['result']['shipping'];
                    $params['prepare_result']['result']['shipping'] = 0;
                }
            }
        }
    }

    public function frontendMyNav()
    {
        if (shopDiscounts::isEnabled('flexdiscount')) {
            // Получаем настройки
            $settings = shopFlexdiscountApp::get('settings');
            if (!empty($settings['flexdiscount_my_discounts']['value'])) {
                $view = new waSmarty3View(shopFlexdiscountApp::get('system')['wa']);
                $view->assign('page_name', !empty($settings['flexdiscount_my_discounts']['page_name']) ? $settings['flexdiscount_my_discounts']['page_name'] : _wp('Your discounts'));
                return $view->fetch((new shopFlexdiscountHelper())->getTemplatePath('frontend/include.frontend.my.nav.html'));
            }
        }
    }

    public function frontendHead()
    {
        if (shopDiscounts::isEnabled('flexdiscount')) {
            $this->addJs('js/flexdiscountFrontend.' . (!waSystemConfig::isDebug() ? 'min.' : '') . 'js');

            $view = new waSmarty3View(shopFlexdiscountApp::get('system')['wa']);
            $view->assign('plugin_id', $this->getId());
            $view->assign('helper', new shopFlexdiscountHelper());
            $view->assign('settings', shopFlexdiscountApp::get('settings'));

            return $view->fetch((new shopFlexdiscountHelper())->getTemplatePath('frontend/include.frontend.head.html'));
        }
    }

    public function frontendFooter()
    {
        if (shopDiscounts::isEnabled('flexdiscount')) {
            $settings = shopFlexdiscountApp::get('settings');

            // Вывод стилей в подвале сайта
            if (!empty($settings['styles_output']) && $settings['styles_output'] == 'footer') {
                return (new shopFlexdiscountHelper())->getCssStyles();
            }
        }
    }

    public function frontendOrderCartVars(&$params)
    {
        if (shopDiscounts::isEnabled('flexdiscount')) {
            $settings = shopFlexdiscountApp::get('settings');

            $output = array();
            // Бонусы
            if (shopAffiliate::isEnabled()) {
                $workflow = shopFlexdiscountApp::getOrder()->getOrderCalculateDiscount();
                $bonus = $workflow['affiliate'] + (float) $params['affiliate']['add_affiliate_bonus'];
                $params['affiliate']['add_affiliate_bonus'] = $bonus;
            }

            // Подменяем данные на витрине магазина о скидке по купону
            $coupon_discounts = shopFlexdiscountPluginHelper::getActiveCoupons();
            $fl_coupon_discount = 0;
            foreach ($coupon_discounts as $cd) {
                $fl_coupon_discount += $cd['coupon_discount'];
            }
            if (!isset($params['coupon_discount'])) {
                $params['coupon_discount'] = 0;
            }
            $params['coupon_discount'] += $fl_coupon_discount;

            // Вывод примененных скидок
            if (!empty($settings['flexdiscount_user_discounts']['value'])) {
                $output['bottom'] = shopFlexdiscountPluginHelper::getUserDiscounts(!empty($settings['flexdiscount_user_discounts']['type']) ? $settings['flexdiscount_user_discounts']['type'] : 0);
            }

            return $output;
        }
    }

    public function frontendProduct(&$product)
    {
        if (shopDiscounts::isEnabled('flexdiscount')) {
            $app = new shopFlexdiscountApp();
            $output = array('menu' => '', 'block_aux' => '', 'block' => '', 'cart' => '');
            // Получаем настройки
            $settings = $app::get('settings');
            // Вывод цены со скидкой
            if (!empty($settings['enable_price_output']['value'])) {
                $price_output_place = isset($settings['price_output_place']) && isset($output[$settings['price_output_place']]) ? $settings['price_output_place'] : 'cart';
                $output[$price_output_place] .= shopFlexdiscountPluginHelper::price($product, 0, !empty($settings['enable_price_output']['type']) ? $settings['enable_price_output']['type'] : 0);
            }
            // Вывод действующих скидок 
            if (!empty($settings['flexdiscount_product_discounts']['value'])) {
                $pd_output_place = isset($settings['pd_output_place']) && isset($output[$settings['pd_output_place']]) ? $settings['pd_output_place'] : 'cart';
                $output[$pd_output_place] .= shopFlexdiscountPluginHelper::getProductDiscounts($product, !empty($settings['flexdiscount_product_discounts']['type']) ? $settings['flexdiscount_product_discounts']['type'] : '');
            }
            // Вывод доступных скидок
            if (!empty($settings['flexdiscount_avail_discounts']['value'])) {
                $ad_output_place = isset($settings['ad_output_place']) && isset($output[$settings['ad_output_place']]) ? $settings['ad_output_place'] : 'block';
                $output[$ad_output_place] .= shopFlexdiscountPluginHelper::getAvailableDiscounts($product, !empty($settings['flexdiscount_avail_discounts']['type']) ? $settings['flexdiscount_avail_discounts']['type'] : '');
            }
            // Вывод правил запрета
            if (!empty($settings['flexdiscount_deny_discounts']['value'])) {
                $deny_output_place = isset($settings['deny_output_place']) && isset($output[$settings['deny_output_place']]) ? $settings['deny_output_place'] : 'block';
                $output[$deny_output_place] .= shopFlexdiscountPluginHelper::getDenyRules($product, !empty($settings['flexdiscount_deny_discounts']['type']) ? $settings['flexdiscount_deny_discounts']['type'] : '');
            }

            // Если была изменена цена товара, тогда необходимо подкорректировать стоимость услуг.
            // Они должны рассчитываться от основных цен
            $product_workflow = $app::get('runtime.product_workflow');
            if (ifempty($product_workflow, $product['id'], 'clear_discount', 0) > 0 && !empty($product['skus'])) {
                $product_data = $product->getData();
                $view = $app::get('system')['wa']->getView();
                foreach ($product_data['skus'] as &$sku) {
                    $sku['price'] = ifempty($sku, 'old_price', $sku['frontend_price']);
                }
                unset($sku);
                // Рассчитываем корректную стоимость услуг
                list($services, $skus_services) = $app::getHelper()->getServiceVars($product_data);
                $view->assign('sku_services', $skus_services);
                $view->assign('services', $services);
            }

            return $output;
        }
    }

    public function frontendCart()
    {
        $html = '';
        if (shopDiscounts::isEnabled('flexdiscount')) {
            // Получаем настройки
            $settings = shopFlexdiscountApp::get('settings');
            // Вывод формы для ввода купонов 
            if (!empty($settings['enable_frontend_cart_hook'])) {
                $html .= shopFlexdiscountPluginHelper::getCouponForm();
            }
            // Вывод примененных скидок
            if (!empty($settings['flexdiscount_user_discounts']['value'])) {
                $html .= shopFlexdiscountPluginHelper::getUserDiscounts(!empty($settings['flexdiscount_user_discounts']['type']) ? $settings['flexdiscount_user_discounts']['type'] : 0);
            }
            // Вывод бонусов
            if (!empty($settings['flexdiscount_affiliate_bonus']['value'])) {
                $html .= shopFlexdiscountPluginHelper::getUserAffiliate(!empty($settings['flexdiscount_affiliate_bonus']['type']) ? $settings['flexdiscount_affiliate_bonus']['type'] : 0);
            }

            $view = shopFlexdiscountApp::get('system')['wa']->getView();
            // Подменяем данные на витрине магазина о скидке по купону
            $coupon_discounts = shopFlexdiscountPluginHelper::getActiveCoupons();
            $fl_coupon_discount = 0;
            foreach ($coupon_discounts as $cd) {
                $fl_coupon_discount += $cd['coupon_discount'];
            }
            $coupon_discount = $view->getVars('coupon_discount');
            $coupon_discount = $coupon_discount ? ((float) $coupon_discount + $fl_coupon_discount) : ($fl_coupon_discount ? $fl_coupon_discount : null);
            if ($coupon_discount !== null) {
                $view->assign('coupon_discount', $coupon_discount);
            }

            // Подменяем данные на витрине магазина о начисленных бонусах
            if (shopAffiliate::isEnabled()) {
                $workflow = shopFlexdiscountApp::getOrder()->getOrderCalculateDiscount();
                $affiliate_bonus = $view->getVars('add_affiliate_bonus');
                $affiliate_bonus = $affiliate_bonus ? ((float) $affiliate_bonus + $workflow['affiliate']) : $workflow['affiliate'];
                $view->assign('add_affiliate_bonus', (!$affiliate_bonus ? '<i class="icon16-flexdiscount loading"></i>' : $affiliate_bonus) . '<span class="fl-affiliate-holder' . (!$affiliate_bonus ? ' fl-hide-block' : '') . '" style="display:none"></span>');
            }
        }
        return $html;
    }

    public function frontendCheckout()
    {
        if (shopDiscounts::isEnabled('flexdiscount')) {
            /* Бесплатная доставка */
            $workflow = shopFlexdiscountApp::getOrder()->getOrderCalculateDiscount();
            if ($workflow['active_rules']) {
                $view = shopFlexdiscountApp::get('system')['wa']->getView();
                $vars = $view->getVars();
                if (isset($vars['shipping']) && isset($vars['total'])) {
                    $shipping_price = $vars['shipping'];
                    foreach ($workflow['active_rules'] as $active_rule) {
                        if (!empty($active_rule['free_shipping'])) {
                            $shipping_price -= $active_rule['free_shipping'];
                        }
                    }
                    if ($shipping_price <= 0) {
                        $view->assign('total', $vars['total'] - $vars['shipping']);
                        $view->assign('shipping', 0);
                    }
                }
            }
        }
    }

    public function frontendProducts(&$params)
    {
        if (shopDiscounts::isEnabled('flexdiscount')) {

            $app = new shopFlexdiscountApp();
            $helper = $app::getHelper();
            $order_class = $app::getOrder();

            // Параметры страницы
            $param = waRequest::param();

            // Получаем настройки
            $settings = $app::get('settings');
            $is_importexport = $app::get('env')['is_importexport'];
            $is_product_page = !empty($params['products']) && count($params['products']) === 1 && reset($params['products']) instanceof shopProduct;
            $is_calculate_active_sku_catalog = $app::get('core')['calculate-active-sku-catalog'] && !$is_product_page;
            if (isset($param['flexdiscount-calculate-active-sku-catalog'])) {
                $is_calculate_active_sku_catalog = $param['flexdiscount-calculate-active-sku-catalog'];
            }

            // Профилируем
            if (self::$profile) {
                $hook_before = self::$profile->log('frontend_products', 'Before validating plugins');
                self::$profile->stop($hook_before);
            }

            // Правила скидок
            $discount_groups = $app::get('core')['discounts'];

            // Игнорировать action
            $action_ignore = (!empty($param['flexdiscount-ignore']) && !empty($param['action']) && $param['action'] == $param['flexdiscount-ignore']) || (!empty($param['flexdiscount_skip_frontend_products'])) || (!empty($param['igaponov_skip_frontend_products']));

            if (empty($settings['frontend_prices']) || $action_ignore || !$discount_groups || !$helper->validatePluginCalls()) {
                return;
            }

            if (!$is_importexport) {
                // Запоминаем товары, с которыми предстоит работать.
                // Это делается на случай, если у нас будет использоваться фильтрация по характеристикам.
                // Вместо того, чтобы делать десятки запросов к БД по каждому товару, мы за один раз получим хар-ки для всех товаров
                if (!empty($params['products'])) {
                    $app->set('runtime.shop/products', $helper->prepareShopProducts($params['products']));
                }
                if (!empty($params['skus'])) {
                    $product_skus = array();
                    foreach ($params['skus'] as $s) {
                        if (isset($params['products'][$s['product_id']]) && (!$is_calculate_active_sku_catalog || $params['products'][$s['product_id']]['sku_id'] == $s['id'])) {
                            $product_skus[$s['id']] = $s['product_id'];
                        }
                    }
                    if ($product_skus) {
                        $app->set('runtime.shop/product_sku_ids', $helper->prepareShopProductSkuIds($product_skus));
                    }
                }
            }

            // Профилируем
            if (self::$profile) {
                $hook = self::$profile->log('frontend_products', 'After validating plugins');
                self::$profile->stop($hook);
            }

            static $order = null;
            static $in_process = 0;

            // Если необходимо пропустить получение данных из корзины, получаем сразу заказ, чтобы приступить к обработке товара.
            // Актуально для плагинов экспорта
            $skip_shop_cart = !$helper->validatePluginCalls(true);
            if ($skip_shop_cart) {
                $order = $order_class->getOrder(true);
            }

            // Чтобы вычислить цену со скидкой для товара, необходимо знать содержимое заказа.
            // Но, когда мы пытаемся получить содержимое заказа,  при выполнении:
            // $shopCart = new shopCart(); $items = $shopCart->items(false);
            // Происходит вызов frontend_products. Это загонит нас в рекурсию, если не делать проверки.
            if (!$order && !$in_process && !$skip_shop_cart) {
                $in_process = 1;
                $order = $order_class->getOrder();
            } elseif ($order) {
                // Если есть массив skus, берем его первый элемент.
                // Он понадобится, чтобы определить, какой набор товаров перед нами.
                // Если у элемента будет обнаружена переменная item_id, значит обрабатываются товары из корзины
                // и для них не нужно просчитывать потенциальную скидку
                $first_sku = !empty($params['skus']) ? reset($params['skus']) : array();

                // Текущая валюта
                $current_cur = $app::get('system')['current_currency'];
                // Основная валюта
                $primary_cur = $app::get('system')['primary_currency'];
                // Рассчитываем потенциальную скидку только, если в обработке не товары из корзины и
                // не происходит добавление или удаление из корзины
                if (
                    !isset($first_sku['item_id']) &&
                    (
                        (isset($param['module'])
                            && $param['module'] !== 'frontendCart'
                            && $param['module'] !== 'frontendOrderCart'
                            && $param['module'] !== 'frontendOrder'
                        )
                        || !isset($param['module'])
                        || $param['action'] == 'productdialog' /* OMG. Всплывающее окно одностраничной корзины */
                    )) {
                    // Не обрабатываем товары из корзины
                    if (isset($params['products']) &&
                        isset($params['skus']) &&
                        !empty($order['order']['products']) &&
                        !array_diff(array_keys($params['products']), $order['order']['products']) &&
                        !array_diff(array_keys($params['skus']), $order['order']['skus']) &&
                        empty($param['force_flexdiscount_frontend_products']) &&
                        (isset($param['action']) && in_array($param['action'], array('cart', 'checkout', 'order')))
                    ) {
                        return;
                    }

                    waRequest::setParam('flexdiscount_is_frontend_products', 1);

                    $functions = $app::getFunction();

                    // Профилируем
                    if (self::$profile) {
                        $hook_calc = self::$profile->log('frontend_products', 'Calculating');
                    }
                    if (isset($params['products'])) {
                        foreach ($params['products'] as &$p) {
                            $product = ($p instanceof shopProduct) ? $p->getData() : $p;
                            if (empty($product['sku_id'])) {
                                continue;
                            }
                            $product['type'] = 'product';

                            // Если явно указан артикул, данные которого необходимо получить
                            if ($get_sku_id = waRequest::get('sku')) {
                                $sku = isset($product['skus'][$get_sku_id]) ? $product['skus'][$get_sku_id] : (isset($params['skus'][$get_sku_id]) ? $params['skus'][$get_sku_id] : $product);
                                $product = array_merge($product, array(
                                        // Очень сомнительный момент присвоения цене основной валюты
                                        'price' => isset($sku['primary_price']) ? $sku['primary_price'] : $functions->shop_currency($sku['price'], $product['currency'], $primary_cur, false),
                                        'frontend_price' => isset($sku['primary_price']) ? $functions->shop_currency($sku['primary_price'], $primary_cur, $current_cur, false) : $functions->shop_currency($sku['price'], $product['currency'], $current_cur, false),
                                        'currency' => $primary_cur,
                                        'compare_price' => isset($sku['compare_price']) ? $sku['compare_price'] : 0,
                                        'frontend_compare_price' => isset($sku['compare_price']) ? $functions->shop_currency($sku['compare_price'], $product['currency'], $current_cur, false) : 0,
                                        'primary_price' => isset($sku['primary_price']) ? $sku['primary_price'] : $functions->shop_currency($sku['price'], $product['currency'], $primary_cur, false),
                                        'purchase_price' => isset($sku['purchase_price']) ? $sku['purchase_price'] : 0
                                    )
                                );
                            }

                            $processed_product = $helper->calculateProductDiscount($p, $product);
                            if (!$processed_product) {
                                continue;
                            }
                            $p = $processed_product;
                            unset($p);

                            $helper->clearCache();
                        }
                    }
                    if (isset($params['skus'])) {

                        // Если отсутствует массив товаров $params['products'], получаем информацию о товарах самостоятельно
                        $products = $app->get('runtime.frontend_products/products', []);
                        $find_products = [];
                        foreach ($params['skus'] as $s) {
                            if (!isset($params['products'][$s['product_id']]) && !isset($products[$s['product_id']])) {
                                $find_products[$s['product_id']] = $s['product_id'];
                            }
                        }
                        if ($find_products) {
                            $pm = new shopProductModel();
                            $products += $pm->getByField('id', $find_products, 'id');
                        }
                        $app->set('runtime.frontend_products/products', $products);

                        foreach ($params['skus'] as &$s) {
                            // Выполняем обработку только, если имеется информация о товаре
                            $sku = $s;
                            $product = isset($params['products'][$sku['product_id']]) ? (is_object($params['products'][$sku['product_id']]) ? $params['products'][$sku['product_id']]->getData() : $params['products'][$sku['product_id']]) : (isset($products[$sku['product_id']]) ? $products[$sku['product_id']] : null);
                            if ($product && (!$is_calculate_active_sku_catalog || $params['products'][$s['product_id']]['sku_id'] == $s['id'])) {
                                $sku['sku_id'] = $sku['id'];
                                // Для корректного вычисления скидки добавляем недостающие параметры товара
                                $sku['product'] = $product;
                                $sku['currency'] = $product['currency'];
                                $sku['type'] = 'product';

                                $processed_product = $helper->calculateProductDiscount($s, $sku, 'sku', [
                                    'product' => $product
                                ]);
                                if (!$processed_product) {
                                    continue;
                                }
                                $s = $processed_product;
                                unset($s);

                                $helper->clearCache();
                            }
                        }
                    }
                    if (self::$profile) {
                        if (!empty($hook_calc)) {
                            self::$profile->stop($hook_calc);
                        }
                    }
                    waRequest::setParam('flexdiscount_is_frontend_products', 0);

                    if ($is_importexport) {
                        $helper->clearCache(true);
                    }
                }
            }
        }
    }

    public function orderCalculateDiscount($params)
    {
        static $workflow = null;
        static $applied = null;
        static $total_with_discount = null;

        $return = array(
            'discount' => 0,
            'description' => ''
        );

        if (shopDiscounts::isEnabled('flexdiscount')) {

            $app = new shopFlexdiscountApp();
            $helper = $app::getHelper();
            $functions = $app::getFunction();

            // Профилируем
            if (self::$profile) {
                $profile_hook = self::$profile->log('order_calculate_discount');
            }

            // Итоговая цена с учетом всех скидок. Работает только на странице оформления
            // Игорь, если у тебя есть сомнения насчет данной конструкции, лучше ее не удалять.
            if (waRequest::param('flexdiscount_shop_cart_total_magic')) {
                waRequest::setParam('flexdiscount_shop_cart_total_magic', 0);
                // SS 7 fix
                if (is_callable(['shopCart', 'clearSessionData'])) {
                    (new shopCart())->clearSessionData();
                } else {
                    $app::get('system')['wa']->getStorage()->remove('shop/cart');
                }
                $total_with_discount = (new shopCart())->total();
            }
            waRequest::setParam('flexdiscount_shop_cart_total', $total_with_discount);

            $is_calculate = $workflow === null || waRequest::param('flexdiscount_force_calculate') || waRequest::param('igaponov_force_calculate');
            if (!$is_calculate && !$app::get('env')['is_frontend']) {
                $cache_in_backend = (new \waAppSettingsModel())->get(array('shop', 'flexdiscount'), shopFlexdiscountProfile::SETTINGS['CACHE_BACKEND_CALCULATIONS'], shopFlexdiscountProfile::DEFAULT_SETTINGS['CACHE_BACKEND_CALCULATIONS']);
                $is_calculate = !$cache_in_backend;
            }
            if ($is_calculate) {
                $workflow = array(
                    "discount" => 0,
                    "affiliate" => 0,
                    "rule_products" => array(),
                    "products" => array(),
                    "active_rules" => array()
                );
                // Если скидка включена
                if (!empty($params['order']['items'])) {

                    // Профилируем
                    if (self::$profile) {
                        $profile_hook_point_calc = self::$profile->log('order_calculate_discount', 'Calculating');
                    }

                    // Если скидки проверяются из бэкэнда, добавляем товары в набор для дальнейшей работы с ними.
                    // или работает плагин "Купить в 1 клик" (quickorder)
                    if (!$app::get('env')['is_frontend'] || waRequest::param('plugin', '') == 'quickorder') {
                        $app->set('runtime.shop/products', $helper->prepareShopProducts($params['order']['items']));
                    }

                    // Получаем все скидки по группам
                    $discount_groups = $app::get('core')['discounts'];
                    // Если нет активных правил, прерываем обработку
                    if ($discount_groups) {
                        // Вычисляем размер скидки и бонусов
                        $workflow = (new shopFlexdiscountCore())->calculate_discount($params, $discount_groups);
                    }
                }

                // Сохраняем результат обработки
                $app->set('core.workflow', $workflow);

                if (waRequest::param('flexdiscount_force_calculate_stop')) {
                    waRequest::setParam('flexdiscount_force_calculate', 0);
                    waRequest::setParam('flexdiscount_force_calculate_stop', 0);
                }
            }

            // Если происходит оформление заказа
            if ($workflow['active_rules'] && $params['apply'] && $applied === null) {
                $coupon_info = $helper->getActiveCouponsInfo(ifset($params, 'order', 'id', 0));
                // Если был введен купон
                if ($coupon_info) {
                    // Перебираем правила скидок, которые участвовали в работе
                    $cm = new shopFlexdiscountCouponPluginModel();
                    foreach ($workflow['active_rules'] as $rule) {
                        // Если был использован купон, увеличиваем значение его использования
                        if ($rule['coupon_id']) {
                            // Если один купон использовался в нескольких скидках, то увеличиваем кол-во его использований лишь на один раз
                            if (!isset(self::$coupons[$rule['coupon_id']])) {
                                if (empty($coupon_info[$rule['coupon_id']]['reduced'])) {
                                    $cm->useOne($rule['coupon_id'], $rule['clean_coupon']);
                                }
                                self::$coupons[$rule['coupon_id']] = array(
                                    'discount' => $rule['discount'],
                                    'affiliate' => $rule['affiliate'],
                                    'code' => $coupon_info[$rule['coupon_id']]['code'],
                                    'reduced' => isset($params['order']['id']) && empty($coupon_info[$rule['coupon_id']]['reduced']) ? 0 : 1
                                );
                            } else {
                                // Суммируем значения скидок и бонусов по купону
                                self::$coupons[$rule['coupon_id']]['discount'] += $rule['discount'];
                                self::$coupons[$rule['coupon_id']]['affiliate'] += $rule['affiliate'];
                            }
                        }
                    }
                    $applied = 1;
                }
            }

            $return['discount'] = $workflow['discount'];
            $return['description'] = '';

            // Если были правила, по которым начислились скидки или бонусы
            if ($workflow['active_rules']) {
                $settings = shopFlexdiscountApp::get('settings');
                $discount_distribution = ifempty($settings, 'distribute_type', '');
                // Распределение скидок по товарам
                if ($workflow['products'] && !empty($params['order']['items']) && $discount_distribution !== 'order') {
                    $products_with_discount = array();

                    foreach ($params['order']['items'] as $item_id => $item) {
                        $cart_item_id = ifset($item, 'cart_item_id', $item_id);
                        if (!empty($workflow['products'][$cart_item_id])) {
                            $workflow_item = $workflow['products'][$cart_item_id];
                            $products_with_discount[$item_id] = array(
                                "description" => "<table class='zebra' style='margin: 10px 0'><tr><th>" . _wp("Discount name") . "</th><th>" . _wp("Discount") . "</th><th>" . _wp("Affiliate") . "</th></tr>",
                                "discount" => $workflow_item['total_discount']
                            );
                            if (!empty($workflow_item['rules'])) {
                                foreach ($workflow_item['rules'] as $r_id => $r) {
                                    $products_with_discount[$item_id]['description'] .= "<tr>";
                                    $products_with_discount[$item_id]['description'] .= "<td>" . str_replace('%', '&percnt;', waString::escapeAll($workflow['active_rules'][$r_id]['name'])) . "</td>";
                                    $products_with_discount[$item_id]['description'] .= "<td>" . $functions->shop_currency_html($r['discount'], $params['order']['currency'], $params['order']['currency']) . "</td>";
                                    $products_with_discount[$item_id]['description'] .= "<td>" . $r['affiliate'] . "</td>";
                                    $products_with_discount[$item_id]['description'] .= "</tr>";
                                }
                                $products_with_discount[$item_id]['description'] .= "</table>";
                                $products_with_discount[$item_id]['description'] .= _wp("Total product discount");
                            }
                            $return['discount'] -= $workflow_item['total_discount'];
                        }
                    }
                    $return['items'] = $products_with_discount;
                }

                $return['description'] .= "<div style='margin-bottom: 5px; margin-top: 15px'>" . _wp("Flexdiscount") . '.' . _wp("Total order discount") . '</div>';
                $return['description'] .= "<table class='zebra'>"
                    . "<tr><th>" . _wp("Discount name") . "</th><th>" . _wp("Discount") . "</th><th>" . _wp("Affiliate") . "</th></tr>";
                // Список всех скидок
                foreach ($workflow['active_rules'] as $active_rule) {
                    // Бесплатная доставка
                    if (!empty($active_rule['free_shipping'])) {
                        $params['order']['shipping'] = 0;
                        $params['order']['params']['coupon_free_shipping'] = 1;
                    }

                    if (!empty($active_rule['name'])) {
                        $return['description'] .= "<tr>";
                        $return['description'] .= "<td>" . str_replace('%', '&percnt;', waString::escapeAll($active_rule['name'])) . "</td>";
                        $return['description'] .= "<td>" . $functions->shop_currency_html(ifempty($active_rule, 'free_shipping', $active_rule['discount']), $params['order']['currency'], $params['order']['currency']) . "</td>";
                        $return['description'] .= "<td>" . $active_rule['affiliate'] . "</td>";
                        $return['description'] .= "</tr>";
                    }
                }
                $return['description'] .= "</table><br>";
                $return['description'] .= _wp("Total discount");
            }

            // Профилируем
            if (self::$profile) {
                if (!empty($profile_hook)) {
                    self::$profile->stop($profile_hook);
                }
                if (!empty($profile_hook_point_calc)) {
                    self::$profile->stop($profile_hook_point_calc);
                }
            }

            $helper->clearCache();
        }

        $return['discount'] = $return['discount'] < 0 ? 0 : $return['discount'];

        return $return;
    }

    public function orderActionCreate($data)
    {
        if (shopDiscounts::isEnabled('flexdiscount')) {

            $workflow = shopFlexdiscountApp::getOrder()->getOrderCalculateDiscount($data['order_id']);

            if (!empty($workflow['active_rules'])) {
                $storage = shopFlexdiscountApp::get('system')['wa']->getStorage();
                $needles = [
                    '"type":"cookie"' => 'flexdiscount_cookie',
                    '"type":"session"' => 'flexdiscount_session',
                    '"type":"is_affiliate_used"' => 'flexdiscount_session',
                    '"type":"get"' => 'flexdiscount_get',
                    '"type":"post"' => 'flexdiscount_post',
                    '"type":"server"' => 'flexdiscount_server',
                ];
                $params = $env_vars = [];
                // Отбираем, какие переменные окружения необходимо сохранять.
                foreach ($workflow['active_rules'] as $rule) {
                    if ($needles) {
                        foreach ($needles as $needle => $save_param) {
                            if (strpos($rule["full_info"]["conditions"], $needle) !== false) {
                                $params[$save_param] = 1;
                                unset($needles[$needle]);
                            }
                        }
                    }
                }
                if (isset($params['flexdiscount_cookie'])) {
                    $env_vars['flexdiscount_cookie'] = json_encode(waRequest::cookie());
                }
                if (isset($params['flexdiscount_session'])) {
                    $env_vars['flexdiscount_session'] = json_encode($storage->getAll());
                }
                if (isset($params['flexdiscount_get'])) {
                    $env_vars['flexdiscount_get'] = json_encode($storage->get('flexdiscount_get'));
                }
                if (isset($params['flexdiscount_post'])) {
                    $env_vars['flexdiscount_post'] = json_encode($storage->get('flexdiscount_post'));
                }
                if (isset($params['flexdiscount_server'])) {
                    $env_vars['flexdiscount_server'] = json_encode($storage->get('flexdiscount_server'));
                }
                $env_vars = array_filter($env_vars, function ($value) {
                    return $value !== 'null';
                });
                if ($env_vars) {
                    // Сохраняем куки и сессию на момент оформления заказа
                    (new shopFlexdiscountOrderParamsPluginModel())->set($data['order_id'], $env_vars);
                }
            }

            // Если был использован купон и заказ оформлен, то запоминаем заказ и присваиваем его купону
            if (!empty(self::$coupons) && !empty($data['order_id'])) {
                $save_coupons = array();
                foreach (self::$coupons as $c_id => $c) {
                    $save_coupons[] = array(
                        "coupon_id" => (int) $c_id,
                        "order_id" => (int) $data['order_id'],
                        "discount" => shopFlexdiscountApp::getFunction()->floatVal($c['discount']),
                        "affiliate" => (shopAffiliate::isEnabled() ? shopFlexdiscountApp::getFunction()->floatVal($c['affiliate']) : 0),
                        "code" => $c['code'],
                        "datetime" => date("Y-m-d H:i:s"),
                    );
                }
                $sfcom = new shopFlexdiscountCouponOrderPluginModel();
                $sfcom->multipleInsert($save_coupons);
            }

            // Если были начислены бонусы за заказ, запоминаем их
            $sfam = new shopFlexdiscountAffiliatePluginModel();
            $sfam->saveBonuses($data, $workflow['affiliate']);
        }
    }

    public function orderActionEdit($data)
    {
        // Если был использован купон и заказ оформлен, то запоминаем заказ и присваиваем его купону
        if (!empty(self::$coupons) && !empty($data['order_id'])) {
            $sfcom = new shopFlexdiscountCouponOrderPluginModel();

            // Получаем существующие коды купонов
            $scm = new shopFlexdiscountCouponPluginModel();
            $sql = "SELECT c.id, co.code FROM {$sfcom->getTableName()} co LEFT JOIN {$scm->getTableName()} c ON co.code = c.code WHERE co.order_id = '" . (int) $data['order_id'] . "'";
            $order_coupons = $sfcom->query($sql)->fetchAll('code');

            foreach (self::$coupons as $c_id => $c) {
                // Если текущий купон отработал, то удаляем его из списка на изменение
                if (isset($order_coupons[$c['code']])) {
                    unset($order_coupons[$c['code']]);
                }
                // Если купон еще не использовался, сохраняем его скидки и бонусы
                $update = array(
                    "discount" => shopFlexdiscountApp::getFunction()->floatVal($c['discount']),
                    "affiliate" => (shopAffiliate::isEnabled() ? shopFlexdiscountApp::getFunction()->floatVal($c['affiliate']) : 0)
                );
                if (empty($c['reduced'])) {
                    $update['reduced'] = 1;
                }
                $sfcom->updateByField(array("coupon_id" => $c_id, "order_id" => $data['order_id']), $update);
            }
            // Если у нас остались купоны, которые не участвовали в заказе, то обнуляем по ним данные
            if ($order_coupons) {
                foreach ($order_coupons as $coup) {
                    $update = array(
                        "reduced" => 0,
                        "discount" => 0,
                        "affiliate" => 0,
                    );
                    if (empty($coup['id'])) {
                        $update['coupon_id'] = 0;
                    }
                    $sfcom->updateByField(array("code" => $coup['code'], "order_id" => $data['order_id']), $update);
                }
            }
        }
        $workflow = shopFlexdiscountApp::getOrder()->getOrderCalculateDiscount($data['order_id']);
        // Списываем старые бонусы, чтобы обновить данные
        $this->orderActionCancelAffiliate($data);

        // Если были начислены бонусы за заказ, запоминаем их
        $sfam = new shopFlexdiscountAffiliatePluginModel();
        $sfam->saveBonuses($data, $workflow['affiliate']);

        // Если следует один из статусов, который предполагает начисление бонусов
        if (in_array($data['after_state_id'], array('paid', 'completed', 'restore'))) {
            $this->orderActionApplyAffiliate($data);
        }
    }

    public function orderActionApplyAffiliate($data)
    {
        if (shopDiscounts::isEnabled('flexdiscount') && shopAffiliate::isEnabled()) {
            $settings = shopFlexdiscountApp::get('settings');
            $log_text = !empty($settings['flexdiscount_affiliate_bonus']['text']) ? $settings['flexdiscount_affiliate_bonus']['text'] : _wp("Flexdiscount bonus");
            (new shopFlexdiscountAffiliatePluginModel())->updateBonuses($data['order_id'], 'done', $log_text, $data['action_id']);
        }
    }

    public function orderActionCancelAffiliate($data)
    {
        if (shopDiscounts::isEnabled('flexdiscount') && shopAffiliate::isEnabled()) {
            $settings = shopFlexdiscountApp::get('settings');
            $log_text = !empty($settings['flexdiscount_affiliate_bonus']['text']) ? _wp('Cancel') . ' "' . $settings['flexdiscount_affiliate_bonus']['text'] . '"' : _wp("Cancel flexdiscount bonus");
            (new shopFlexdiscountAffiliatePluginModel())->updateBonuses($data['order_id'], 'cancel', $log_text);
        }
    }

    public function productPreSave($params)
    {
        if (shopDiscounts::isEnabled('flexdiscount')) {
            // Сохраняем минмиальные цены для артикулов, скидку, бонусы
            $minimal_price_for_all = waRequest::post('minimal_price_for_all');
            $discount_for_all = waRequest::post('discount_for_all');
            $affiliate_for_all = waRequest::post('affiliate_for_all');
            if (!empty($params['data']['skus'])) {
                foreach ($params['data']['skus'] as &$sku) {
                    if (!empty($minimal_price_for_all) && !isset($sku['flexdiscount_minimal_discount_price'])) {
                        // Если артикул редактируется, тогда не меняем его цены
                        $sku['flexdiscount_minimal_discount_price'] = $params['data']['flexdiscount_minimal_discount_price'];
                        $sku['flexdiscount_minimal_discount_currency'] = $params['data']['flexdiscount_minimal_discount_currency'];
                    }
                    if (!empty($discount_for_all) && !isset($sku['flexdiscount_item_discount'])) {
                        // Если артикул редактируется, тогда не меняем его цены
                        $sku['flexdiscount_item_discount'] = $params['data']['flexdiscount_item_discount'];
                        $sku['flexdiscount_discount_currency'] = $params['data']['flexdiscount_discount_currency'];
                    } elseif (empty($discount_for_all) && isset($sku['flexdiscount_item_discount']) && $sku['flexdiscount_item_discount'] == '') {
                        $sku['flexdiscount_item_discount'] = null;
                    }
                    if (!empty($affiliate_for_all) && !isset($sku['flexdiscount_item_affiliate'])) {
                        // Если артикул редактируется, тогда не меняем его цены
                        $sku['flexdiscount_item_affiliate'] = $params['data']['flexdiscount_item_affiliate'];
                        $sku['flexdiscount_affiliate_currency'] = $params['data']['flexdiscount_affiliate_currency'];
                    } elseif (empty($affiliate_for_all) && isset($sku['flexdiscount_item_affiliate']) && $sku['flexdiscount_item_affiliate'] == '') {
                        $sku['flexdiscount_item_affiliate'] = null;
                    }
                }
                $params['instance']['skus'] = $params['data']['skus'];
            }
        }
    }

    public function promoRuleTypes()
    {
        if (shopDiscounts::isEnabled('flexdiscount')) {
            return (new shopFlexdiscountMarketing($this))->getPromoRuleTypes();
        }
    }

    public function backendMarketingSidebar()
    {
        if (shopDiscounts::isEnabled('flexdiscount')) {
            return (new shopFlexdiscountMarketing($this))->getBackendMarketingSidebar();
        }
    }

    public function backendMarketingPromo()
    {
        if (shopDiscounts::isEnabled('flexdiscount')) {
            return (new shopFlexdiscountMarketing($this))->getBackendMarketingPromo();
        }
    }

    public function promoRuleEditor(&$params)
    {
        if (shopDiscounts::isEnabled('flexdiscount')) {
            (new shopFlexdiscountMarketing($this))->getPromoRuleEditor($params);
        }
    }

    public function promoRuleValidate(&$params)
    {
        if (shopDiscounts::isEnabled('flexdiscount')) {
            (new shopFlexdiscountMarketing($this))->getPromoRuleValidate($params);
        }
    }

    public function promoWorkflowRun(&$params)
    {
        if (shopDiscounts::isEnabled('flexdiscount')) {
            return (new shopFlexdiscountMarketing($this))->getPromoWorkflowRun($params);
        }
    }

    public function routing($params = array())
    {
        if (shopDiscounts::isEnabled('flexdiscount')) {
            static $stop = 0;

            // Получаем содержимое заказа, чтобы в методе frontendProducts() не было проблем
            if (!$stop && shopFlexdiscountApp::get('env')['is_frontend']) {
                if (shopFlexdiscountApp::getHelper()->validatePluginCalls()) {
                    $stop = 1;
                    (new shopFlexdiscountHelper())->checkRulesForTotalPriceWithDiscount();
                    $event_params = ["products" => [], "skus" => []];
                    $this->frontendProducts($event_params);
                }
            }
        }
        return parent::routing($params);
    }

    public function rightsConfig(waRightConfig $config)
    {
        $config->addItem('flexdiscout_header', _wp('Flexdiscount'), 'header');
        $config->addItem('flexdiscount_rules', _wp('Access to discount rules'));
        $config->addItem('flexdiscount_settings', _wp('Access to discount settings'));
    }

    /**
     * Check if plugin is active
     *
     * @return bool
     */
    public static function isEnabled()
    {
        $plugins = shopFlexdiscountApp::get('system')['config']->getPlugins();
        return shopDiscounts::isEnabled('flexdiscount') && isset($plugins['flexdiscount']);
    }

    private function updateContactFieldsFromParams($params)
    {
        $update = array();
        // Обновляем контактные поля
        if (!empty($params['auth']['fields'])) {
            $update = $params['auth']['fields'];
        }
        // Обновляем поля адреса
        if (!empty($params['region']['selected_values'])) {
            $address = [
                'address.shipping' => [
                    'country' => $params['region']['selected_values']['country_id'],
                    'region' => $params['region']['selected_values']['region_id'],
                    'city' => $params['region']['selected_values']['city'],
                    'zip' => ifempty($params['region']['selected_values'], 'zip', '')
                ]
            ];
            $update += $address;
        }
        if ($update) {
            waRequest::setParam('flexdiscount_force_calculate', 1);
            waRequest::setParam('flexdiscount_force_calculate_stop', 1);
            shopFlexdiscountApp::getContact()->set($update);
        }
    }

}

<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

namespace Igaponov\flexdiscount;

class Order
{

    /**
     * Set order information
     *
     * @param array $order
     * @return array
     */
    public function updateOrderInfo($order)
    {
        unset($order['items'], $order['contact']);
        $app = new \shopFlexdiscountApp();

        $order_info = $app::get('runtime.order/info', []);
        if (!empty($order['id']) && (empty($order_info['id']) || (!empty($order_info['id']) && $order_info['id'] !== $order['id']))) {
            $order += (new \shopOrderModel())->select('create_datetime, total, discount, currency')->where('id = i:id', ['id' => $order['id']])->fetchAssoc();
            if (empty($order['params'])) {
                $order['params'] = (new \shopOrderParamsModel())->get($order['id']);
            }
            $order += (new \shopFlexdiscountOrderParamsPluginModel())->get($order['id']);
        }
        $app->set('order.currency', $order['currency']);
        $order['real_total'] = $order['total'];

        return $app->set('order.info', $order);
    }

    /**
     * Get order params
     *
     * @param int $order_id
     * @param bool $skip_shop_cart
     * @return array
     */
    public function updateOrder($order_id = 0, $skip_shop_cart = false)
    {
        // Если необходимо получить информацию и просчитать скидки для конкретного заказа
        if ($order_id) {
            $order_data = (new \shopOrderModel())->getOrder($order_id, true);
        } else {
            $shopCart = new \shopCart();
        }
        // Получаем содержимое заказа.
        // Для фронтенда - набор из корзины
        // Для готового заказа - массив товаров из БД
        // Для бекенда - пустой массив (скорее всего происходит выгрузка)

        $items = $order_id ? $this->prepareOrderItems($order_data['items'], $order_data['currency']) : (\shopFlexdiscountApp::get('env')['is_frontend'] && !$skip_shop_cart ? $shopCart->items(false) : []);
        $contact = $order_id ? new \waContact($order_data['contact_id']) : \shopFlexdiscountApp::get('system.wa')->getUser();
        $order = array(
            'order' => array(
                'currency' => $order_id ? $order_data['currency'] : \shopFlexdiscountApp::get('system.current_currency'),
                'items' => $items ? $items : array(),
                'ids' => array(),
                'contact' => $contact,
                'total' => $order_id ? $order_data['total'] : (!$skip_shop_cart ? $shopCart->total(false) : 0),
            ),
            'contact' => $contact,
            'apply' => $order_id ? 1 : 0
        );
        if ($order_id) {
            $order['order']['id'] = $order_id;
        }

        return $order;
    }

    /**
     * Add product to virtual order. Just pretend
     *
     * @param array $product
     * @param string $currency
     * @return array Order params
     */
    public function addToVirtualOrder($product, $currency = '')
    {
        $app = new \shopFlexdiscountApp();
        $params = $app::get('order.full');

        $prod = is_object($product) ? $product->getData() : $product;
        if (!isset($prod['product'])) {
            $prod['product'] = $prod;
        }
        $prod['quantity'] = isset($prod['quantity']) ? $prod['quantity'] : 1;

        $merge = false;
        $ignore_service = array();
        // Проверяем есть ли такой товар в корзине, если имеется, то увеличиваем количество
        foreach ($params['order']['items'] as $k => $item) {
            if ($item['sku_id'] == $prod['sku_id']) {
                $params['order']['items'][$k]['quantity'] += $prod['quantity'];
                $merge = true;
                $item_id = $k;
            }
            // Если в заказе имеется услуга и, мы ее же пытаемся добавить с новым товаром,
            // устанавливаем флаг, чтобы игнорировать повторное добавление услуги
            if ($item['type'] == 'service' && $prod['sku_id'] == $item['sku_id'] && isset($prod['services'][$item['service_variant_id']])) {
                $ignore_service[$item['service_variant_id']] = true;
            }
        }

        $prod['currency'] = $currency ? $currency : $params['order']['currency'];
        if (!$merge) {
            $params['order']['items'][] = $prod;
            end($params['order']['items']);
            $item_id = key($params['order']['items']);
            reset($params['order']['items']);
        }
        // Добавляем услуги к заказу
        if (!empty($prod['services'])) {
            foreach ($prod['services'] as $variant_id => $variant) {
                if (empty($ignore_service[$variant_id])) {
                    $params['order']['items'][] = array(
                        'type' => 'service',
                        'service_id' => $variant['service_id'],
                        'service_variant_id' => $variant_id,
                        'parent_id' => $item_id,
                        'price' => $variant['price'],
                        'currency' => $variant['currency']
                    );
                }
                // Изменяем общую цену заказа
                $params['order']['total'] += (float) $variant['price'] * $prod['quantity'];
            }
        }

        // Добавляем товары в общую выборку при динамическом обновлении информационных блоков
        if (\waRequest::isXMLHttpRequest()) {
            $app->set('runtime.shop/products', $app::getHelper()->prepareShopProducts($params['order']['items']));
        }

        // Изменяем общую цену заказа
        $product_price = $app::getFunction()->shop_currency((float) $prod['price'] * $prod['quantity'], $prod['currency'], $params['order']['currency'], false);
        $params['order']['total'] += \shopRounding::roundCurrency($product_price, $params['order']['currency']);

        return $params;
    }

    /**
     * Get the result of order_calculate_discount
     *
     * @param int $order_id
     * @param bool $update
     * @return array
     */
    public function getOrderCalculateDiscount($order_id = 0, $update = false)
    {
        $app = new \shopFlexdiscountApp();
        $workflow = $app::get('core.workflow', []);

        if (!empty($workflow) && !$update) {
            return $workflow;
        }

        // Правила скидок
        $discount_groups = $app::get('core.discounts');
        // Данные заказа
        $order_params = $app->set('order.full', $app::getOrder()->updateOrder($order_id));
        // Вычисляем размер скидки и бонусов
        $workflow = (new \shopFlexdiscountCore())->calculate_discount($order_params, $discount_groups);
        return $app->set('core.workflow', $workflow);
    }

    /**
     * Get order params from shop/checkout. Keep in touch with Quickorder plugin
     *
     * @return array
     */
    public function getCurrentOrderParams()
    {
        $app = new \shopFlexdiscountApp();
        $params = $app::get('system')['wa']->getStorage()->get('shop/checkout');
        if (!$params) {
            $params = [];
        }
        $shipping = $payment = null;
        $is_common_coupons_enabled = \shopDiscounts::isEnabled('coupons');
        $is_coupons_enabled = $is_common_coupons_enabled || ifempty(ref($app::get('settings')), 'enable_frontend_cart_hook', 0);

        /* SS8 */
        if (isset($params['order'])) {
            $params += $params['order'];

            $cache = new \waRuntimeCache('flexdiscount_checkout_params');
            if ($cache->isCached()) {
                $checkout_params = $cache->get();
                // Преобразуем метод доставки к нужному виду
                if (!empty($checkout_params['shipping']['variant_id'])) {
                    $shipping = $checkout_params['shipping']['variant_id'];
                }
                if (!empty($checkout_params['payment'])) {
                    $payment = $checkout_params['payment'];
                }
                $params = array_merge($params, $checkout_params);
            }

            // Преобразуем метод доставки к нужному виду
            if (!empty($params['shipping']['variant_id'])) {
                $shipping = $params['shipping']['variant_id'];
            }

            // Fix. In some cases coupon can be lost on one-step checkout page
            if ($is_common_coupons_enabled) {
                $coupon_data = \waRequest::post('coupon', [], \waRequest::TYPE_ARRAY);
                if (\waRequest::isXMLHttpRequest() && $coupon_data) {
                    $params['coupon_code'] = $coupon_data['code'];
                }
            }
        }

        // Creating order from admin page
        if (!$app::get('env')['is_frontend']) {
            $post = \waRequest::post();
            if (!empty($post['params'])) {
                $post_params = $post['params'];
                if (isset($post_params['shipping_id'])) {
                    $shipping = ifempty($post_params, 'shipping_id', null);
                }
                if (isset($post_params['payment_id'])) {
                    $payment = ifempty($post_params, 'payment_id', null);
                }
            } else {
                $payment = ifempty($post, 'payment_id', $payment);
                if (isset($post['shipping_id'])) {
                    $shipping = ifempty($post, 'shipping_id', null);
                }
            }
            if (!$shipping) {
                $params['shipping'] = [];
            }
            if (!$payment) {
                $params['payment'] = 0;
            }
        } else {
            $post = \waRequest::post();
            if (!$shipping && !empty($post['shipping']['variant_id'])) {
                $shipping = $post['shipping']['variant_id'];
            }
            if (!$payment && !empty($post['payment']['id'])) {
                $payment = $post['payment']['id'];
            }

            // Fix. In some cases coupon can be lost on multi-step checkout page
            if ($is_coupons_enabled && !$app::get('env')['is_onestep_checkout'] && isset($post['coupon_code'])) {
                $params['coupon_code'] = $post['coupon_code'];
            }
        }

        if ($shipping) {
            $parts = explode('.', $shipping);
            if (!isset($params['shipping'])) {
                $params['shipping'] = [];
            }
            $params['shipping']['id'] = $parts[0];
            if (isset($parts[1])) {
                $params['shipping']['rate_id'] = $parts[1];
            }
        }

        if ($payment) {
            $params['payment'] = $payment;
        }
        if (isset($params['payment']['id'])) {
            $params['payment'] = $params['payment']['id'];
        }

        $params['contact'] = $app::getContact()->get();

        // Учитываем плагин "Купить в 1 клик" (quickorder)
        if (\waRequest::param('plugin', '') == 'quickorder') {
            $quickorder_cart = new \shopQuickorderPluginCart(\waRequest::post('qformtype'));
            $params['quickorder_cart'] = $quickorder_cart;
            // Доставка
            $shipping = $quickorder_cart->getStorage()->getSessionData('shipping');
            if ($shipping) {
                $params['shipping'] = $shipping;
            }
            // Оплата
            $payment = $quickorder_cart->getData('payment_id');
            if ($payment) {
                $params['payment'] = $payment;
            }
            // Контакт
            $contact = $quickorder_cart->getContact();
            if ($contact) {
                $params['contact'] = $contact;
            }
            // Купон
            if ($is_coupons_enabled) {
                $coupon_code = $quickorder_cart->getData('coupon');
                $params['flexdiscount-coupon'] = $params['coupon_code'] = '';
                if ($coupon_code) {
                    $params['coupon_code'] = $coupon_code;
                    $params['flexdiscount-coupon'] = $coupon_code;
                }
            }
        }

        if (!$is_coupons_enabled) {
            $params['coupon_code'] = '';
        }

        return $params;
    }

    /**
     * Get sended coupon codes
     *
     * @param int $order_id
     * @return array
     */
    public function getSubmittedCouponCodes($order_id = 0)
    {
        // Если заказ еще не создан
        if (!$order_id) {
            $app = new \shopFlexdiscountApp();
            $data = $this->getCurrentOrderParams();
            // Получаем купон через обычную форму для ввода купонов
            $coupon_code = ifempty($data, 'coupon_code', '');
            // Получаем купон через форму Гибких скидок
            $coupon_fl_code = ifset($data, 'flexdiscount-coupon', $app::get('system')['wa']->getStorage()->get("flexdiscount-coupon"));
            $codes = array($coupon_code, $coupon_fl_code);
            if (!$app::get('env')['is_frontend']) {
                $coupon_code = \waRequest::post('flexdiscount-coupon-code1', '');
                $coupon_fl_code = \waRequest::post('flexdiscount-coupon-code2', '');
                $codes = array($coupon_code, $coupon_fl_code);
            }
        } else {
            // Если заказ обрабатывается через бэкенд
            $com = new \shopFlexdiscountCouponOrderPluginModel();
            $codes = $com->select("code")->where("order_id = i:id", ['id' => $order_id])->fetchAll(null, true);
        }

        return $codes;
    }

    /**
     * Get current order
     *
     * @param bool $skip_shop_cart - skip calculating total and items from shop cart to prevent redirecting to frontend_products
     * @return array
     */
    public function getOrder($skip_shop_cart = false)
    {
        $app = new \shopFlexdiscountApp();
        $order = $app->set('order.full', $this->updateOrder(0, $skip_shop_cart), true);
        // Формируем массив из товаров и артикулов. Он требуется, чтобы понимать с каким набором товаром мы работаем.
        // Если нам попались товары, лежащие в корзине, их не трогаем
        if (!empty($order['order']['items'])) {
            $order['order']['products'] = $order['order']['skus'] = array();
            foreach ($order['order']['items'] as $it) {
                $it['product_id'] = ifset($it, 'product_id', ifset($it, 'product', 'id', ifset($it, 'id', 0)));
                $order['order']['products'][$it['product_id']] = $it['product_id'];
                $order['order']['skus'][$it['sku_id']] = $it['sku_id'];
            }
        }
        return $order;
    }

    /**
     * Prepare order items
     *
     * @param array $items
     * @param string $currency
     * @return array
     */
    private function prepareOrderItems($items, $currency)
    {
        $order_data = [];
        $k = 0;

        foreach ($items as $item) {
            $item['type'] = 'product';
            $item['currency'] = $currency;
            $item['product_id'] = $item['item']['product_id'];
            $item['quantity'] = $item['item']['quantity'];
            if (!isset($item['purchase_price'])) {
                $item['purchase_price'] = $item['item']['purchase_price'];
            }
            $parent_id = $k;
            $order_data[$k] = $item;
            if (!empty($item['services'])) {
                foreach ($item['services'] as $is) {
                    if (!empty($is['item'])) {
                        $k++;
                        $is['item']['parent_id'] = $parent_id;
                        $is['item']['currency'] = $item['currency'];
                        $order_data[$k] = $is['item'];
                    }
                }
            }
            $k++;
        }
        return $order_data;
    }
}
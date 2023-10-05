<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopQuickorderPluginOrder extends shopQuickorderPluginAnalytics
{
    protected $cart;
    private $fields;

    public function __construct()
    {
        $this->cart = new shopQuickorderPluginCart(waRequest::post('qformtype', 'product'));
        $this->fields = waRequest::post('quickorder_fields', array());
        // Необходимая доработка поля страны, чтобы при проверке обязательных полей не было проблем
        if (isset($this->fields['country'])) {
            $this->fields['address::country'] = $this->fields['country'];
        }
        waRequest::setParam('quickorder_create', 1);
    }

    /**
     * Get cart object
     *
     * @return shopQuickorderPluginCart
     */
    public function getCart()
    {
        return $this->cart;
    }

    /**
     * Get fields array
     *
     * @return array|mixed|null
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Prepare data to create an order
     *
     * @param array $errors
     * @return array
     * @throws Exception
     */
    public function prepareOrder(&$errors = array())
    {
        $validate = new shopQuickorderPluginValidate($this);
        $settings = shopQuickorderPluginHelper::getSettings();

        // Выполняем основные проверки
        if (!$validate->isValid()) {
            $errors = $validate->getErrors();
            return false;
        }

        // Фильтруем контактные поля на случай, если была попытка их подмены
        $this->fields = $validate->fieldsFilter($this->fields);

        if (!wa()->getSetting('ignore_stock_count')) {
            $check_count = true;
            if (wa()->getSetting('limit_main_stock') && waRequest::param('stock_id')) {
                $check_count = waRequest::param('stock_id');
            }

            $not_available_items = (new shopQuickorderPluginCartItemsModel())->getNotAvailableProducts($this->cart, $check_count);
            foreach ($not_available_items as $row) {
                if ($row['sku_name']) {
                    $row['name'] .= ' (' . $row['sku_name'] . ')';
                }
                if ($row['available']) {
                    if ($row['count'] > 0) {
                        $errors[] = sprintf(_w('Only %d pcs of %s are available, and you already have all of them in your shopping cart.'), $row['count'], $row['name']);
                    } else {
                        $errors[] = sprintf(_w('Oops! %s just went out of stock and is not available for purchase at the moment. We apologize for the inconvenience. Please remove this product from your shopping cart to proceed.'), $row['name']);
                    }
                } else {
                    $errors[] = sprintf(_w('Oops! %s is not available for purchase at the moment. Please remove this product from your shopping cart to proceed.'), $row['name']);
                }
            }
            if ($errors) {
                $errors['update_captcha'] = '';
                return false;
            }
        }

        $contact = $cart_contact = $this->cart->getContact();

        // Если совпадает email или телефон, присваиваем заказ существующему покупателю
        $user_is_auth = wa()->getUser()->isAuth();
        if (!$user_is_auth && (!isset($settings['unauthorized_checkout'])) || $settings['unauthorized_checkout'] == 'find_user') {
            foreach (['phone', 'email'] as $field_id) {
                if (!empty($this->fields[$field_id])) {
                    $c = new waContactsCollection('search/' . $field_id . '=' . (is_array($this->fields[$field_id]) && !empty($this->fields[$field_id][0]['value']) ? $this->fields[$field_id][0]['value'] : $this->fields[$field_id]));
                    $c = reset(ref($c->getContacts('id,is_company', 0, 1)));
                    if (!empty($c['id'])) {
                        $contact = new waContact($c['id']);
                        $user_is_auth = 1;
                        break;
                    }
                }
            }
        }

        // Сохраняем переданные поля
        foreach ($this->fields as $field_id => $value) {
            if (strpos($field_id, '::') !== false) {
                continue;
            }
            $contact->set($field_id, $value);
        }
        if ($cart_user_addresses = $cart_contact->get('address.shipping')) {
            $contact_shipping_address = ifset(ref($contact->get('address.shipping')), 0, 'data', []);
            $cart_shipping_address = ifset($cart_user_addresses, 0, 'data', []);
            $contact->set('address.shipping', array_merge($contact_shipping_address, $cart_shipping_address));
        }

        if ($user_is_auth) {
            $contact->save();
        } else {
            if (!$contact->get("name")) {
                $contact->set("firstname", "<" . _wp("no-name") . ">");
            }
        }

        $items = $this->cart->getItems();
        // remove id from item
        foreach ($items as &$item) {
            unset($item['id']);
            unset($item['parent_id']);
        }
        unset($item);

        $order = array(
            'contact' => $contact,
            'items' => $items,
            'total' => $this->cart->getTotal(false, false),
            'params' => array(),
        );

        // Купон на скидку
        if ($coupon_code = $this->cart->getData('coupon')) {
            $data['coupon_code'] = $coupon_code;
            wa()->getStorage()->set('shop/checkout', $data);
        }

        $order['discount_description'] = null;

        // Вычисляем скидки от общей стоимости товаров без учета стоимости доставки
        $order['discount'] = shopDiscounts::apply($order, $order['discount_description']);

        // Сумма заказа с учетом стоимости доставки
        $order['total'] = $this->cart->getTotal(true, false);

        $shipping_class = new shopQuickorderPluginWaShipping($this->cart);
        $shipping = $shipping_class->getSelectedMethod();
        if (!empty($shipping)) {
            $order['params']['shipping_id'] = $shipping['id'];
            $order['params']['shipping_rate_id'] = $shipping['rate_id'];

            $rate = $shipping_class->getRate($order['params']['shipping_id'], $order['params']['shipping_rate_id']);

            $order['params']['shipping_plugin'] = $rate['plugin'];
            $order['params']['shipping_name'] = $rate['name'];
            $order['params']['shipping_tax_id'] = $rate['tax_id'];
            if (isset($rate['est_delivery'])) {
                $order['params']['shipping_est_delivery'] = $rate['est_delivery'];
            }
            if (!isset($order['shipping'])) {
                $order['shipping'] = $rate['rate'];
            }
            if (!empty($rate['params'])) {
                foreach ($rate['params'] as $k => $v) {
                    if (strpos($k, '_') !== 0) {
                        # save params without leading '_'
                        $order['params']['shipping_params_' . $k] = $v;
                    }
                }
            }
        } else {
            $order['shipping'] = 0;
        }

        $payment_class = new shopQuickorderPluginWaPayment($this->cart);
        $payment = $payment_class->getSelectedMethod();

        if (!empty($payment)) {
            $order['params']['payment_id'] = $payment;
            $plugin_model = new shopPluginModel();
            $plugin_info = $plugin_model->getById($payment);
            $order['params']['payment_name'] = $plugin_info['name'];
            $order['params']['payment_plugin'] = $plugin_info['plugin'];
            $payment_params = waRequest::post('payment_' . $payment);
            if ($payment_params) {
                foreach ($payment_params as $k => $v) {
                    $order['params']['payment_params_' . $k] = $v;
                }
            }
        }

        $routing_url = wa()->getRouting()->getRootUrl();
        $order['params']['storefront'] = wa()->getConfig()->getDomain() . ($routing_url ? '/' . $routing_url : '');
        // Сохраняем канал продаж
        $order['params']['quickorder_' . $this->cart->getType()] = 'storefront:' . $order['params']['storefront'];

        $ref = waRequest::cookie('referer') ? waRequest::cookie('referer') : waRequest::server('HTTP_REFERER');
        if ($ref) {
            $order['params']['referer'] = $ref;
            $ref_parts = @parse_url($ref);
            $order['params']['referer_host'] = $ref_parts['host'];
            // try get search keywords
            if (!empty($ref_parts['query'])) {
                $search_engines = array(
                    'text' => 'yandex\.|rambler\.',
                    'q' => 'bing\.com|mail\.|google\.',
                    's' => 'nigma\.ru',
                    'p' => 'yahoo\.com'
                );
                $q_var = false;
                foreach ($search_engines as $q => $pattern) {
                    if (preg_match('/(' . $pattern . ')/si', $ref_parts['host'])) {
                        $q_var = $q;
                        break;
                    }
                }
                // default query var name
                if (!$q_var) {
                    $q_var = 'q';
                }
                parse_str($ref_parts['query'], $query);
                if (!empty($query[$q_var])) {
                    $order['params']['keyword'] = $query[$q_var];
                }
            }
        }

        if (($utm = waRequest::cookie('utm'))) {
            $utm = json_decode($utm, true);
            if ($utm && is_array($utm)) {
                foreach ($utm as $k => $v) {
                    $order['params']['utm_' . $k] = $v;
                }
            }
        }

        if (($landing = waRequest::cookie('landing')) && ($landing = @parse_url($landing))) {
            if (!empty($landing['query'])) {
                @parse_str($landing['query'], $arr);
                if (!empty($arr['gclid'])
                    && !empty($order['params']['referer_host'])
                    && strpos($order['params']['referer_host'], 'google') !== false
                ) {
                    $order['params']['referer_host'] .= ' (cpc)';
                    $order['params']['cpc'] = 1;
                } elseif (!empty($arr['_openstat'])
                    && !empty($order['params']['referer_host'])
                    && strpos($order['params']['referer_host'], 'yandex') !== false
                ) {
                    $order['params']['referer_host'] .= ' (cpc)';
                    $order['params']['openstat'] = $arr['_openstat'];
                    $order['params']['cpc'] = 1;
                }
            }

            $order['params']['landing'] = $landing['path'];
        }

        // A/B tests
        $abtest_variants_model = new shopAbtestVariantsModel();
        foreach (waRequest::cookie() as $k => $v) {
            if (substr($k, 0, 5) == 'waabt') {
                $variant_id = $v;
                $abtest_id = substr($k, 5);
                if (wa_is_int($abtest_id) && wa_is_int($variant_id)) {
                    $row = $abtest_variants_model->getById($variant_id);
                    if ($row && $row['abtest_id'] == $abtest_id) {
                        $order['params']['abt' . $abtest_id] = $variant_id;
                    }
                }
            }
        }

        $order['params']['ip'] = waRequest::getIp();
        $order['params']['user_agent'] = waRequest::getUserAgent();

        foreach (array('shipping', 'billing') as $ext) {
            $address = $contact->getFirst('address.' . $ext);
            if ($address) {
                foreach ($address['data'] as $k => $v) {
                    $order['params'][$ext . '_address.' . $k] = $v;
                }
            }
        }

        if ($comment = $this->cart->getData('comment')) {
            $order['comment'] = $comment;
        }

        if (class_exists('shopStockRulesModel')) {
            list($stock_id, $virtualstock_id) = $this->determineStockIds($order);
            if ($virtualstock_id) {
                $order['params']['virtualstock_id'] = $virtualstock_id;
            }
            if ($stock_id) {
                $order['params']['stock_id'] = $stock_id;
            }
        } else {
            if ($stock_id = waRequest::post('stock_id', waRequest::param('stock_id', null, 'string'))) {
                $order['params']['stock_id'] = $stock_id;
            }
        }

        return $order;
    }

    private function determineStockIds($order)
    {
        $stock_rules_model = new shopStockRulesModel();
        $rules = $stock_rules_model->getRules();
        $stocks = shopHelper::getStocks();

        $event_params = array(
            'order' => $order,
            'stocks' => $stocks,
            'rules' => &$rules,
        );
        $this->processBuiltInRules($event_params);
        wa('shop')->event('frontend_checkout_stock_rules', $event_params);

        $groups = $stock_rules_model->prepareRuleGroups($rules);
        foreach ($groups as $g) {
            if (($g['stock_id'] && empty($stocks[$g['stock_id']])) || ($g['virtualstock_id'] && empty($stocks['v' . $g['virtualstock_id']]))) {
                continue;
            }

            $all_fulfilled = true;
            foreach ($g['conditions'] as $rule) {
                if (!ifset($rule['fulfilled'], false)) {
                    $all_fulfilled = false;
                    break;
                }
            }
            if ($all_fulfilled) {
                return array($g['stock_id'], $g['virtualstock_id']);
            }
        }

        // No rule matched the order. Use stock specified in routing params.
        $virtualstock_id = null;
        $stock_id = waRequest::param('stock_id', null, 'string');
        if (empty($stocks[$stock_id])) {
            $stock_id = null;
        } elseif (isset($stocks[$stock_id]['substocks'])) {
            $virtualstock_id = $stocks[$stock_id]['id'];
            $stock_id = null;
        }
        return array($stock_id, $virtualstock_id);
    }

    private function processBuiltInRules(&$params)
    {
        $shipping_type_id = null;
        if (!empty($params['order']['params']['shipping_id'])) {
            $shipping_type_id = $params['order']['params']['shipping_id'];
        }
        $shipping_country = $shipping_region = null;
        if (!empty($params['order']['params']['shipping_address.country'])) {
            $shipping_country = (string) $params['order']['params']['shipping_address.country'];
            if (!empty($params['order']['params']['shipping_address.region'])) {
                $shipping_region = $shipping_country . ':' . $params['order']['params']['shipping_address.region'];
            }
        }

        foreach ($params['rules'] as &$rule) {
            if ($rule['rule_type'] == 'by_shipping') {
                $rule['fulfilled'] = $shipping_type_id && $shipping_type_id == $rule['rule_data'];
            } elseif ($rule['rule_type'] == 'by_region') {
                $rule['fulfilled'] = false;
                foreach (explode(',', $rule['rule_data']) as $candidate) {
                    if ($candidate === $shipping_country || $candidate === $shipping_region) {
                        $rule['fulfilled'] = true;
                        break;
                    }
                }
            }
        }
        unset($rule);
    }

    /**
     * Create an order
     *
     * @param array $order
     * @return mixed
     */
    public function createOrder($order)
    {
        $workflow = new shopWorkflow();
        
        $order_id = $workflow->getActionById('create')->run($order);
        return $order_id;
    }

    /**
     * Action after successful order
     *
     * @param int $order_id
     * @return array
     * @throws waException
     */
    public function success($order_id)
    {
        $settings = shopQuickorderPluginHelper::getSettings();

        $order_model = new shopOrderModel();
        $order_items_model = new shopOrderItemsModel();
        $this->order = $order = $order_model->getById($order_id);
        $this->order['id'] = shopHelper::encodeOrderId($this->order['id']);
        $this->order['items'] = $order['items'] = $order_items_model->getByField('order_id', $order_id, true);

        // Код аналитики
        $analytics = $this->getAnalytics($settings, $this->cart->getType());

        // Если обрабатывалась корзина, очищаем сессию и перенаправляем
        if ($this->cart->getType() == 'cart') {
            (new shopCart())->clear();
            wa()->getStorage()->del('shop/checkout');
            wa()->getStorage()->set('shop/order_id', $order_id);
            return array('redirect' => wa('shop')->getRouteUrl('shop/frontend/checkout', array('step' => 'success')), 'analytics' => $analytics, 'order_id' => $order_id);
        } else {
            $order_params_model = new shopOrderParamsModel();
            $order['params'] = $order_params_model->get($order_id);
            if ($order) {
                $order['_id'] = $order['id'];
            }
            // Если используется плагин оплаты, перенаправляем на страницу успешного завершения
            if (!empty($order['params']['payment_id'])) {
                wa()->getStorage()->set('shop/order_id', $order_id);
                return array('redirect' => wa('shop')->getRouteUrl('shop/frontend/checkout', array('step' => 'success')), 'analytics' => $analytics, 'order_id' => $order_id);
            }

            $view = new waSmarty3View(wa());
            $view->assign('order', $this->order);

            $msg = !empty($settings['product']['successfull_message']) ? $settings['product']['successfull_message'] : (!isset($settings['product']['successfull_message']) ? shopQuickorderPluginHelper::getDefaultSuccessMsg() : '');
            $msg .= <<<HTML
{if !empty(\$payment)}
    <div class='quickorder-row quickorder-success-payment-block'>{\$payment}</div>
{/if}
HTML;
            return array('html' => $view->fetch('string:' . $msg) . $analytics, 'order_id' => $order_id);
        }
    }

}
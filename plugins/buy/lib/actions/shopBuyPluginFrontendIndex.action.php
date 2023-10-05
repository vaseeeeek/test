<?php
/**
 * Class shopBuyPluginFrontendIndexAction
 *
 * @author    Eugen Nichikov <eugen@hardman.com.ua>
 * @copyright Hardman.com.ua
 */
class shopBuyPluginFrontendIndexAction extends shopFrontendAction {

    protected $data = array();
    protected $contact;
    protected $order;

    public function execute()
    {

        if (waRequest::param('ssl') && !waRequest::isHttps()) {
            $url = 'https://'.waRequest::server('HTTP_HOST').wa()->getConfig()->getCurrentUrl();
            wa()->getResponse()->redirect($url, 301);
        }

        wa()->popActivePlugin();

        $cart = new shopCart();
        if (!$cart->count()) {
            $this->redirect(wa()->getRouteUrl('/frontend/cart'));
        }



        if($this->getTheme()->getFile('plugin.buy.html')) {
            $this->setThemeTemplate('plugin.buy.html');
        }

        //$loc = shopCityselectHelper::detectLocation();
        //shopCityselectHelper::setCity($loc['city'], $loc['region'], $loc['zip']);

        $this->data = $this->getSessionData();
        if(!is_array($this->data)) {
            $this->data = array();
        }

        if (wa()->getUser()->isAuth()) {
            $this->data['contact'] = wa()->getUser();
        } elseif(empty($this->data['contact'])) {
            $this->data['contact'] = new waContact();
        }
        unset($this->data['register']);

        if ($this->validateFields() && $this->createOrder()) {
            $this->redirect('/checkout/success');
        }



        $this->getPayments();
        $this->getShippings();

        $total = $cart->total(false);
        $o = array(
            'total' => $total,
            //'items' => $cart->items()
            'items' => $this->getItems(),
        );
        $discount = shopDiscounts::calculate($o);

        $plugin = wa()->getPlugin('buy');

        $rm = new waRegionModel();
        $regions = $rm->select('code,name')->where('country_iso3=?', 'rus')->order('name')->fetchAll('code', true);


        if(class_exists('shopZipomagicPlugin')) {
            /**
             * @var shopZipomagicPlugin $zipomagic
             */
            $zipomagic = wa()->getPlugin('zipomagic');
            $zipomagic = $zipomagic->hookFrontendCheckout();
            //wa_dump($zipomagic);
        } else {
            $zipomagic = '';
        }

        $this->view->assign(array(
            'customer' => $this->data['contact'],
            'comment' => ifempty($this->data['comment']),
            'register' => ifempty($this->data['register']),
            'total' => $total,
            'discount' => $discount,
            'buy_settings' => $plugin->getSettings(),
            'is_auth'  => wa()->getUser()->isAuth(),
            'regions' => $regions,
            'zipomagic' => $zipomagic,
        ));

        $this->getResponse()->setTitle(_w('Checkout'));


        $step_number = shopCheckout::getStepNumber('contactinfo');
        $checkout_flow = new shopCheckoutFlowModel();
        $checkout_flow->add(array(
            'step' => $step_number
        ));


        /**
         * @event frontend_checkout
         * @return array[string]string $return[%plugin_id%] html output
         */
        $event_params = array('step' => 'contactinfo');
        $this->view->assign('frontend_checkout', wa()->event('frontend_checkout', $event_params));


        $shippinginfo = array();
        $city_valid = [];
        if(class_exists('shopShippinginfoPlugin')) {
            try {
                $shippinginfo_plugin = wa('shop')->getPlugin('shippinginfo');
                if($shippinginfo_plugin->getSettings('free_shipping')) {
                    $shippinginfo = $shippinginfo_plugin->getSettings('shipping');
                }

                if(!empty($this->data['contact']['city'])) {
                    foreach ($shippinginfo_plugin->getSettings('shipping') as $shipping) {
                        if(!empty($shipping['free_city'])) {
                            $city_valid[$shipping['id']] = $shippinginfo_plugin->checkCity($this->data['contact']['city'], $shipping['id']);
                        }
                    }
                }
            } catch (Exception $e) {
                //var_dump($e->getMessage());
            }
        }
        $this->view->assign('shippinginfo', $shippinginfo);
        $this->view->assign('city_valid', $city_valid);
    }

    protected function validateFields()
    {
        $error = array();

        if($count = shopBuyPlugin::checkCartItems()) {
            $error['count'] = $count;
        }

        $data = waRequest::post();
        if(!$data) {
            $this->view->assign('error', $error);
            return false;
        }


        if(empty($data['customer']['name'])) {
            $error['name'] = 1;
        }

        if(empty($data['customer']['phone'])) {
            $error['phone'] = 1;
        }

        //if(empty($data['customer']['zip1'])) {
        //    $error['zip1'] = 1;
        //}

        $email_validator = new waEmailValidator();
        if (!$email_validator->isValid($data['customer']['email'])) {
            $e = $email_validator->getErrors();
            if($e) {
                $error['email'] = $e;
            }
        }

        if (!empty($data['register'])) {
            $contact_model = new waContactModel();
            if ($contact_model->getByEmail($data['customer']['email'], true)) {
                $error['email'][] = _w('Email already registered');
            }

            if(!$this->validateSrt($data['password'], 4)) {
                $error['password'] = 1;
            }
        }

        $customer = ifempty($data['customer'], array());
        $address = ifempty($customer['address.shipping'], array());

        $this->data['payment_id'] = (int) $data['payment_id'];
        $this->data['shipping_id'] = (int) $data['shipping_id'];
        $this->data['shipping_rate'] = (array) $data['rate_id'];
        $this->data['register'] = ifempty($data['register']);
        $this->data['password'] = ifempty($data['password']);
        $this->data['comment'] = ifempty($data['comment']);


        $this->data['contact']['email'] = ifempty($customer['email']);

        if(empty($customer['firstname']) && empty($customer['lastname']) && empty($customer['middlename'])) {
            $this->data['contact']->set('name', ifempty($customer['name']));
        } else {
            $name = waContactNameField::formatName($customer, true);
            $this->data['contact']->set('name', $name);
        }
        if(!empty($customer['phone'])) {
            $customer['phone'] = '+7 ('.$customer['phone'];
            $this->data['contact']['phone'] = $customer['phone'];
        }

        $address_contact = $this->data['contact']->get('address.shipping');
        if (!empty($address['country'])) {
            $address_contact[0]['data']['country'] = $address['country'];
        }
        if (!empty($address['city'])) {
            $address_contact[0]['data']['city'] = $address['city'];
        }
        if (!empty($address['region'])) {
            $address_contact[0]['data']['region'] = $address['region'];
        }
        if (!empty($address['zip'])) {
            $address_contact[0]['data']['zip'] = $address['zip'];
        }
        if (!empty($address['street'])) {
            $address_contact[0]['data']['street'] = $address['street'];
        }

        $this->data['contact']->set('address.shipping', $address_contact);

        //$this->data['contact']['zip1'] = $data['customer']['zip1'];


        //wa_dump($customer, $customer['name'], $this->data['contact']->get('name'));
        if(wa()->getUser()->isAuth()) {
            $this->data['contact']->save();
        }

        foreach($this->data as $k => $v) {
            $this->setSessionData($k, $v);
        }

        $this->view->assign('error', $error);
        if($error) {
            return false;
        }
        return true;
    }

    protected function getPayments()
    {
        $plugin_model = new shopPluginModel();

        $methods = $plugin_model->listPlugins('payment');

        $currencies = wa('shop')->getConfig()->getCurrencies();
        foreach ($methods as $method_id => $m) {
            $plugin = shopPayment::getPlugin($m['plugin'], $m['id']);
            $plugin_info = $plugin->info($m['plugin']);
            $methods[$method_id]['icon'] = $plugin_info['icon'];
            $custom_fields = $this->getCustomPaymentFields($method_id, $plugin);
            $custom_html = '';
            foreach ($custom_fields as $c) {
                $custom_html .= '<div class="wa-field">'.$c.'</div>';
            }
            $methods[$method_id]['custom_html'] = $custom_html;
            $allowed_currencies = $plugin->allowedCurrency();
            if ($allowed_currencies !== true) {
                $allowed_currencies = (array) $allowed_currencies;
                if (!array_intersect($allowed_currencies, array_keys($currencies))) {
                    $methods[$method_id]['error'] = sprintf(_w('Payment procedure cannot be processed because required currency %s is not defined in your store settings.'), implode(', ', $allowed_currencies));
                }
            }
        }

        $this->view->assign('checkout_payment_methods', $methods);

        if(empty($this->data['payment_id'])) {
            $m = reset($methods);
            $this->data['payment_id'] = $m ? $m['id'] : null;
        }
        $this->view->assign('payment_id', $this->data['payment_id']);
    }


    protected function getShippings()
    {
        $plugin_model = new shopPluginModel();
        $methods = $plugin_model->listPlugins('shipping');

        foreach ($methods as &$m) {
            if(!empty($this->data['shipping_rate'])) {
                if (isset($this->data['shipping_rate'][$m['id']])) {
                    $m['rate_id'] = $this->data['shipping_rate'][$m['id']];
                }
            }
            $plugin = shopShipping::getPlugin($m['plugin'], $m['id']);

            $custom_fields = $this->getCustomShippingFields($m['id'], $plugin);
            $custom_html = '';
            foreach ($custom_fields as $c) {
                $custom_html .= '<div class="wa-field">'.$c.'</div>';
            }
            $m['custom_html'] = $custom_html;
        }

        $this->view->assign('checkout_shipping_methods', $methods);

        if(empty($this->data['shipping_id'])) {
            $_method = reset($methods);
            $this->data['shipping_id'] = $_method ? $_method['id'] : null;
        }

        $this->view->assign('shipping_id', $this->data['shipping_id']);
    }


    public function getItems($weight_unit = null)
    {
        $items = array();
        $cart = new shopCart();
        $cart_items = $cart->items();
        $product_ids = $sku_ids = array();
        foreach ($cart_items as $item) {
            $product_ids[] = $item['product_id'];
            $sku_ids[] = $item['sku_id'];
        }
        $feature_model = new shopFeatureModel();
        $f = $feature_model->getByCode('weight');
        if (!$f) {
            $values = array();
        } else {
            $values_model = $feature_model->getValuesModel($f['type']);
            $values = $values_model->getProductValues($product_ids, $f['id']);
        }

        $m = null;
        if ($weight_unit) {
            $dimension = shopDimension::getInstance()->getDimension('weight');
            if ($weight_unit != $dimension['base_unit']) {
                $m = $dimension['units'][$weight_unit]['multiplier'];
            }
        }

        $insantrik1c_plugin = wa()->getPlugin('insantrik1c');
        $sizes = $insantrik1c_plugin->getSettings('sizes');
        $product_model = new shopProductModel();
        $default = $sizes['default'];

        foreach ($cart_items as $item) {
            $product = $product_model->getById($item['product_id']);

            if (isset($values['skus'][$item['sku_id']])) {
                $w = $values['skus'][$item['sku_id']];
            } else {
                $w = isset($values[$item['product_id']]) ? $values[$item['product_id']] : 0;
            }
            if ($m !== null) {
                $w = $w / $m;
            }

            $row = array(
                'name' => $item['name'],
                'price' => $item['price'],
                'quantity' => $item['quantity'],
                'weight' => $w
            );

            if (!empty($product) && isset($sizes[$product['type_id']])) {
                $size = $sizes[$product['type_id']];

                foreach (['w' => 'width', 'h' => 'height', 'l' => 'length', 'wh' => 'weight'] as $key => $value) {
                    if (!empty($size[$key])) {
                        $row[$value] = $size[$key];
                    }
                    else {
                        $row[$value] = $default[$key];
                    }

                    if ($key != 'wh') {
                        $row[$value] = round(($row[$value] / 100), 3, PHP_ROUND_HALF_UP);
                        $row[$value] = number_format($row[$value], 3, '.', '');
                    }
                }
            }

            $items[] = $row;
        }

        return $items;
    }

    protected function getCustomPaymentFields($id, waPayment $plugin)
    {
        if (wa()->getUser()->isAuth()) {
            $contact = wa()->getUser();
            //$this->contact->getOption('')
        } else {
            $contact = new waContact();
        }
        //$contact = $this->getContact($this->data['customer']);
        $order_params = array();

        $cart = new shopCart();
        $payment_params = waRequest::post('payment_'.$id);
        $payment_params = $payment_params ? $payment_params : array();
        foreach ($payment_params as $k => $v) {
            $order_params['payment_params_'.$k] = $v;
        }
        $order = new waOrder(array('contact' => $contact,
            'contact_id' => $contact ? $contact->getId() : null,
            'params' => $order_params,
            'items' => $cart->items()
        ));
        $custom_fields = $plugin->customFields($order);
        if (!$custom_fields) {
            return $custom_fields;
        }

        $params = array();
        $params['namespace'] = 'payment_'.$id;
        $params['title_wrapper'] = '%s';
        $params['description_wrapper'] = '<br><span class="hint">%s</span>';
        $params['control_wrapper'] = '<div class="wa-name">%s</div><div class="wa-value">%s %s</div>';

        $selected = ($id == $this->data['payment_id']);

        $controls = array();
        foreach ($custom_fields as $name => $row) {
            $row = array_merge($row, $params);
            if ($selected && isset($payment_params[$name])) {
                $row['value'] = $payment_params[$name];
            }
            $controls[$name] = waHtmlControl::getControl($row['control_type'], $name, $row);
        }

        return $controls;
    }


    protected function getControls($custom_fields, $namespace = null)
    {
        $params = array();
        $params['namespace'] = $namespace;
        $params['title_wrapper'] = '%s';
        $params['description_wrapper'] = '<span class="hint">%s</span>';
        //$params['description_wrapper'] = '<br><span class="hint">%s</span>';
        $params['control_wrapper'] = '<div class="wa-name">%s</div><div class="wa-value"><p><span>%3$s %2$s</span></p></div>';
        //$params['control_wrapper'] = '<div class="wa-name">%s</div><div class="wa-value">%s %s</div>';
        $params['control_separator'] = '</span><span>';

        $controls = array();
        foreach ($custom_fields as $name => $row) {
            $row = array_merge($row, $params);
            $controls[$name] = waHtmlControl::getControl($row['control_type'], $name, $row);
        }

        return $controls;
    }


    protected function getCustomShippingFields($id, waShipping $plugin)
    {
        if (wa()->getUser()->isAuth()) {
            $contact = wa()->getUser();
            //$this->contact->getOption('')
        } else {
            $contact = new waContact();
        }
        $order_params = $this->getSessionData('params', array());
        $shipping_params = isset($order_params['shipping']) ? $order_params['shipping'] : array();
        foreach ($shipping_params as $k => $v) {
            $order_params['shipping_params_'.$k] = $v;
        }
        $order = new waOrder(array(
            'contact'    => $contact,
            'contact_id' => $contact ? $contact->getId() : null,
            'params'     => $order_params,
        ));
        $custom_fields = $plugin->customFields($order);
        if (!$custom_fields) {
            return $custom_fields;
        }

        $selected = !empty($this->data['shipping_id']) ? $id == $this->data['shipping_id'] : '';

        if ($selected) {
            foreach ($custom_fields as $name => &$row) {
                if (isset($shipping_params[$name])) {
                    $row['value'] = $shipping_params[$name];
                }
            }
        }

        return $this->getControls($custom_fields, 'shipping_'.$id);
    }

    protected function validateSrt($str, $min_length = 0, $max_length = 0)
    {
        if($min_length && mb_strlen($str) < $min_length) {
            return false;
        }

        if($max_length && mb_strlen($str) > $max_length) {
            return false;
        }

        return true;
    }

    protected function createOrder()
    {
        $contact = $this->data['contact'];
        if($this->data['register']) {
            $contact->set('password', $this->data['password']);
        }
        $contact->save();

        $cart = new shopCart();
        $items = $cart->items(false);
        // remove id from item
        foreach ($items as &$item) {
            unset($item['id']);
            unset($item['parent_id']);
        }
        unset($item);
        $total = $cart->total(false);


        $this->data['params'] = isset($this->data['params']) ? $this->data['params'] : array();
        $this->data['params'] += $this->_getOrderParamsFromRequest();
        $address_contact = $contact->get('address.shipping');
        $this->data['params']['dadata_zip'] = $address_contact[0]['data']['zip'];
        //$address_contact[0]['data']['zip'] = $contact['zip1'];
        $contact->set('address.shipping', $address_contact);

        $order = array(
            'contact' => $contact,
            'items'   => $items,
            'total'   => $total,
            'params'  => $this->data['params'],
        );

        $order['discount'] = shopDiscounts::apply($order, $order['discount_description']);

        $plugin_model = new shopPluginModel();

        if (isset($this->data['payment_id'])) {
            $order['params']['payment_id'] = $this->data['payment_id'];
            $plugin_info = $plugin_model->getById($this->data['payment_id']);
            $order['params']['payment_name'] = $plugin_info['name'];
            $order['params']['payment_plugin'] = $plugin_info['plugin'];
            $params = waRequest::post('payment_'.$this->data['payment_id']);
            if (!empty($params)) {
                foreach ($params as $k => $v) {
                    $order['params']['payment_params_'.$k] = $v;
                }
                unset($params);
            }

            if($this->data['payment_id'] == 4) {
                $dd = round(($total - $order['discount']) * .05);
                $order['discount'] += $dd;
                $order['discount_description'] .= "\nСкидка 5% (".shop_currency($dd).") за предоплату";
            }
        }

        if(isset($this->data['shipping_id']) && $this->data['shipping_id']) {
            $plugin_info = $plugin_model->getById($this->data['shipping_id']);
            $plugin = shopShipping::getPlugin($plugin_info['plugin'], $this->data['shipping_id']);
            $total = $cart->total();
            $currency = $plugin->allowedCurrency();
            $round_shipping = wa()->getSetting('round_shipping');
            $current_currency = wa()->getConfig()->getCurrency(false);
            if ($currency != $current_currency) {
                $total = shop_currency($total, $current_currency, $currency, false);
            }

            $shipping_params = shopShipping::getItemsTotal($items);

            if(isset($this->data['shipping_rate'][$this->data['shipping_id']])) {
                $rate_id = $this->data['shipping_rate'][$this->data['shipping_id']];
            } else {
                $rate_id = null;
            }
            $shipping_params['total_price'] = $total;

            if($address = $this->data['contact']->get('address.shipping')) {
                $address = reset($address);
                $address = $address['data'];
            } else {
                $address = array();
            }
            $items = $this->getItems($plugin->allowedWeightUnit());

            $rates = $plugin->getRates($items, $address, $shipping_params);

            if (!$rates) {
                $result = array('rate' => 0);
            } elseif (is_string($rates)) {
                $result = array('rate' => 0);
            } elseif ($rate_id) {
                $result = $rates[$rate_id];
            } else {
                $result = array('rate' => 0);
            }

            if (is_array($result['rate'])) {
                $result['rate'] = max($result['rate']);
            }
            if ($currency != $current_currency) {
                $result['rate'] = shop_currency($result['rate'], $currency, $current_currency, false);
            }
            if ($result['rate'] && $round_shipping) {
                $result['rate'] = shopRounding::roundCurrency($result['rate'], $current_currency);
            }
            $order['params']['shipping_id'] = $this->data['shipping_id'];
            $order['params']['shipping_rate_id'] = $rate_id;
            $order['params']['shipping_rate'] = $result['rate'];
            $order['params']['shipping_plugin'] = $plugin->getId();


            $shipping_name = $result['name'];

            $order['params']['shipping_name'] = $plugin_info['name'] . ': ' . $shipping_name;
            $order['shipping'] = $result['rate'];

            $shippinginfo = array();
            $city_valid = false;
            if(class_exists('shopShippinginfoPlugin')) {
                try {
                    $shippinginfo_plugin = wa('shop')->getPlugin('shippinginfo');

                    if($shippinginfo_plugin->getSettings('free_shipping')) {
                        $shippinginfo = $shippinginfo_plugin->getSettings('shipping');
                    }

                    if(!empty($this->data['contact']['city'])) {
                        $city_valid = $shippinginfo_plugin->checkCity(ifempty($address['city']),$this->data['shipping_id']);
                    }
                } catch (Exception $e) {
                    //var_dump($e->getMessage());
                }
            }
            if($shippinginfo && !empty($shippinginfo[$this->data['shipping_id']])) {
                $si = $shippinginfo[$this->data['shipping_id']];
                $ct = $cart->total(false);
                if(
                    !empty($si['free_status']) &&
                    (empty($si['free_city']) || $city_valid) &&
                    ($ct >= ifempty($si['free_step'])) &&
                    ($order['shipping'] <= ifempty($si['free_step_shipping']))
                ) {
                    $original_shipping = $order['shipping'];
                    $order['shipping'] = 0;
                }

                waLog::dump(array(
                    'shippinginfo' => $si,
                    'cart_total' => $ct
                ),'shop/plugins/shippinginfo/free_shipping.log');
            }
        }

        /**
         * @todo BuyPlugin. Change stock if need
         */
        if ($stock_id = waRequest::post('stock_id')) {
            $order['params']['stock_id'] = $stock_id;
        }

        //$routing_url = wa()->getRouting()->getRootUrl();
        //$order['params']['storefront'] = wa()->getConfig()->getDomain().($routing_url ? '/'.$routing_url : '');


        /**
         * @todo BuyPlugin. Referal program
         *
         *//*
        if ($ref = wa()->getStorage()->get('shop/referer')) {
            $order['params']['referer'] = $ref;
            $ref_parts = parse_url($ref);
            $order['params']['referer_host'] = $ref_parts['host'];
        }*/


        foreach (array('shipping', 'billing') as $ext) {
            $address = $contact->getFirst('address.'.$ext);
            if (!$address) {
                $address = $contact->getFirst('address');
            }
            if ($address) {
                foreach ($address['data'] as $k => $v) {
                    $order['params'][$ext.'_address.'.$k] = $v;
                }
            }
        }

        if (isset($this->data['comment'])) {
            $order['comment'] = $this->data['comment'];
        }

        $workflow = new shopWorkflow();
        if ($order_id = $workflow->getActionById('create')->run($order)) {
            $cart->clear();
            //wa()->getStorage()->remove('shop/checkout');

            if(!empty($original_shipping)) {
                $olm = new shopOrderLogModel();
                $olm->add([
                    'order_id' => $order_id,
                    'contact_id' => null,
                    'action_id' => '',
                    'text' => sprintf('<i class="icon16 exclamation-red"></i> Заказ с бесплатной доставкой. Фактическая стоимость доставки: %s руб.', $original_shipping),
                    'before_state_id' => 'new',
                    'after_state_id' => 'new',
                ]);
            }


            $step_number = shopCheckout::getStepNumber();
            $checkout_flow = new shopCheckoutFlowModel();
            $checkout_flow->add(array(
                'step' => $step_number
            ));

            wa()->getStorage()->set('shop/order_id', $order_id);
            return true;
        }
    }


    protected function getSessionData($key = null, $default = null)
    {
        $data = wa()->getStorage()->get('shop/checkout');
        return isset($key) ? (isset($data[$key]) ? $data[$key] : $default) : $data;
    }

    protected function setSessionData($key, $value)
    {
        $data = wa()->getStorage()->get('shop/checkout', array());
        $data[$key] = $value;
        wa()->getStorage()->set('shop/checkout', $data);
    }

    protected function _getOrderParamsFromRequest()
    {
        $params = [];

        $wa = wa('shop');
        $routing_url = $wa->getRouting()->getRootUrl();
        $params['storefront'] = $wa->getConfig()->getDomain().($routing_url ? '/'.$routing_url : '');

        if ($wa->getStorage()->get('shop_order_buybutton')) {
            $params['sales_channel'] = 'buy_button:';
        }

        if (($ref = waRequest::cookie('referer'))) {
            $params['referer'] = $ref;
            $ref_parts = @parse_url($ref);
            $params['referer_host'] = $ref_parts['host'];
            // try get search keywords
            if (!empty($ref_parts['query'])) {
                $search_engines = array(
                    'text' => 'yandex\.|rambler\.',
                    'q'    => 'bing\.com|mail\.|google\.',
                    's'    => 'nigma\.ru',
                    'p'    => 'yahoo\.com',
                );
                $q_var = false;
                foreach ($search_engines as $q => $pattern) {
                    if (preg_match('/('.$pattern.')/si', $ref_parts['host'])) {
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
                    $params['keyword'] = $query[$q_var];
                }
            }
        }

        if (($utm = waRequest::cookie('utm'))) {
            $utm = json_decode($utm, true);
            if ($utm && is_array($utm)) {
                foreach ($utm as $k => $v) {
                    $params['utm_'.$k] = $v;
                }
            }
        }

        if (($landing = waRequest::cookie('landing')) && ($landing = @parse_url($landing))) {
            if (!empty($landing['query'])) {
                @parse_str($landing['query'], $arr);
                if (!empty($arr['gclid'])
                    && !empty($params['referer_host'])
                    && strpos($params['referer_host'], 'google') !== false
                ) {
                    $params['referer_host'] .= ' (cpc)';
                    $params['cpc'] = 1;
                } elseif (!empty($arr['_openstat'])
                    && !empty($params['referer_host'])
                    && strpos($params['referer_host'], 'yandex') !== false
                ) {
                    $params['referer_host'] .= ' (cpc)';
                    $params['openstat'] = $arr['_openstat'];
                    $params['cpc'] = 1;
                }
            }

            $params['landing'] = $landing['path'];
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
                        $params['abt'.$abtest_id] = $variant_id;
                    }
                }
            }
        }

        $params['ip'] = waRequest::getIp();
        $params['user_agent'] = waRequest::getUserAgent();

        return $params;
    }

}
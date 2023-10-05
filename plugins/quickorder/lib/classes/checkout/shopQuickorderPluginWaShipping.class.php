<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopQuickorderPluginWaShipping extends shopCheckoutShipping
{
    private $cart;

    public function __construct(shopQuickorderPluginCart $cart)
    {
        $this->cart = $cart;
    }

    public function getShippingOptions()
    {
        $config = wa('shop')->getConfig();
        /**
         * @var shopConfig $config
         */

        if (method_exists('shopCheckoutShipping', 'getCheckoutSettings')) {
            $settings = self::getCheckoutSettings();
        } else {
            $settings = $config->getCheckoutSettings();
        }

        $options = array(
            'address_form' => !isset($settings['contactinfo']) || !isset($settings['contactinfo']['fields']['address.shipping']),
            'address' => $this->getAddress(),
            'currencies' => $config->getCurrencies(),
            'current_currency' => $config->getCurrency(false),
            'settings' => array(),
        );

        $settings = $this->getExtendedCheckoutSettings();

        $empty = true;
        foreach ($options['address'] as $v) {
            if ($v) {
                $empty = false;
                break;
            }
        }

        if ($empty) {
            $options['address'] = array();
        }

        if (!$options['address']) {
            $options['shipping_address'] = array();
            $options['address_form'] = true;
            if (!empty($settings['contactinfo']['fields']['address'])) {
                foreach ($settings['contactinfo']['fields']['address']['fields'] as $k => $f) {
                    if (!empty($f['value'])) {
                        $options['shipping_address'][$k] = $f['value'];
                    }
                }
            }
        } else {
            $options['shipping_address'] = $options['address'];
        }

        return $options;
    }

    private function getExtendedCheckoutSettings()
    {
        static $settings = null;
        if ($settings === null) {
            if (method_exists('shopCheckoutShipping', 'getCheckoutSettings')) {
                $settings = self::getCheckoutSettings();
            } else {
                $settings = wa()->getConfig()->getCheckoutSettings();
            }
            if (!isset($settings['contactinfo'])
                ||
                (
                    !isset($settings['contactinfo']['fields']['address.shipping'])
                    && !isset($settings['contactinfo']['fields']['address'])
                )
            ) {
                $config = wa('shop')->getConfig();
                /**
                 * @var shopConfig $config
                 */
                $settings = $config->getCheckoutSettings(true);
            }
        }
        return $settings;
    }

    public function workupShippingMethod($m, $method_options, $selected_shipping = array())
    {
        static $items;
        if ($items === null) {
            $items = $this->getItems();
        }
        static $total;
        if ($total === null) {
            waRequest::setParam('quickorder_ignore_sd', 1);
            $total = $this->cart->getTotal(false);
            waRequest::setParam('quickorder_ignore_sd', 0);
        }

        try {
            $plugin = shopShipping::getPlugin($m['plugin'], $m['id']);

            $m['currency'] = $plugin->allowedCurrency();
            $m['external'] = ($selected_shipping && $selected_shipping['id'] == $m['id']) ? 0 : $plugin->getProperties('external');

            if (empty($m['available'])) {
                $m['rates'] = _w('Not available');
            } elseif ($plugin->isAllowedAddress($method_options['shipping_address'])) {
                if ($m['external']) {
                    $m['rates'] = array();
                } else {
                    $options = array(
                        'currency' => $m['currency'],
                        'weight' => $plugin->allowedWeightUnit(),
                        'dimensions' => $plugin->allowedLinearUnit(),
                    );
                    if (method_exists('shopHelper', 'workupOrderItems')) {
                        $shipping_items = shopHelper::workupOrderItems($items, $options);
                    } else {
                        $shipping_items = shopQuickorderPluginMigrate::workupOrderItems($items, $options);
                    }

                    if (method_exists('shopHelper', 'workupValue')) {
                        $total_price = shopHelper::workupValue($total, 'price', $options['currency']);
                    } else {
                        $total_price = shopQuickorderPluginMigrate::workupValue($total, 'price', $options['currency']);
                    }

                    $params = $this->getPluginShippingParams($plugin, $m, $items, $total_price);

                    $m['rates'] = $plugin->getRates($shipping_items, $method_options['shipping_address'], $params);
                }
            } else {
                $m['rates'] = false;
            }

            if (is_array($m['rates'])) {
                if (!isset($method_options['currencies'][$m['currency']])) {
                    $m['rate'] = 0;
                    $format = _w('Shipping rate was not calculated because required currency %s is not defined in your store settings.');
                    $m['error'] = sprintf($format, $m['currency']);
                    return $m;
                }

                foreach ($m['rates'] as &$r) {
                    if (is_array($r['rate'])) {
                        $r['rate'] = max($r['rate']);
                    }

                    // Apply rounding. This converts all rates to current frontend currency.
                    if ($r['rate'] && wa()->getSetting('round_shipping')) {
                        $r['rate'] = shopRounding::roundCurrency(
                            shop_currency($r['rate'], $m['currency'], $method_options['current_currency'], false),
                            $method_options['current_currency']
                        );
                        $r['currency'] = $method_options['current_currency'];
                    }
                }
                unset($r);

                if (wa()->getSetting('round_shipping')) {
                    $m['currency'] = $method_options['current_currency'];
                }

                if ($m['rates']) {
                    if (!empty($selected_shipping['rate_id']) && isset($m['rates'][$selected_shipping['rate_id']])) {
                        $rate = $m['rates'][$selected_shipping['rate_id']];
                    } else {
                        $rate = reset($m['rates']);
                    }
                    $m['rate'] = $rate['rate'];
                    $m['est_delivery'] = isset($rate['est_delivery']) ? $rate['est_delivery'] : '';
                    if (!empty($rate['comment'])) {
                        $m['comment'] = $rate['comment'];
                    }
                } else {
                    $m['rates'] = array();
                    $m['rate'] = null;
                }
            } elseif (is_string($m['rates'])) {
                if ($method_options['address']) {
                    $m['error'] = $m['rates'];
                } else {
                    $m['rates'] = array();
                    $m['rate'] = null;
                }
            } else {
                return null;
            }

            // When free shipping coupon is used, display all rates as 0
            $checkout_data = wa('shop')->getStorage()->read('shop/checkout');
            if (!empty($checkout_data['coupon_code']) && ($m['rate'] !== null)) {
                empty($cm) && ($cm = new shopCouponModel());
                $coupon = $cm->getByField('code', $checkout_data['coupon_code']);
                if ($coupon && $coupon['type'] == '$FS') {
                    $m['rate'] = 0;
                    foreach ($m['rates'] as &$r) {
                        $r['rate'] = 0;
                    }
                    unset($r);
                }
            }

            $custom_fields = $this->getCustomFields($m['id'], $plugin);
            $custom_html = '';
            foreach ($custom_fields as $c) {
                $custom_html .= '<div class="wa-field">' . $c . '</div>';
            }
            if ($custom_html) {
                $m['custom_html'] = $custom_html;
            }

            $f = $this->getAddressForm($m['id'], $plugin, null, $method_options['address'], $plugin->requestedAddressFields() ? true : false);
            if ($f) {
                $cache_contact_changed = new waRuntimeCache('quickorder_cache_contact_changed');
                $cache_contact_changed->set(1);
                $m['form'] = $f;
                $m['form']->setValue($this->cart->getContact());
                // Make sure there are no more than one address of each type in the form
                foreach (array('address.shipping') as $fld) {
                    if (isset($m['form']->values[$fld]) && count($m['form']->values[$fld]) > 1) {
                        $m['form']->values[$fld] = array(reset($m['form']->values[$fld]));
                    }
                }
            }

            return $m;
        } catch (Exception $ex) {
            waLog::log($ex->getMessage(), 'shop/checkout.error.log');
            return null;
        }
    }

    /**
     * @param int $id
     * @param string $rate_id
     * @param waContact $contact
     * @return array|false|string
     * @throws Exception
     */
    public function getRate($id = null, &$rate_id = null, $contact = null)
    {
        if (!$id) {
            $shipping = $this->cart->getStorage()->getSessionData('shipping');
            if (!$shipping) {
                return array();
            }
            $id = $shipping['id'];
            $rate_id = $shipping['rate_id'];
        }

        if (!$contact) {
            $contact = $this->cart->getContact();
        }

        if (!isset($this->plugin_model)) {
            $this->plugin_model = new shopPluginModel();
        }

        $plugin_info = $this->plugin_model->getById($id);

        try {
            $plugin = shopShipping::getPlugin($plugin_info['plugin'], $id);
        } catch (waException $ex) {
            return false;
        }

        $total = $this->cart->getTotal(false);
        $currency = $plugin->allowedCurrency();

        $shop_config = wa('shop')->getConfig();
        /**
         * @var shopConfig $shop_config
         */
        $current_currency = $shop_config->getCurrency(false);
        /**
         * @var string $current_currency
         */
        if ($currency != $current_currency) {
            $total = shop_currency($total, $current_currency, $currency, false);
        }

        $items = $this->getItems($plugin->allowedWeightUnit());

        $params = $this->getPluginShippingParams($plugin, $plugin_info, $items, $total);

        if (method_exists('shopShipping', 'convertItemsDimensions')) {
            shopShipping::convertItemsDimensions($items, $plugin);
        }

        $rates = $plugin->getRates($items, $this->getAddress($contact), $params);
        if (!$rates) {
            return false;
        }
        if (is_string($rates)) {
            return $rates;
        }
        if ($rate_id === null) {
            $rate_id = key($rates);
        }
        if (isset($rates[$rate_id])) {
            $result = $rates[$rate_id];
        } elseif (!in_array($rate_id, array(null, false), true)) {
            return _w('Shipping option is not defined. Please return to the shipping option checkout step to continue.');
        } else {
            $result = array('rate' => 0);
        }
        if ($result['rate']) {
            if (is_array($result['rate'])) {
                $result['rate'] = max($result['rate']);
            }

            // if $current_currency == $currency it's will be rounded to currency precision
            if ($result['rate']) {
                $result['rate'] = shop_currency($result['rate'], $currency, $current_currency, false);
            }

            // rounding
            if ($result['rate'] && wa()->getSetting('round_shipping')) {
                $result['rate'] = shopRounding::roundCurrency($result['rate'], $current_currency);
            }
        }
        $result['plugin'] = $plugin->getId();
        $result['name'] = $plugin_info['name'] . (!empty($result['name']) ? ' (' . $result['name'] . ')' : '');
        $result['tax_id'] = ifset($plugin_info['options']['tax_id']);
        $result['params'] = ifempty($params, 'shipping_params', array());
        return $result;
    }

    public function getItems($weight_unit = null)
    {
        $items = array();
        $cart_items = $this->cart->getItems();

        $units = array(
            'weight' => true,
            'dimensions' => true,
        );
        #get actual order items weight
        if (method_exists('shopShipping', 'extendItems')) {
            shopShipping::extendItems($cart_items, $units);
        } else {
            shopQuickorderPluginMigrate::extendShippingItems($cart_items, $weight_unit);
        }

        foreach ($cart_items as $item) {
            $items[] = [
                'name' => $item['name'],
                'price' => $item['price'],
                'currency' => $item['currency'],
                'quantity' => $item['quantity'],
                'type' => ifset($item, 'type', null),
                'product_id' => ifset($item, 'product_id', null),
                'sku_id' => ifset($item, 'sku_id', null),
                'service_id' => ifset($item, 'service_id', null),
                'service_variant_id' => ifset($item, 'service_variant_id', null),
                'weight' => ifset($item, 'weight', ifset($item, '_weight', null)),
                'height' => ifset($item, 'height', null),
                'width' => ifset($item, 'width', null),
                'length' => ifset($item, 'length', null),
            ];
        }
        return $items;
    }

    /**
     * @param waContact $contact
     * @return array
     * @throws waException
     */
    public function getAddress($contact = null)
    {
        static $address;

        if ($address === null) {
            if ($contact === null) {
                $contact = $this->cart->getContact();
            }
            if (!$contact) {
                return array();
            }
            $address = $contact->getFirst('address.shipping');
            if (!$address) {
                $address = $contact->getFirst('address');
            }
        }
        if ($address) {
            return $address['data'];
        } else {
            return array();
        }
    }

    private $shipping_params = null;

    protected function getPluginShippingParams($plugin, $plugin_info, $items, $total_price)
    {
        $params = ['shipping_params' => null];
        if ($this->shipping_params === null) {
            $this->shipping_params = method_exists('shopShipping', 'getItemsTotal') ? shopShipping::getItemsTotal($items) : [];
        }
        if (method_exists('shopShipping', 'workupShippingParams')) {
            $params = shopShipping::workupShippingParams($this->shipping_params, $plugin, $plugin_info);
        }
        $params['total_price'] = $total_price;

        # extendShippingParams
        if ($shipping_params = waRequest::request('shipping_' . $plugin_info['id'])) {
            $params['shipping_params'] = $shipping_params;
        } else {
            $shipping = $this->cart->getStorage()->getSessionData('shipping', array());
            if (ifset($shipping['id']) == $plugin_info['id']) {
                $session_params = $this->cart->getStorage()->getSessionData('params', array());
                $params['shipping_params'] = ifset($session_params['shipping']);
            }
        }

        return $params;
    }

    protected function getCustomFields($id, waShipping $plugin)
    {
        $contact = $this->cart->getContact();
        $order_params = $this->cart->getStorage()->getSessionData('params', array());
        $shipping_params = isset($order_params['shipping']) ? $order_params['shipping'] : array();
        foreach ($shipping_params as $k => $v) {
            $order_params['shipping_params_' . $k] = $v;
        }
        $order = new waOrder(array(
            'contact' => $contact,
            'contact_id' => $contact ? $contact->getId() : null,
            'params' => $order_params,
        ));
        $custom_fields = $plugin->customFields($order);
        if (!$custom_fields) {
            return $custom_fields;
        }

        $selected_shipping = $this->cart->getStorage()->getSessionData('shipping');
        $selected = $selected_shipping ? ($id == $selected_shipping['id']) : false;

        if ($selected) {
            foreach ($custom_fields as $name => &$row) {
                if (isset($shipping_params[$name])) {
                    $row['value'] = $shipping_params[$name];
                }
            }
        }

        if (method_exists($this, 'getControls')) {
            return $this->getControls($custom_fields, 'shipping_' . $id);
        } else {
            return (new shopQuickorderPluginMigrate())->getControls($custom_fields, 'shipping_' . $id);
        }
        return $controls;
    }

    public function getSelectedMethod()
    {
        if (waRequest::method() == 'post') {
            $shipping_id = waRequest::post('shipping_id');
            $rate_id = waRequest::post('rate_id');
            $selected_shipping = array(
                'id' => $shipping_id,
                'rate_id' => isset($rate_id[$shipping_id]) ? $rate_id[$shipping_id] : '',
            );
            $this->cart->getStorage()->setSessionData('shipping', array(
                'id' => $selected_shipping['id'],
                'rate_id' => $selected_shipping['rate_id'],
                'name' => '',
                'plugin' => ''
            ));
        } else {
            $selected_shipping = $this->cart->getStorage()->getSessionData('shipping', array());
        }
        return $selected_shipping;
    }

    /**
     * @param int $shipping_id
     * @param array $items
     * @param array $address
     * @param float $total
     * @param bool $save_keys
     * @return array|mixed|string
     * @throws waException
     */
    public function getSingleShippingRates($shipping_id, $items, $address, $total, $save_keys = false)
    {
        try {
            //XXX use shopCheckoutShipping class
            $plugin_info = shopShipping::getPluginInfo($shipping_id);
            $plugin = shopShipping::getPlugin($plugin_info['plugin'], $plugin_info['id']);

            $shipping_params = method_exists('shopShipping', 'getItemsTotal') ? shopShipping::getItemsTotal($items) : [];

            $params = method_exists('shopShipping', 'workupShippingParams') ? shopShipping::workupShippingParams($shipping_params, $plugin, $plugin_info) : [];
            $params['shipping_params'] = [];

            # convert dimensions
            if (method_exists('shopShipping', 'convertItemsDimensions')) {
                shopShipping::convertItemsDimensions($items, $plugin);
            } else {
                shopQuickorderPluginMigrate::convertItemsDimensions($items, $plugin);
            }

            $currency = $plugin->allowedCurrency();
            $config = wa('shop')->getConfig();
            /**
             * @var shopConfig $config
             */
            $current_currency = $config->getCurrency(false);
            if ($currency != $current_currency) {
                $total = shop_currency($total, $current_currency, $currency, false);
            }

            foreach ($items as &$item) {
                if (!empty($item['currency'])) {
                    if ($item['currency'] != $currency) {
                        $item['price'] = shop_currency($item['price'], $item['currency'], $currency, false);
                    }
                    unset($item['currency']);
                }
            }
            unset($item);

            $params['total_price'] = $total;

            if ($shipping_params_request = waRequest::request('shipping_' . $shipping_id)) {
                $params['shipping_params'] = $shipping_params_request;
            }

            $rates = $plugin->getRates($items, $address, $params);

            return $this->formatRates($rates, $current_currency, $save_keys);
        } catch (waException $ex) {
            return $ex->getMessage();
        }
    }

    protected function formatRates($rates, $current_currency, $save_keys)
    {
        if ($rates instanceof waShipping) {
            $rates = $rates->getPromise();
        }
        if (is_array($rates)) {
            $is_html = waRequest::request('html', 1);
            // When free shipping coupon is used, display all rates as 0
            $checkout_data = wa('shop')->getStorage()->read('shop/checkout');
            $free_shipping = false;
            if (!empty($checkout_data['coupon_code'])) {
                empty($cm) && ($cm = new shopCouponModel());
                $coupon = $cm->getByField('code', $checkout_data['coupon_code']);
                if ($coupon && $coupon['type'] == '$FS') {
                    $free_shipping = true;
                }
            }
            foreach ($rates as $r_id => &$r) {
                $r['id'] = $r_id;
                if (!isset($r['rate'])) {
                    $r['rate'] = null;
                } elseif (is_array($r['rate'])) {
                    if ($r['rate']) {
                        $r['rate'] = max($r['rate']);
                    } else {
                        $r['rate'] = null;
                    }
                }
                if ($r['rate'] !== null) {
                    if ($free_shipping) {
                        $r['rate'] = 0;
                    }
                    $round_shipping = wa()->getSetting('round_shipping');
                    // Apply rounding. This converts all rates to current frontend currency.
                    if ($r['rate'] && $round_shipping) {
                        $r['rate'] = shopRounding::roundCurrency(shop_currency($r['rate'], $r['currency'], $current_currency, false), $current_currency);
                        $r['currency'] = $current_currency;
                    }

                    $r['rate_html'] = $is_html ? shop_currency_html($r['rate'], $r['currency']) : shop_currency($r['rate'], $r['currency']);
                    $r['rate'] = shop_currency($r['rate'], $r['currency'], null, false);
                }
            }
            unset($r);
            return $save_keys ? $rates : array_values($rates);
        } elseif (!$rates) {
            return _w('Not available');
        } else {
            return $rates;
        }
    }

}
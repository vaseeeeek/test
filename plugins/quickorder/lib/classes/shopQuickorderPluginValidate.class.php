<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopQuickorderPluginValidate
{
    private $errors = array();
    private $settings = array();
    private $cart;
    private $order;
    private $phone_mask;
    private $email_validation;

    public function __construct(shopQuickorderPluginOrder $order)
    {
        $this->settings = shopQuickorderPluginHelper::getSettings();
        $this->order = $order;
        $this->cart = $this->order->getCart();
    }

    /**
     * Go through all validations
     *
     * @return bool
     * @throws Exception
     */
    public function isValid()
    {
        $this->requiredFilter();
        $this->othersFilter();

        $shipping = $this->cart->getMethods('shipping');
        if (!empty($shipping['methods'])) {
            $this->shippingFilter();
        }
        $payment = $this->cart->getMethods('payment');
        if (!empty($payment['methods'])) {
            $this->paymentFilter($payment['methods']);
        }

        if (!$this->fieldsFilter($this->order->getFields())) {
            $this->addError(null, _wp('Contact fields are empty'));
        }

        $this->minimalFilter();

        if (!$this->errors) {
            $captcha = !empty($this->settings['shared_display_settings']) ? !empty($this->settings['product']['captcha']) : !empty($this->settings[$this->cart->getType()]['captcha']);
            // Капча
            if ($captcha && !wa()->getCaptcha()->isValid()) {
                $this->addError(null, _wp('Captcha is not correct'));
            }
        }

        return $this->errors ? false : true;
    }

    /**
     * Get all errors
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Add error to array
     *
     * @param string $field_id
     * @param string $message
     */
    public function addError($field_id, $message)
    {
        if ($field_id) {
            $this->errors[$field_id] = $message;
        } else {
            $this->errors[] = $message;
        }
    }

    /**
     * Required filter
     */
    private function requiredFilter()
    {
        /* Проверка обязательных полей формы */
        $json_fields = !empty($this->settings['shared_display_settings']) ? $this->settings['fields']['product'] : $this->settings['fields'][$this->cart->getType()];
        $fields = $this->prepareContactFields(shopQuickorderPluginHelper::decodeToArray($json_fields));

        $post_fields = $this->order->getFields();

        foreach ($fields as $f) {
            if (!empty($f['required']) && empty($post_fields[$f['type']])) {
                $this->addError($f['type'], _wp('Field is required'));
            }
            // Проверяем существует ли поле "Телефон", чтобы выполнить для него проверку по маске
            if ($f['type'] == 'phone' && !empty($f['extra']) && !empty($post_fields[$f['type']])) {
                $this->phone_mask = $f['extra'];
            }
            // Проверяем существует ли поле "Email", чтобы выполнить для него проверку
            if ($f['type'] == 'email' && !empty($post_fields[$f['type']])) {
                $this->email_validation = 1;
            }
        }
        if (!empty($this->errors)) {
            $this->addError('common', _wp('Fix the errors above'));
        }
    }

    /**
     * Filter fields, if someone is trying to fake them
     *
     * @param array $fields
     * @return array
     */
    public function fieldsFilter($fields)
    {
        foreach ($fields as $k => $f) {
            if (!$this->isFieldExist($k)) {
                unset($fields[$k]);
            }
        }
        return $fields;
    }

    /**
     * Phone mask validation
     */
    private function maskFilter()
    {
        $post_fields = $this->order->getFields();

        $phone = '';
        if (!empty($post_fields['phone'][0]['value'])) {
            $phone = $post_fields['phone'][0]['value'];
        }

        if ($phone) {
            $regexp = "/^" . preg_replace(array("/([-[\]{}()*+?.,\\^$|#\/])/", "/\s/", "/[0]/", "/[Z]/"), array('\\\$0', "\s", "\\d", "0"), $this->phone_mask) . "$/";
            if (!preg_match($regexp, $phone)) {
                $this->addError('phone', _wp("Print correct phone format") . ": " . $this->phone_mask);
                $this->addError('common', _wp('Fix the errors above'));
            }
        }
    }

    /**
     * Email validation
     */
    private function emailFilter()
    {
        $post_fields = $this->order->getFields();

        $email = $post_fields['email'];

        $validator = new waEmailValidator();
        if (is_array($email)) {
            foreach ($email as $f) {
                if (!$validator->isValid($f['value'])) {
                    $this->addError('email', _wp('Incorrect email'));
                    $this->addError('common', _wp('Fix the errors above'));
                    return;
                }
            }
        } else {
            if (!$validator->isValid($email)) {
                $this->addError('email', _wp('Incorrect email'));
                $this->addError('common', _wp('Fix the errors above'));
                return;
            }
        }
    }

    /**
     * Others validation
     */
    private function othersFilter()
    {
        // Принятие условий соглашения
        $terms = $this->cart->getData('terms');
        if (!empty($this->settings['terms']) && empty($terms)) {
            $this->addError('terms', ifempty($this->settings, 'terms_error', _wp('Terms and agreement')));
        }

        // Наличие товаров
        if (!$this->cart->getItems()) {
            $this->addError(null, _wp('Your order is empty'));
        }

        // Проверка email
        if ($this->email_validation) {
            $this->emailFilter();
        }

        // Маска телефона
        if ($this->phone_mask) {
            $this->maskFilter();
        }
    }

    /**
     * Shipping validation
     *
     * @throws waException
     */
    private function shippingFilter()
    {
        if ($shipping_id = waRequest::post('shipping_id')) {
            $shipping = new shopQuickorderPluginWaShipping($this->cart);
            $total = $this->cart->getTotal(false);
            $items = $shipping->getItems();
            $customer = waRequest::post('customer_' . $shipping_id);
            $address = ifset($customer['address.shipping'], $shipping->getAddress());

            $plugin = shopShipping::getPlugin(null, $shipping_id);
            $f = $shipping->getAddressForm($shipping_id, $plugin, null, $address, $plugin->requestedAddressFields() ? true : false);
            // Проверка обязательных полей
            if ($f && (!($f instanceof waContactForm) || !$f->isValid())) {
                $this->addError(null, _wp('Shipping method has errors. Please, fix them.') . "<br>" . $this->getShippingErrors($f->errors('address.shipping')));
            } else {
                $rates = $shipping->getSingleShippingRates($shipping_id, $items, $address, $total);
                if (!isset($rates[0]['rate']) || $rates[0]['rate'] === null) {
                    $this->addError(null, _wp('Shipping method has errors. Please, fix them.') . (is_string($rates) ? '<br>' . $rates : ''));
                }
            }
        } else {
            $this->addError(null, _wp('Shipping method has errors. Please, fix them.'));
        }
    }

    /**
     * Payment validation
     *
     * @param array $payment_methods
     */
    private function paymentFilter($payment_methods)
    {
        if ($payment_id = $this->cart->getData('payment_id')) {
            if (!isset($payment_methods[$payment_id])) {
                $this->addError(null, _wp('Payment method has errors.'));
            }
        } else {
            $this->addError(null, _wp('Payment method has errors.'));
        }
    }

    /**
     * Minimal sum validation
     *
     * @throws Exception
     */
    private function minimalFilter()
    {
        // Минимальная сумма
        if (!empty($this->settings['minimal']['price'])) {
            $minimal_price_primary = shopQuickorderPluginHelper::floatVal($this->settings['minimal']['price']);
            $minimal_price = shop_currency($minimal_price_primary, null, null, false);
            $total_price = $this->cart->getTotal(false);
            if ($minimal_price > $total_price) {
                $this->addError(null, sprintf(_wp('Minimal sum of order is %s'), shop_currency($minimal_price_primary)));
            }
        }

        if (!empty($this->settings['minimal']['product_sum']) || !empty($this->settings['minimal']['product_quantity']) || !empty($this->settings['minimal']['total_quantity'])) {
            $items = $this->cart->getItems();
        }

        // Минимальная сумма каждого товара
        if (!empty($this->settings['minimal']['product_sum']) && !empty($items)) {
            $products = array();
            foreach ($items as $k => $i) {
                if ($i['type'] == 'product') {
                    $products[$k] = $i['price'] * $i['quantity'];
                } elseif (isset($products[$i['parent_id']])) {
                    $products[$i['parent_id']] += $i['price'] * $i['quantity'];
                }
            }
            foreach ($products as $p) {
                $minimal_price_primary = shopQuickorderPluginHelper::floatVal($this->settings['minimal']['product_sum']);
                $minimal_price = shop_currency($minimal_price_primary, null, null, false);
                if ($minimal_price > $p) {
                    $this->addError(null, _wp('Minimal sum of each product is') . " " . shop_currency($minimal_price_primary));
                    break;
                }
            }
        }

        //  Минимальное общее количество товаров
        if (!empty($this->settings['minimal']['total_quantity']) && !empty($items)) {
            $total_quantity = 0;
            foreach ($items as $k => $i) {
                if ($i['type'] == 'product') {
                    $total_quantity += $i['quantity'];
                }
            }
            $minimal_quantity = shopQuickorderPluginHelper::floatVal($this->settings['minimal']['total_quantity']);
            if ($minimal_quantity > $total_quantity) {
                $this->addError(null, _wp('Minimal quantity of products is') . " " . $minimal_quantity);
            }
        }

        //  Минимальное количество каждого товара
        if (!empty($this->settings['minimal']['product_quantity']) && !empty($items)) {
            $minimal_product_quantity = shopQuickorderPluginHelper::floatVal($this->settings['minimal']['product_quantity']);
            foreach ($items as $k => $i) {
                if ($i['type'] == 'product' && $minimal_product_quantity > $i['quantity']) {
                    $this->addError(null, _wp('Minimal quantity of each product is') . " " . $minimal_product_quantity);
                }
            }
        }
    }

    /**
     * Check, if field is really exist
     *
     * @param string $field_id
     * @return bool
     * @throws waException
     */
    private function isFieldExist($field_id)
    {
        static $all_fields;
        static $address_fields;

        if ($all_fields === null) {
            $all_fields = waContactFields::getAll();
            $address_fields = !empty($all_fields['address']) ? $all_fields['address']->getFields() : array();
        }

        if (strpos($field_id, "::")) {
            $parts = explode("::", $field_id);
            $field_type = $parts[0];
            $field_id = $parts[1];
        } else {
            $field_type = "contact";
        }

        if (
            ($field_type == 'contact' && isset($all_fields[$field_id])) ||
            ($field_type == 'address' && isset($address_fields[$field_id]))
        ) {
            return true;
        }

        return false;
    }

    /**
     * Prepare contact fields
     *
     * @param array $fields
     * @return array
     */
    private function prepareContactFields($fields)
    {
        $data = array();
        if (is_array($fields)) {
            foreach ($fields as $f) {
                $field = array();
                foreach ($f as $v) {
                    $field[$v['name']] = $v['value'];
                }
                if (isset($field['type']) && strpos($field['type'], 'address::') !== false) {
                    $field['address_type'] = substr($field['type'], 9);
                }
                $data[] = $field;
            }
        }
        return $data;
    }

    /**
     * Get all shipping errors
     *
     * @param string $errors
     * @return string
     */
    private function getShippingErrors($errors)
    {
        $error_msg = '';
        foreach ($errors as $error) {
            if (is_array($error)) {
                foreach ($error as $e) {
                    if (is_string($e)) {
                        $error_msg .= ' ' . $e;
                    }
                }
            } elseif (is_string($error)) {
                $error_msg .= ' ' . $error;
            }
        }
        return $error_msg;
    }
}
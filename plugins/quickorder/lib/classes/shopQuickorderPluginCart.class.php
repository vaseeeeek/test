<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopQuickorderPluginCart
{
    private $code;
    private $contact;
    private $type;
    private $items;
    private $post_data = array();
    private $error;

    public function __construct($type = 'product', $items = array())
    {
        $this->type = $type;
        if (waRequest::request('quickorder_fields')) {
            if (!$this->items) {
                $items = waRequest::request('products', array());
            }
            $this->setContact(waRequest::request('quickorder_fields', array()));
            // Бонусы
            if ($post_affiliate = waRequest::request('quickorder_affiliate', array())) {
                $data = wa()->getStorage()->get('shop/checkout');
                if (!empty($post_affiliate['use'])) {
                    $data['use_affiliate'] = 1;
                } elseif (!empty($post_affiliate['cancel']) && isset($data['use_affiliate'])) {
                    unset($data['use_affiliate']);
                }
                wa()->getStorage()->set('shop/checkout', $data);
                wa()->getStorage()->remove('shop/cart');
            }
        }
        $this->post_data = waRequest::request('quickorder', array());
        $this->prepareItems($items);
        $this->contact = $this->getContact();
    }

    /**
     * Add first product to default cart
     */
    public function addProductToDefaultCart()
    {
        $cart = new shopCart();
        $cart_items_model = new shopCartItemsModel();
        $item = reset($this->items);
        // Если товара нет в корзине, только тогда добавляем
        if (!$cart_items_model->getSingleItem($cart->getCode(), $item['id'], $item['sku_id'])) {
            $item['product_id'] = $item['id'];
            $quantity = $this->checkCartItemQuantity($item);
            if ($quantity > 0) {
                $cart->addItem(array(
                    'type' => 'product',
                    'product_id' => $item['id'],
                    'sku_id' => $item['sku_id'],
                    'quantity' => $quantity,
                ));
            }
        }
    }

    /**
     * Prepare items.
     * $_POST['products'] - serialized string. It is decoded to array and save to $this->items.
     * If we are working with cart - change cart session
     *
     * @param string|array $items
     */
    private function prepareItems($items)
    {
        if (is_string($items)) {
            $decoded_items = shopQuickorderPluginHelper::decodeToArray(json_decode($items));
            $items = array();
            if (is_array($decoded_items)) {
                foreach ($decoded_items as $k => $item) {
                    $parse_str = array();
                    parse_str($item, $parse_str);
                    if (isset($parse_str['quickorder_product'])) {
                        $items[$k] = $parse_str['quickorder_product'];
                    }
                }
            }
        } elseif (is_array($items)) {
            foreach ($items as $k => $item) {
                // Удаляем список услуг. Необходимо, если данные передаются со страницы товара
                if (isset($item['services'])) {
                    unset($items[$k]['services']);
                }
            }
        }
        $this->items = $items;

        // Если были переданные новые данные о товарах, и обрабатывается корзина, производим замену основной корзины
        if ($this->type == 'cart' && waRequest::issetPost('products')) {
            $cart_skus = $skus = $cart_services = $skus_services = array();
            $shop_cart = new shopCart();
            waRequest::setParam('flexdiscount_skip_frontend_products', 1);
            $cart_items = $shop_cart->items();
            waRequest::setParam('flexdiscount_skip_frontend_products', 0);

            foreach ($cart_items as $c) {
                $cart_skus[$c['sku_id']] = $c['id'];
                if (!empty($c['services'])) {
                    foreach ($c['services'] as $cs) {
                        $cart_services[$c['sku_id']][$cs['service_id']] = $cs;
                    }
                }
            }

            foreach ($this->items as $k => $i) {
                $skus[$i['sku_id']] = $k;
                if (!empty($i['services'])) {
                    foreach ($i['services'] as $ss) {
                        $skus_services[$i['sku_id']][$ss] = $i['service_variant'][$ss];
                    }
                }
            }

            foreach ($skus as $sku_id => $item_id) {
                $item = $this->items[$item_id];
                $item['quantity'] = $this->checkCartItemQuantity($item);

                // Если товар уже имеется в корзине
                if (isset($cart_skus[$sku_id])) {
                    $cart_item = $cart_items[$cart_skus[$sku_id]];

                    // Проверяем количество
                    if (shopQuickorderPluginHelper::floatVal($cart_item['quantity']) !== shopQuickorderPluginHelper::floatVal($item['quantity'])) {
                        $shop_cart->setQuantity($cart_item['id'], $item['quantity']);
                    }

                    // Проверяем услуги
                    if (isset($skus_services[$sku_id])) {
                        foreach ($skus_services[$sku_id] as $service_id => $variant_id) {
                            // Если услуга в корзине уже имеется, удаляем ее из массива, чтобы пропустить дальнейшую обработку
                            if (isset($cart_services[$sku_id][$service_id]) && $cart_services[$sku_id][$service_id]['service_variant_id'] == $variant_id) {
                                unset($cart_services[$sku_id][$service_id]);
                            } elseif (!isset($cart_services[$sku_id][$service_id]) || (isset($cart_services[$sku_id][$service_id]) && $cart_services[$sku_id][$service_id]['service_variant_id'] !== $variant_id)) {
                                // Добавляем новую услугу к текущему товару в корзине
                                $shop_cart->addItem(array('product_id' => $item['product_id'], 'sku_id' => $sku_id, 'parent_id' => $cart_item['id'], 'type' => 'service', 'service_id' => $service_id, 'service_variant_id' => $variant_id, 'quantity' => $item['quantity']));
                            } else {
                                // Удаляем услуги
                                if (isset($cart_services[$sku_id][$service_id])) {
                                    $shop_cart->deleteItem($cart_services[$sku_id][$service_id]['id']);
                                    unset($cart_services[$sku_id][$service_id]);
                                }
                            }
                        }
                    }
                    // Удаляем услуги
                    if (!empty($cart_services[$sku_id])) {
                        foreach ($cart_services[$sku_id] as $s) {
                            $shop_cart->deleteItem($s['id']);
                        }
                    }
                    unset($cart_skus[$sku_id]);
                } // Если товара в корзине нет, добавляем его
                else {
                    // Услуги
                    $services = array();
                    if (isset($skus_services[$sku_id])) {
                        foreach ($skus_services[$sku_id] as $service_id => $variant_id) {
                            $services[] = array(
                                'service_id' => $service_id,
                                'service_variant_id' => $variant_id
                            );
                        }
                    }
                    $shop_cart->addItem(array('product_id' => $item['product_id'], 'sku_id' => $sku_id, 'type' => 'product', 'quantity' => $item['quantity']), $services);
                }
            }

            // Удаляем товары из корзины, которые не были обработаны
            foreach ($cart_skus as $cs) {
                $shop_cart->deleteItem($cs);
            }
        }
    }

    /**
     * Get available quantity of item
     *
     * @param array $item
     * @return int|float
     */
    private function checkCartItemQuantity($item)
    {
        $q = $item['quantity'];

        if (!wa()->getSetting('ignore_stock_count')) {
            $product_model = new shopProductModel();
            $p = $product_model->getById($item['product_id']);
            $sku_model = new shopProductSkusModel();
            $sku = $sku_model->getById($item['sku_id']);

            // limit by main stock
            if (wa()->getSetting('limit_main_stock') && waRequest::param('stock_id')) {
                $stock_id = waRequest::param('stock_id');
                $product_stocks_model = new shopProductStocksModel();
                $sku_stock = shopHelper::fillVirtulStock($product_stocks_model->getCounts($sku['id']));
                if (isset($sku_stock[$stock_id])) {
                    $sku['count'] = $sku_stock[$stock_id];
                }
            }
            // check quantity
            if ($sku['count'] !== null && $q > $sku['count']) {
                $q = $sku['count'];
                $name = $p['name'] . ($sku['name'] ? ' (' . $sku['name'] . ')' : '');
                if ($q > 0) {
                    $this->error = sprintf(_w('Only %d pcs of %s are available, and you already have all of them in your shopping cart.'), $q, $name);
                } else {
                    $this->error = sprintf(_w('Oops! %s just went out of stock and is not available for purchase at the moment. We apologize for the inconvenience.'), $name);
                }
                return $q;
            }
        }
        return $q;
    }

    /**
     * Make sure that product has all necessary fields
     *
     * @param array $product
     * @param array $item - cart item
     * @return array
     * @throws waException
     */
    private function processProduct($product, $item)
    {
        $quantity = !empty($item['quantity']) ? (float) $item['quantity'] : (!empty($product['quantity']) ? (float) $product['quantity'] : 1);
        $product_id = !empty($item['product_id']) ? $item['product_id'] : (!empty($product['product_id']) ? $product['product_id'] : (!empty($product['id']) ? $product['id'] : 0));
        $sku_id = !empty($item['sku_id']) ? (float) $item['sku_id'] : (!empty($product['sku_id']) ? $product['sku_id'] : 0);
        $product['product_id'] = $product_id;
        $product['sku_id'] = $sku_id;
        $product['quantity'] = $quantity > 0 ? $quantity : 1;
        // Игнорируем изменение цены у Гибких скидок
        $products = (new shopQuickorderPluginProductData())->prepareProducts([ifset($item, 'id', 0) => $product], false, true);
        $product = reset($products);
        $product['product'] = $product;
        // Приводим данные товара к формату, необходимому для order_calculate_discount
        return $this->prepareCartItem($product);
    }

    /**
     * Get order items
     *
     * @return array
     * @throws waException
     */
    public function getItems()
    {
        static $items = [];
        if (isset($items[$this->type])) {
            return $items[$this->type];
        }

        $items[$this->type] = [];

        if ($this->type == 'product') {
            $items[$this->type] = $this->getItemsForProduct();
        } else {
            $items[$this->type] = $this->getItemsForCart();
        }

        return $items[$this->type];
    }

    /**
     * Get items if we are working with product
     *
     * @return array
     * @throws waException
     */
    private function getItemsForProduct()
    {
        $items = $sku_ids = array();
        $rounding_enabled = shopRounding::isEnabled();
        foreach ($this->items as $item) {
            $product_id = !empty($item['product_id']) ? $item['product_id'] : (!empty($item['id']) ? $item['id'] : 0);
            // Товар
            $product = (new shopQuickorderPluginHelper())->getProduct((int) $product_id);
            if ($product) {
                $processed_item = $this->processProduct($product, $item);
                $sku_ids[] = $processed_item['sku_id'];
                $items[$processed_item['id']] = $processed_item;

                // Услуги
                if (!empty($item['services']) || !empty($item['active_services'])) {
                    $service_item = array(
                        "product_id" => $processed_item['product_id'],
                        "sku_id" => $processed_item['sku_id'],
                        "parent_id" => $processed_item['id'],
                        "quantity" => $processed_item['quantity'],
                        "product" => $processed_item,
                    );
                    $variant_ids = $service_ids = array();
                    if (empty($item['services'])) {
                        $item['service_variant'] = $item['active_services'];
                        $item['services'] = array_keys($item['active_services']);
                    }
                    foreach ($item['services'] as $service_id) {
                        $service_ids[$service_id] = $service_id;
                        if (isset($item['service_variant'][$service_id])) {
                            $variant_ids[] = $item['service_variant'][$service_id];
                        }
                    }

                    $service_model = new shopServiceModel();
                    $services = $service_model->getByField('id', $service_ids, 'id');
                    $rounding_enabled && shopRounding::roundServices($services);

                    $service_variants_model = new shopServiceVariantsModel();
                    $variants = $service_variants_model->getByField('id', $variant_ids, 'id');
                    $rounding_enabled && shopRounding::roundServiceVariants($variants, $services);

                    $product_services_model = new shopProductServicesModel();
                    $rows = $product_services_model->getByProducts($processed_item['product_id']);
                    $rounding_enabled && shopRounding::roundServiceVariants($rows, $services);

                    $product_services = $sku_services = array();
                    foreach ($rows as $row) {
                        if ($row['sku_id'] && !in_array($row['sku_id'], $sku_ids)) {
                            continue;
                        }
                        if (!$row['sku_id']) {
                            $product_services[$row['product_id']][$row['service_variant_id']] = $row;
                        }
                        if ($row['sku_id']) {
                            $sku_services[$row['sku_id']][$row['service_variant_id']] = $row;
                        }
                    }

                    // Добавляем услуги к заказу
                    foreach ($item['service_variant'] as $service_id => $variant_id) {
                        if (in_array($service_id, $item['services']) && !empty($services[$service_id])) {
                            $prepared_item = $this->prepareCartItem($service_item + array('service_id' => $service_id, 'service_variant_id' => $variant_id), 'service', array('services' => $services, 'product_services' => $product_services, 'sku_services' => $sku_services, 'variants' => $variants));
                            $items[$prepared_item['id']] = $prepared_item;
                        }
                    }
                }
            }
        }
        return $items;
    }

    /**
     * Get items if we are working with cart
     *
     * @return array
     * @throws waException
     */
    private function getItemsForCart()
    {
        $items = array();
        $shop_cart = new shopCart();
        $cart_items = $shop_cart->items();
        foreach ($cart_items as $k => $item) {
            $processed_item = $this->processProduct($item, $item);
            $items[$k] = $processed_item;
            if (!empty($item['services'])) {
                foreach ($item['services'] as $service) {
                    $items[$k . '-' . $service['service_id'] . '-' . $service['service_variant_id']] = $service;
                }
            }
        }
        return $items;
    }

    /**
     * Prepare item for order_calculate_discount format
     *
     * @param array $item
     * @param string $type
     * @param array $params
     * @return array
     */
    private function prepareCartItem($item, $type = 'product', $params = array())
    {
        static $item_id = 1;
        static $datetime = null;

        if ($datetime === null) {
            $datetime = date("Y-m-d H:i:s");
        }

        $item['type'] = $type;
        $item['id'] = $item_id;
        $item['create_datetime'] = $datetime;
        $item['contact_id'] = $this->contact && $this->contact->getId() ? $this->contact->getId() : 0;
        // Обработка товара
        if ($type == 'product') {
            $item['product'] = $item;
            $item['product']['id'] = $item['product_id'];
            $item['service_id'] = $item['service_variant_id'] = $item['parent_id'] = null;
        } // Обработка услуги
        else {
            $item['name'] = $item['service_name'] = $params['services'][$item['service_id']]['name'];
            $item['currency'] = $params['services'][$item['service_id']]['currency'];
            $item['service'] = $params['services'][$item['service_id']];
            $item['variant_name'] = $params['variants'][$item['service_variant_id']]['name'];
            if ($item['variant_name']) {
                $item['name'] .= ' (' . $item['variant_name'] . ')';
            }
            $item['price'] = (float) $params['variants'][$item['service_variant_id']]['price'];
            if (isset($params['product_services'][$item['product_id']][$item['service_variant_id']])) {
                if ($params['product_services'][$item['product_id']][$item['service_variant_id']]['price'] !== null) {
                    $item['price'] = (float) $params['product_services'][$item['product_id']][$item['service_variant_id']]['price'];
                }
            }
            if (isset($params['sku_services'][$item['sku_id']][$item['service_variant_id']])) {
                if ($params['sku_services'][$item['sku_id']][$item['service_variant_id']]['price'] !== null) {
                    $item['price'] = (float) $params['sku_services'][$item['sku_id']][$item['service_variant_id']]['price'];
                }
            }
            if ($item['currency'] == '%') {
                $p = $item["product"];
                $item['price'] = (float) shop_currency($item['price'] * $p['price'] / 100, $p['currency'], $p['currency'], false);
                $item['currency'] = $p['currency'];
            }
        }
        $item_id++;

        return $item;
    }

    /**
     * Get order total
     *
     * @param bool $calculate_shipping
     * @param bool $calculate_discount
     * @return float
     * @throws Exception
     */
    public function getTotal($calculate_shipping = true, $calculate_discount = true)
    {
        static $total;

        $key = 't' . ($calculate_shipping ? 's' : '') . ($calculate_discount ? 'd' : '');

        if (isset($total[$key])) {
            return $total[$key];
        }

        $total[$key] = 0;
        $order = $this->getOrder($calculate_shipping);
        if ($order['total'] > 0) {
            $total[$key] = $order['total'] - ($calculate_discount ? $this->getDiscount($order) : 0);
            if ($total[$key] < 0) {
                $total[$key] = 0;
            }
        }
        return (float) $total[$key];
    }

    /**
     * Get order
     *
     * @param bool $calculate_shipping
     * @return array
     * @throws Exception
     */
    public function getOrder($calculate_shipping = true)
    {
        static $order;
        static $total = array();

        $key = 't' . ($calculate_shipping ? 's' : '');

        if ($order !== null) {
            if (!isset($total[$key])) {
                $total[$key] = $this->getOrderTotal($order['items'], $calculate_shipping);
                $order['total'] = $total[$key];
            }

            return $order;
        }

        $order = array(
            'currency' => wa('shop')->getConfig()->getCurrency(false),
            'total' => 0,
            'items' => array(),
            'contact' => $this->getContact(),
            'custom_coupon' => $this->getData('coupon')
        );

        $order['items'] = $this->getItems();
        $total[$key] = $this->getOrderTotal($order['items'], $calculate_shipping);
        $order['total'] = $total[$key];

        return $order;
    }

    /**
     * Calculate order total value
     *
     * @param array $items
     * @param bool $calculate_shipping
     * @return float|int
     * @throws Exception
     */
    private function getOrderTotal($items, $calculate_shipping = true)
    {
        return $this->itemsTotal($items) + ($calculate_shipping ? $this->shippingTotal() : 0);
    }

    /**
     * Get order discount
     *
     * @param array $order
     * @param bool $skip_caching
     * @return float
     */
    public function getDiscount($order = array(), $skip_caching = false)
    {
        static $discount = array();

        $key = 'default';
        if (waRequest::param('quickorder_ignore_sd')) {
            $key = 'quickorder_ignore_sd';
        }

        if (isset($discount[$key]) && !$skip_caching) {
            return $discount[$key];
        }

        $order = $order ? $order : $this->getOrder();
        // Для расчета скидки в значении total нельзя включать стоимость доставки, только стоимость товаров.
        // Иначе это будет противоречить обычному оформлению заказа
        $order_total = $this->getTotal(false, false);
        $order['total'] = $order_total;

        // Заставляем Гибкие скидки сделать перерасчет
        waRequest::setParam('flexdiscount_force_calculate', 1);
        waRequest::setParam('flexdiscount_skip_caching', 1);

        $discount_value = (float) shopDiscounts::calculate($order);

        waRequest::setParam('flexdiscount_skip_caching', 0);

        // Бесплатная доставка от Гибких скидкок
        if (shopDiscounts::isEnabled('flexdiscount')) {
            $workflow = shopFlexdiscountData::getOrderCalculateDiscount();
            if (!empty($workflow['active_rules'])) {
                foreach ($workflow['active_rules'] as $active_rule) {
                    if (!empty($active_rule['free_shipping'])) {
                        $discount_value += $active_rule['free_shipping'];
                    }
                }
            }
        }

        // Не даем начислить скидку больше, чем стоимость заказа в целом
        if ($discount_value > $order_total) {
            $discount_value = $order_total;
        }

        if (!$skip_caching) {
            $discount[$key] = $discount_value;
        }

        return $discount_value;
    }

    /**
     * Get discount without coupon
     *
     * @return float
     */
    public function getDiscountWithoutCoupon()
    {
        $data = wa()->getStorage()->get('shop/checkout');
        $old_coupon = ifset($data, 'coupon_code', '');
        $this->setCoupon('');
        $discount_without_coupon = $this->getDiscount([], true);
        $this->setCoupon($old_coupon);
        return $discount_without_coupon;
    }

    /**
     * Set coupon code
     *
     * @param string $code
     */
    public function setCoupon($code)
    {
        $data = wa()->getStorage()->get('shop/checkout');
        $data['coupon_code'] = $code;
        wa()->getStorage()->set('shop/checkout', $data);
        if (!isset($_POST['quickorder'])) {
            $_POST['quickorder'] = [
                'coupon' => $code
            ];
        } else {
            $_POST['quickorder']['coupon'] = $code;
        }
    }

    /**
     * Calculate items total
     *
     * @param array|null $items
     * @return float
     * @throws waException
     */
    public function itemsTotal($items = null)
    {
        if ($items === null) {
            $items = $this->getItems();
        }
        $items_total = 0.0;
        foreach ($items as $i) {
            $items_total += $i['price'] * $i['quantity'];
        }
        return (float) $items_total;
    }

    /**
     * Calculate shipping total
     *
     * @return float
     * @throws Exception
     */
    private function shippingTotal()
    {
        $shipping_total = 0.0;

        $shipping = $this->getMethods('shipping');
        if (!empty($shipping['selected']['id']) && !empty($shipping['methods'][$shipping['selected']['id']])) {
            $method = $shipping['methods'][$shipping['selected']['id']];
            if (empty($method['error']) && isset($method['rate']) && $method['rate'] !== null && $method['rate'] !== '') {
                $shipping_total = shop_currency($method['rate'], $method['currency'], null, false);
            }
        }
        return $shipping_total;
    }

    /**
     * Generate unique query code
     *
     * @return string
     * @throws waException
     */
    public function getCode()
    {
        if (!$this->code) {
            $this->code = md5(microtime(true));
        }
        return $this->code;
    }

    /**
     * Set query code
     *
     * @param string $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * Get storage object
     *
     * @return shopQuickorderPluginStorage
     * @throws waException
     */
    public function getStorage()
    {
        static $storage;
        if (!$storage) {
            $storage = new shopQuickorderPluginStorage($this->getCode());
        }
        return $storage;
    }

    /**
     * Get shipping or payment methods
     *
     * @param string $method
     * @return array
     * @throws Exception
     */
    public function getMethods($method)
    {
        static $methods = array();

        if (isset($methods[$method])) {
            return $methods[$method];
        }

        $methods[$method] = array();
        $settings = shopQuickorderPluginHelper::getSettings();

        $methods_list_str = !empty($settings['shared_display_settings']) ? $settings[$method]['product'] : $settings[$method][$this->type];

        if (!empty($methods_list_str)) {
            $methods_list = shopQuickorderPluginHelper::decodeToArray($methods_list_str);
            $class = $method == 'shipping' ? new shopQuickorderPluginShipping($methods_list, $this) : new shopQuickorderPluginPayment($methods_list, $this);
            $methods[$method] = $class->getMethods();
        }

        return $methods[$method];
    }

    /**
     * Update contact with post data
     *
     * @param array $fields
     * @throws waException
     */
    private function setContact($fields)
    {
        if ($fields) {
            $contact = $this->getContact();

            $address_field = array();
            foreach ($fields as $field_id => $field) {
                if ($field_id == 'country') {
                    $field_id = 'address::country';
                }
                // Если передано поле адреса
                if (strpos($field_id, "::")) {
                    $parts = explode("::", $field_id);
                    $field_type = $parts[0];
                    if (strpos($field_type, '.') !== false) {
                        $parts2 = explode('.', $field_type, 2);
                        $field_type = $parts2[0];
                        $ext = "." . $parts2[1];
                    } else {
                        $ext = '.shipping';
                    }
                    $field_id = $parts[1];
                } else {
                    $field_type = "contact";
                }
                // Если передан адрес, то формируем массив к сохранению, иначе обновляем поля контакта
                if ($field_type == 'address') {
                    $address_field[$field_type . $ext][$field_id] = $field;
                } else {
                    $contact->set($field_id, $field);
                }
            }

            // Проверяем, переданы ли поля доставки
            $shipping_id = waRequest::post('shipping_id');
            if ($shipping_id) {
                $customer_post_name = 'customer_' . $shipping_id;
                $customer_fields = waRequest::post($customer_post_name);
                if (!waRequest::post('replace_shipping')) {
                    if (!empty($customer_fields['address.shipping'])) {
                        foreach ($customer_fields['address.shipping'] as $field => $value) {
                            $address_field['address.shipping'][$field] = $value;
                        }
                    }
                } // Подменяем данные, чтобы во всех методах доставки менять регион при смене оного в контактных данных
                elseif (isset($address_field['address.shipping']) && isset($_POST[$customer_post_name])) {
                    $_POST[$customer_post_name]['address.shipping'] = array_merge($_POST[$customer_post_name]['address.shipping'], $address_field['address.shipping']);
                }
            }

            // Сохраняем поля адреса
            if ($address_field) {
                foreach ($address_field as $addr_id => $addr_v) {
                    $contact->set($addr_id, $addr_v);
                }
            }
            if (!$contact->get("name")) {
                $contact->set("firstname", "<" . _wp("no-name") . ">");
            }
            $this->contact = $contact;
            $cache_contact = new waRuntimeCache('quickorder_cache_contact');
            $cache_contact->set($this->contact);

            $cache_contact_changed = new waRuntimeCache('quickorder_cache_contact_changed');
            $cache_contact_changed->set(1);
        }
    }

    /**
     * Get contact
     *
     * @return waAuthUser|waContact|waUser
     * @throws waException
     */
    public function getContact()
    {
        static $processed;

        $cache_contact = new waRuntimeCache('quickorder_cache_contact');
        $cache_contact_changed = new waRuntimeCache('quickorder_cache_contact_changed');

        if ($cache_contact->isCached()) {
            $this->contact = $cache_contact->get();
        } elseif (!$this->contact) {
            $this->contact = wa()->getUser()->isAuth() ? wa()->getUser() : new waContact();
        }

        if ($processed === null || $cache_contact_changed->isCached()) {
            $this->getContactAddress();
            $cache_contact->set($this->contact);
            $cache_contact_changed->delete();
            $processed = 1;
        }
        return $this->contact;
    }

    private function getContactAddress()
    {
        static $api_address;

        $address = $this->contact->getFirst('address.shipping') + $this->contact->getFirst('address');

        if (!$address) {
            $address = array(
                'data' => array()
            );
        }

        /* Плагин Автоопределение и выбор города */
        $is_cityselect_enable = 0;
        $plugins = wa('shop')->getConfig()->getPlugins();
        if (isset($plugins['cityselect'])) {
            $route_settings = shopCityselectPlugin::loadRouteSettings();
            $is_cityselect_enable = !empty($route_settings['enable']);
        }

        if ($is_cityselect_enable) {
            if (empty($address['data'])) {
                $address['data'] = shopCityselectHelper::getLocation();
            }
        } else {
            // Автоопределение города
            if (shopQuickorderPluginHelper::getSettings('use_sypex_geo') && (empty($address['data']['country']) || empty($address['data']['region']) || empty($address['data']['city']))) {
                if ($api_address === null) {
                    $api_address = (new shopQuickorderPluginGeo())->getAddress();
                }
                if ($api_address) {
                    $address['data']['country'] = ifempty($address['data']['country'], $api_address['country']);
                    $address['data']['region'] = ifempty($address['data']['region'], $api_address['region']);
                    $address['data']['city'] = ifempty($address['data']['city'], $api_address['city']);
                }
            }
        }

        // Если не указана страна, выбираем по умолчанию
        if (empty($address['data']['country'])) {
            // Если указан только регион, определяем страну по региону
            if (!empty($address['data']['region'])) {
                $country = (new waRegionModel())->select('country_iso3')->where('code=i:code', array('code' => $address['data']['region']))->fetchField();
            } // Если адрес не указан вообще, указываем страну, в которой находится магазин (из настроек)
            else {
                $country = wa('shop')->getConfig()->getGeneralSettings('country');
            }
            if ($country) {
                $address['data']['country'] = $country;
            }
        }
        if (!empty($address['data'])) {
            $this->contact->set('address.shipping', $address['data']);
        }
    }

    /**
     * Return current type
     *
     * @return string - product or cart
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Add all items to temporary table shop_quickorder_cart_items.
     * It is necessary for getting "not available products"
     *
     * @throws waException
     */
    public function addItems()
    {
        $code = $this->getCode();

        $items = $this->getItems();

        $add = array();
        if ($items) {
            $datetime = date('Y-m-d H:i:s');
            foreach ($items as $item) {
                $add[] = array(
                    'code' => $code,
                    'contact_id' => $this->getContact()->getId(),
                    'product_id' => $item['product_id'],
                    'sku_id' => $item['sku_id'],
                    'create_datetime' => $datetime,
                    'quantity' => $item['quantity'],
                    'type' => $item['type'],
                    'service_id' => $item['service_id'],
                    'service_variant_id' => $item['service_variant_id'],
                    'parent_id' => $item['parent_id'],
                );
            }
            (new shopQuickorderPluginCartItemsModel())->multipleInsert($add);
        }
    }

    /**
     * Remove items from temporary table shop_quickorder_cart_items
     *
     * @throws waException
     */
    public function removeItems()
    {
        $code = $this->getCode();
        (new shopQuickorderPluginCartItemsModel())->deleteByField('code', $code);
    }

    /**
     * Get field from $_POST['quickorder']
     *
     * @param string $field
     * @return mixed|null
     */
    public function getData($field)
    {
        return !empty($this->post_data[$field]) ? $this->post_data[$field] : null;
    }

    /**
     * Check field from $_POST['quickorder']
     *
     * @param string $field
     * @return bool
     */
    public function issetData($field)
    {
        return isset($this->post_data[$field]);
    }

    /**
     * Get affiliate vars
     *
     * @return array
     */
    public function getAffiliateVars()
    {
        $affiliate_bonus = $affiliate_discount = 0;
        $order = array(
            'currency' => wa()->getConfig()->getCurrency(false),
            'total' => $this->getTotal(false, true),
            'items' => $this->getItems(),
            'params' => array('affiliate_bonus' => $affiliate_bonus)
        );

        if (wa()->getUser()->isAuth()) {
            $customer_model = new shopCustomerModel();
            $customer = $customer_model->getById(wa()->getUser()->getId());
            $affiliate_bonus = $customer ? round($customer['affiliate_bonus'], 2) : 0;
            $order['params']['affiliate_bonus'] = $affiliate_bonus;
        }

        $affiliate_discount = shopFrontendCartAction::getAffiliateDiscount($affiliate_bonus, $order);

        $add_affiliate_bonus = shopAffiliate::calculateBonus($order);
        return array('affiliate_bonus' => $affiliate_bonus, 'affiliate_discount' => $affiliate_discount, 'add_affiliate_bonus' => round($add_affiliate_bonus, 2));
    }

}
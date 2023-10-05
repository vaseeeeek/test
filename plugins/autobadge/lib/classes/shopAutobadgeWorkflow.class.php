<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopAutobadgeWorkflow extends shopAutobadgeCore
{

    /**
     * Add product to order
     *
     * @param array $product
     * @param string $currency
     * @return array Order params
     */
    public static function addToOrder($product, $currency = '')
    {
        $params = self::getOrder();

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

        // Изменяем общую цену заказа
        $product_price = (float) shop_currency((float) $prod['price'] * $prod['quantity'], $prod['currency'], $params['order']['currency'], false);
        $params['order']['total'] += shopRounding::roundCurrency($product_price, $params['order']['currency']);

        return $params;
    }

    /**
     * Get order params
     *
     * @return array
     */
    public static function getOrder()
    {
        static $order = array();
        if (!$order) {
            $shopCart = new shopCart();
            $contact = wa('shop')->getUser();

            waRequest::setParam('promos_skip_frontend_products', 1);
            waRequest::setParam('flexdiscount_skip_frontend_products', 1);

            $order = array(
                'order' => array(
                    'currency' => wa('shop')->getConfig()->getCurrency(false),
                    'items' => $shopCart->items(false),
                    'contact' => $contact,
                    'total' => $shopCart->total(false),
                ),
                'contact' => $contact,
                'apply' => 0
            );

            waRequest::setParam('promos_skip_frontend_products', 0);
            waRequest::setParam('flexdiscount_skip_frontend_products', 0);

            if ($order['order']['items']) {
                $order['order']['items'] = shopAutobadgeHelper::fixPrices($order['order']['items']);
            }
        }
        return $order;
    }

    /**
     * Prepare product. Convert prices
     *
     * @param array $products
     * @param array $sku_ids
     * @return array
     * @throws waException
     */
    public static function prepareProducts($products, $sku_ids)
    {
        static $current_cur;
        static $primary_cur;
        static $is_flexdiscount_enabled;
        $plugins = wa('shop')->getConfig()->getPlugins();
        if ($current_cur === null) {
            $config = wa('shop')->getConfig();
            $current_cur = $config->getCurrency(false);
            $primary_cur = $config->getCurrency(true);
            $is_flexdiscount_enabled = isset($plugins['flexdiscount']) && shopDiscounts::isEnabled('flexdiscount')
                && version_compare($plugins['flexdiscount']['version'], '4', '>=')
                && shopFlexdiscountHelper::getSettings('frontend_prices');
        }

        $skus = (new shopProductSkusModel())->getById($sku_ids);

        if (method_exists(new shopProductsCollection(), 'promoProductPrices')) {
            self::promoProductPrices()->workupPromoSkus($skus, $products);
        }

        if ($skus) {
            waRequest::setParam('flexdiscount_skip_frontend_products', 1);
            // Вызываем хук frontend_products
            $event_params = array("skus" => &$skus);
            wa('shop')->event('frontend_products', $event_params);
            waRequest::setParam('flexdiscount_skip_frontend_products', 0);

            foreach ($products as &$product) {
                $sku_id = $product['sku_id'];
                // Если переданные данные совпадают
                if (isset($skus[$sku_id])) {
                    $sku = $skus[$sku_id];
                    $product_currency = !empty($product['unconverted_currency']) ? $product['unconverted_currency'] : $product['currency'];

                    $sku['product'] = $product;
                    $sku['sku_id'] = $sku['id'];
                    $sku['id'] = $sku['product_id'];
                    $sku['quantity'] = !empty($product['quantity']) ? $product['quantity'] : 1;
                    // Переводим цены товара в текущую валюту
                    $sku['price'] = shop_currency($sku['price'], $product_currency, $current_cur, false);
                    if ($product_currency !== $primary_cur) {
                        $sku['price'] = shopRounding::roundCurrency($sku['price'], $current_cur);
                    }

                    $compare_price_changed = 0;
                    // Если были скидки от плагина Гибкие скидки 4.0
                    try {
                        if ($is_flexdiscount_enabled) {
                            waRequest::setParam('flexdiscount_skip_caching', 1);
                            $workflow_discount = shopFlexdiscountPluginHelper::getProductDiscounts($product, null, $sku_id, false);
                            if (!empty($workflow_discount['clear_discount'])) {
                                $sku['compare_price'] = $sku['price'];
                                $sku['price'] -= $workflow_discount['clear_discount'];
                                $compare_price_changed = 1;
                            }
                            waRequest::setParam('flexdiscount_skip_caching', 0);
                        }
                    } catch (Exception $e) {

                    }

                    if ($sku['compare_price'] == $sku['price']) {
                        $sku['compare_price'] = 0;
                    }

                    if (!$compare_price_changed) {
                        $sku['compare_price'] = shop_currency($sku['compare_price'], $product_currency, $current_cur, false);
                        if ($product_currency !== $primary_cur) {
                            $sku['compare_price'] = shopRounding::roundCurrency($sku['compare_price'], $current_cur);
                        }
                    }

                    $sku['purchase_price'] = shop_currency($sku['purchase_price'], $product_currency, $current_cur, false);
                    if ($product_currency !== $primary_cur) {
                        $sku['purchase_price'] = shopRounding::roundCurrency($sku['purchase_price'], $current_cur);
                    }
                    // Сохраняем услуги, если они имеются
                    if (!empty($product['services'])) {
                        $sku['services'] = $product['services'];
                    }
                    if (isset($product['autobadge-page'])) {
                        $sku['autobadge-page'] = $product['autobadge-page'];
                    }
                    if (isset($product['autobadge-type'])) {
                        $sku['autobadge-type'] = $product['autobadge-type'];
                    }
                    $product = $sku;
                    $product['type'] = 'product';
                    $product['currency'] = $current_cur;
                }
                unset($product);
            }
        }

        return $products;
    }

    /**
     * The same method as in shopProductsCollection
     * @return shopPromoProductPrices
     * @throws waException
     */
    private static function promoProductPrices()
    {
        static $promo_product_prices;

        $routing_url = wa('shop')->getRouting()->getRootUrl();
        $storefront = wa('shop')->getConfig()->getDomain() . ($routing_url ? '/' . $routing_url : '');

        if (!isset($promo_product_prices[$storefront])) {
            $promo_prices_model = new shopProductPromoPriceTmpModel();
            $options = [
                'model' => $promo_prices_model,
                'storefront' => $storefront,
            ];
            $promo_product_prices[$storefront] = new shopPromoProductPrices($options);
        }
        return $promo_product_prices[$storefront];
    }

}

<?php

class shopPricePluginBackendGetProductsController extends shopOrdersGetProductController
{

    public function execute()
    {
        error_reporting(E_ALL & ~E_NOTICE);

        $price_id = waRequest::post('price_id', null, waRequest::TYPE_INT);
        $order_id = waRequest::post('order_id', null, waRequest::TYPE_INT);
        $customer_id = waRequest::post('customer_id', null, waRequest::TYPE_INT);
        $order_id = $order_id ? $order_id : null;
        $currency = waRequest::post('currency');
        $storefront = waRequest::post('storefront');

        $_products = waRequest::post('product');
        $product_ids = array();

        if (!empty($_products['edit'])) {
            foreach ($_products['edit'] as $product_id) {
                $product_ids[] = $product_id;
            }
        }
        if (!empty($_products['add'])) {
            foreach ($_products['add'] as $product_id) {
                $product_ids[] = $product_id;
            }
        }

        if (!$product_ids) {
            return;
        }

        $products = [];
        foreach ($product_ids as $product_id) {
            $shop_product = new shopProduct($product_id, ['round_currency' => $currency]);
            $product = $shop_product->getData();
            $product = $this->workupProduct($product);

            $product['skus'] = $shop_product->getSkus();
            $product['skus'] = $this->workupSkus($product['skus']);

            $products[$product_id] = $product;
        }

        $response = array();

        if ($price_id !== 0) {
            $products = shopPricePlugin::prepareProducts($products, $customer_id, $currency, $storefront, $price_id);
        }

        foreach ($products as &$product) {
            if ($price_id !== 0) {
                $product['skus'] =
                    shopPricePlugin::prepareSkus($product['skus'], $customer_id, $currency, $storefront, $price_id);
            }

            $min_price = null;
            $max_price = null;
            foreach ($product['skus'] as &$sku) {
                if (isset($sku['price'])) {
                    $sku['price_str'] = wa_currency($sku['price'], $currency);
                    $sku['price_html'] = wa_currency_html($sku['price'], $currency);
                }
            }
            unset($sku);

            if (empty($product['services'])) {
                $product['services'] = [];
            }

            $response[$product['id']] = array(
                'product' => $product,
                'sku_ids' => array_keys($product['skus']),
                'service_ids' => array_keys($product['services']),
            );
        }
        unset($product);
        $this->response = $response;
    }

//    protected function getCurrency()
//    {
//        $order_id = waRequest::get('order_id', null, waRequest::TYPE_INT);
//
//        $order_id = $order_id
//            ? $order_id
//            : null;
//        $currency = waRequest::get('currency');
//
//        if (! $currency && $order_id) {
//            $order_model = new shopOrderModel;
//            $order       = $order_model->getOrder($order_id);
//            if ($order) {
//                $currency = $order['currency'];
//            } else {
//                $currency = wa('shop')->getConfig()->getCurrency(true);
//            }
//        }
//
//        return $currency;
//    }
//
//    /**
//     * Get services for a specific sku
//     *
//     * @param $product
//     * @param $sku
//     * @return array
//     */
//    private function getServices($product, $sku)
//    {
//        if (empty($product) || empty($sku)) {
//            return [];
//        }
//
//        $service_model = new shopProductServicesModel();
//
//        $out_currency = $this->getCurrency();
//        $sku_price = $sku['price'];
//
//        $services = $service_model->getAvailableServicesFullInfo($product, $sku['id']);
//
//        foreach ($services as $service_id => &$service) {
//            $service_currency = $service['currency'];
//
//            foreach ($service['variants'] as &$variant) {
//                if ($service['currency'] == '%') {
//                    $variant['percent_price'] = $variant['price'];
//                    // Converting interest to actual value
//                    $variant['price'] = (float)$sku_price / 100 * $variant['price'];
//                }
//
//                //Price in text format
//                $variant['price'] = $this->convertService($variant['price'], $service_currency, $out_currency);
//                $variant['price_str'] = ($variant['price'] >= 0 ? '+' : '-').wa_currency($variant['price'], $out_currency);
//                $variant['price_html'] = ($variant['price'] >= 0 ? '+' : '-').wa_currency_html($variant['price'], $out_currency);
//            }
//            unset($variant);
//
//            // Sets the default price for the service.
//            $default_variant = ifset($service, 'variants', $service['variant_id'], []);
//            if (isset($default_variant['price'])) {
//                $service['price'] = $default_variant['price'];
//                if (isset($default_variant['percent_price'])) {
//                    $service['percent_price'] = $default_variant['percent_price'];
//                }
//            } else {
//                // Invalid database state.
//                unset($services[$service_id]);
//            }
//        }
//
//        unset($service);
//        return $services;
//    }
}

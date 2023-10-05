<?php

class shopPricePluginBackendGetProductController extends shopOrdersGetProductController
{

    public function execute()
    {
        $price_id = waRequest::get('price_id', null, waRequest::TYPE_INT);
        $order_id = waRequest::get('order_id', null, waRequest::TYPE_INT);
        $customer_id = waRequest::get('customer_id', null, waRequest::TYPE_INT);
        $order_id = $order_id ? $order_id : null;
        $currency = waRequest::get('currency');
        $storefront = waRequest::get('storefront');

        if (!$currency && $order_id) {
            $order_model = new shopOrderModel();
            $order = $order_model->getOrder($order_id);
            $currency = $order['currency'];
        }

        $product_id = waRequest::get('product_id', 0, waRequest::TYPE_INT);
        if (!$product_id) {
            $this->errors[] = _w("Unknown product");
            return;
        }

        $options = [
            'round_currency' => $this->getCurrency()
        ];

        $storefront_context = $this->getStorefront();
        if (!empty($storefront_context)) {
            $options['storefront_context'] = $storefront_context;
        }
        $shop_product = new shopProduct($product_id, $options);

        $product = $shop_product->getData();
        $product = $this->workupProduct($product);

        $product['skus'] = $shop_product->getSkus();
        $product['skus'] = $this->workupSkus($product['skus']);

        $sku_id = waRequest::get('sku_id', 0, waRequest::TYPE_INT);
        if ($sku_id) {
            $sku = $product['skus'][$sku_id];
            if ($price_id !== 0) {
                $skus = shopPricePlugin::prepareSkus(array($sku_id => $sku), $customer_id, $currency, $storefront, $price_id);
            }
            if (!empty($skus[$sku_id])) {
                $sku = $skus[$sku_id];
            }
            $sku['services'] = $this->getServices($product, $sku);
            $this->response['sku'] = $sku;
            $this->response['service_ids'] = array_keys($sku['services']);
        } else {
            $sku = ifset($product, 'skus', $product['sku_id'], []);
            //take services for the main sku
            $product['services'] = $this->getServices($product, $sku);

            if ($price_id !== 0) {
                $products = shopPricePlugin::prepareProducts(array($product_id => $product), $customer_id, $currency, $storefront, $price_id);
            }
            if (!empty($products[$product_id])) {
                $product = $products[$product_id];
            }
            if ($price_id !== 0) {
                $product['skus'] = shopPricePlugin::prepareSkus($product['skus'], $customer_id, $currency, $storefront, $price_id);
            }

            foreach ($product['skus'] as &$sku) {
                if (isset($sku['price'])) {
                    $sku['price_str'] = wa_currency($sku['price'], $currency);
                    $sku['price_html'] = wa_currency_html($sku['price'], $currency);
                }
            }
            unset($sku);
            $this->response['product'] = $product;
            $this->response['sku_ids'] = array_keys($product['skus']);

            if (empty($product['services'])) {
                $product['services'] = [];
            }

            $this->response['service_ids'] = array_keys($product['services']);
        }
    }

    /**
     * Get services for a specific sku
     *
     * @param $product
     * @param $sku
     * @return array
     */
    private function getServices($product, $sku)
    {
        if (empty($product) || empty($sku)) {
            return [];
        }

        $service_model = new shopProductServicesModel();

        $out_currency = $this->getCurrency();
        $sku_price = $sku['price'];

        $services = $service_model->getAvailableServicesFullInfo($product, $sku['id']);
        $services = $this->workupServices($services, $sku_price, $out_currency);

        unset($service);
        return $services;
    }


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
//
//    protected function getCurrency()
//    {
//        $order_id = waRequest::get('order_id', null, waRequest::TYPE_INT);
//
//        $order_id = $order_id ? $order_id : null;
//        $currency = waRequest::get('currency');
//
//        if (!$currency && $order_id) {
//            $order_model = new shopOrderModel();
//            $order = $order_model->getOrder($order_id);
//            $currency = $order['currency'];
//        }
//        return $currency;
//    }

}

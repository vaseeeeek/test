<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopFlexdiscountPluginFrontendUpdateDiscountController extends shopFlexdiscountPluginJsonController
{

    public function execute()
    {
        $data = array();
        
        $products = waRequest::post('products');
        
        // Список товаров, для которых необходимо узнать скидки
        if ($products) {
            $data['products'] = $data['product_skus'] = array();
            foreach ($products as $product_id => $p) {

                // Информация о товаре
                $product = new shopProduct($product_id, true);
                if (!$product) {
                    continue;
                }
                $product = $product->getData();

                $sku_id = 0;
                // Данные форм
                if ($p['params']) {
                    parse_str($p['params'], $p['params']);
                }
                // Проверяем, передан ли артикул товара. Если нет, то пытаемся определить его через данные форм
                if (empty($p['sku_id'])) {
                    if (isset($p['params']['sku_id'])) {
                        $sku_id = $p['params']['sku_id'];
                    } else {
                        if (isset($p['params']['features'])) {
                            $sku_id = (new shopProductFeaturesModel())->getSkuByFeatures($product_id, $p['params']['features']);
                        }
                    }
                } else {
                    $sku_id = $p['sku_id'];
                }

                if (!$sku_id) {
                    $sku_id = $product['sku_id'];
                }
                $product['sku_id'] = $sku_id;

                // Проверяем наличие услуг
                if (!empty($p['params']['services'])) {
                    $product['services'] = self::getProductServices($p['params'], $product);
                }

                $data['products'][$product_id] = array($sku_id => array());
                $data['product_skus'][$product_id] = $sku_id;

                // Изменяем количество товара
                $product['quantity'] = (int) $p['quantity'] > 0 ? (int) $p['quantity'] : 1;

                // Действующие скидки
                self::getTypeDiscount('p_discounts', $p, $product, $sku_id, $data);
                // Доступные скидки
                self::getTypeDiscount('available_discounts', $p, $product, $sku_id, $data);
                // Цена со скидкой
                self::getTypeDiscount('price_blocks', $p, $product, $sku_id, $data);
                // Запрещающие скидки
                self::getTypeDiscount('deny_discounts', $p, $product, $sku_id, $data);
            }
        }

        $this->response = $data;
    }

    /**
     * Get product services by POST data
     * 
     * @param array $params
     * @param array $product
     * @return array
     */
    private static function getProductServices($params, $product)
    {
        // Текущая валюта
        $current_cur = shopFlexdiscountApp::get('system')['current_currency'];
        $services = $params['services'];
        $variants = !empty($params['service_variant']) ? $params['service_variant'] : array();
        $temp = array();
        $service_ids = array();
        foreach ($services as $service_id) {
            $temp[$service_id] = isset($variants[$service_id]) ? $variants[$service_id] : 0;
            $service_ids[] = $service_id;
        }
        $temp_services = (new shopServiceModel())->getById($service_ids);
        $service_stubs = array();
        foreach ($temp_services as $row) {
            if (!$temp[$row['id']]) {
                $temp[$row['id']] = $row['variant_id'];
            }
            $service_stubs[$row['id']] = array(
                'id' => $row['id'],
                'currency' => $row['currency'],
            );
        }
        $services = $temp;

        $variant_ids = array_values($services);

        $rounding_enabled = shopRounding::isEnabled();
        $variants = (new shopServiceVariantsModel())->getWithPrice($variant_ids);
        $rounding_enabled && shopRounding::roundServiceVariants($variants, $service_stubs);
        
        $product_services_model = new shopProductServicesModel();
        // Fetch service prices for specific products and skus
        $rows = $product_services_model->getByField(array('product_id' => $product['id'], 'service_id' => $service_ids, 'service_variant_id' => $variant_ids), true);
        shopRounding::roundServiceVariants($rows, $service_stubs);
        $skus_services = array();
        foreach ($rows as $row) {
            if (!$row['sku_id']) {
                if (!$row['status']) {
                    continue;
                } elseif ($row['price'] !== null && isset($variants[$row['service_variant_id']])) {
                    $variants[$row['service_variant_id']]['price'] = $row['price'];
                }
            } else {
                if ($row['status'] && $row['price'] !== null && isset($variants[$row['service_variant_id']]) && $product['sku_id'] == $row['sku_id']) {
                    $skus_services[$row['service_variant_id']] = $row['price'];
                }
            }
        }

        foreach ($variants as &$v) {
            $variant_price = isset($skus_services[$v['id']]) ? $skus_services[$v['id']] : $v['price'];
            if ($v['currency'] == '%') {
                $v['price'] = shopFlexdiscountApp::getFunction()->shop_currency($variant_price * $product['price'] / 100, shopFlexdiscountApp::get('system')['primary_currency'], $current_cur, false);
            } else {
                $v['price'] = shopFlexdiscountApp::getFunction()->shop_currency($variant_price, $v['currency'], $current_cur, false);
            }
            $v['currency'] = $current_cur;
        }

        return $variants;
    }

    /**
     * Get html to discount types
     * 
     * @param string $type
     * @param array $product
     * @param array $product_info
     * @param int $sku_id
     * @param array $data
     */
    private static function getTypeDiscount($type, $product, $product_info, $sku_id, &$data)
    {
        $product_id = $product_info['id'];
        $return_html = waRequest::post('return_html', 1);
        if (!empty($product[$type]['find'])) {
            $data['products'][$product_id][$sku_id][$type] = array();
            foreach ($product[$type]['find'] as $view => $filter_by) {
                if ($type == 'price_blocks') {
                    $workflow = shopFlexdiscountPluginHelper::price($product_info, $sku_id, $view);
                } elseif ($type == 'available_discounts') {
                    $workflow = shopFlexdiscountPluginHelper::getAvailableDiscounts($product_info, $view, $sku_id, $filter_by ? $filter_by : array(), $return_html);
                } elseif ($type == 'deny_discounts') {
                    $workflow = shopFlexdiscountPluginHelper::getDenyRules($product_info, $view, $sku_id, $return_html);
                } else {
                    $workflow = shopFlexdiscountPluginHelper::getProductDiscounts($product_info, $view, $sku_id, $return_html);
                }
                if ($workflow) {
                    if ($return_html) {
                        $data['products'][$product_id][$sku_id][$type][$view] = $workflow;
                    } else {
                        $data['products'][$product_id][$sku_id][$type] = $workflow;
                        break;
                    }
                }
                unset($workflow);
            }
            if (!$data['products'][$product_id][$sku_id][$type]) {
                unset($data['products'][$product_id][$sku_id][$type]);
            }
            unset($product[$type]['find']);
        }
        if (!empty($product[$type])) {
            foreach ($product[$type] as $view_sku => $view) {
                if (!isset($data['products'][$product_id][$view_sku][$type])) {
                    $data['products'][$product_id][$view_sku][$type] = array();
                }
                if ($type == 'price_blocks') {
                    $workflow = shopFlexdiscountPluginHelper::price($product_info, $view_sku, $view);
                } elseif ($type == 'available_discounts') {
                    $workflow = shopFlexdiscountPluginHelper::getAvailableDiscounts($product_info, $view, $view_sku, array(), $return_html);
                } elseif ($type == 'deny_discounts') {
                    $workflow = shopFlexdiscountPluginHelper::getDenyRules($product_info, $view, $view_sku, $return_html);
                } else {
                    $workflow = shopFlexdiscountPluginHelper::getProductDiscounts($product_info, $view, $view_sku, $return_html);
                }
                if ($workflow) {
                    if ($return_html) {
                        $data['products'][$product_id][$view_sku][$type][$view] = $workflow;
                    } else {
                        $data['products'][$product_id][$view_sku][$type] = $workflow;
                        break;
                    }
                }
                unset($workflow);
            }
        }
    }

}

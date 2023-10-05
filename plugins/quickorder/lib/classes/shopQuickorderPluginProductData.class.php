<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopQuickorderPluginProductData extends shopProductsCollection
{

    /**
     * Prepare required product params
     *
     * @param array $products
     * @param bool $find_services
     * @param bool $skip_flexdiscount
     * @param bool $find_available
     * @return array
     */
    public function prepareProducts($products, $find_services = false, $skip_flexdiscount = false, $find_available = false)
    {
        $product_ids = $image_ids = $find_skus = $sku_ids = $service_ids = $type_ids = $images = array();

        $wa = wa('shop');

        // Текущая валюта
        $current_cur = $wa->getConfig()->getCurrency(false);
        $skus_model = new shopProductSkusModel();

        $plugins = $wa->getConfig()->getPlugins();
        $is_giftcertificate_installed = isset($plugins['giftcertificates']);
        if ($is_giftcertificate_installed) {
            $app = (new shopGiftcertificatesApp());
            $certificate_helper = $app::getCertificate();
            $giftcertificates_helper = (new shopGiftcertificatesPluginHelper());
            $certificates = $certificate_helper->getCartItemCertificates();
        }

        foreach ($products as $k => &$product) {
            // Если обрабатывается товар из корзины, добавляем недостающие данные
            if (isset($product['product'])) {
                $product['type_id'] = $product['product']['type_id'];
                $product['image_id'] = $product['product']['image_id'];
                $product['ext'] = $product['product']['ext'];
                $product['image_filename'] = $product['product']['image_filename'];
                $product['name'] = $product['product']['name'];
                $product['url'] = $product['product']['url'];
                $product['sku_count'] = $product['product']['sku_count'];
                $product['tax_id'] = $product['product']['tax_id'];
                if (isset($product['product']['unconverted_currency'])) {
                    $product['unconverted_currency'] = $product['product']['unconverted_currency'];
                }
            }
            $product_ids[] = $product['id'] = isset($product['product_id']) ? $product['product_id'] : (isset($product['product']) ? $product['product']['id'] : $product['id']);
            $type_ids[] = $product['type_id'];
            $sku_ids[] = $product['sku_id'];
            if (!isset($product['skus'])) {
                $product['skus'] = array();
            }
            if (isset($product['skus'][$product['sku_id']])) {
                $image_ids[] = $product['skus'][$product['sku_id']]['image_id'];
            }
            if (!isset($product['skus'][$product['sku_id']])) {
                $find_skus[$k] = $product['sku_id'];
            }
            $product['quantity'] = !empty($product['quantity']) ? (float) $product['quantity'] : 1;
        }
        unset($product);

        // Получаем недостающую информацию о артикулах
        if ($find_skus) {
            $skus = $skus_model->getByField('id', array_values($find_skus), 'id');

            if (method_exists($this, 'promoProductPrices')) {
                $this->promoProductPrices()->workupPromoSkus($skus, $products);
            }

            if ($skip_flexdiscount) {
                waRequest::setParam('flexdiscount_skip_frontend_products', 1);
            }

            // Вызываем хук frontend_products
            $event_params = array("skus" => &$skus);
            $wa->event('frontend_products', $event_params);

            if ($skip_flexdiscount) {
                waRequest::setParam('flexdiscount_skip_frontend_products', 0);
            }

            foreach ($find_skus as $k => $sku_id) {
                $products[$k]['skus'][$skus[$sku_id]['id']] = $skus[$sku_id];
                $image_ids[] = $skus[$sku_id]['image_id'];
            }
        }

        // Получаем информацию о изображениях
        $image_ids = array_unique($image_ids);
        if ($image_ids) {
            $images = (new shopProductImagesModel())->getByField('id', array_values($image_ids), 'id');
        }

        $ignore_stock_count = $wa->getConfig()->getGeneralSettings('ignore_stock_count');

        // Если необходимо подставить товар в наличии
        if ($find_available && !$ignore_stock_count) {
            $sql = "SELECT * FROM {$skus_model->getTableName()} WHERE `product_id` IN (i:ids) AND (`count` IS NULL OR `count` > 0) AND `available` = 1 ORDER BY `sort`";
            $available_skus = $skus_model->query($sql, array("ids" => $product_ids))->fetchAll('product_id', 2);
            if (method_exists($this, 'promoProductPrices')) {
                foreach ($available_skus as $available_skus_product_id => &$skus_list) {
                    $skus_list = array_map(function ($item) use ($available_skus_product_id) {
                        $item['product_id'] = $available_skus_product_id;
                        return $item;
                    }, $skus_list);
                    $this->promoProductPrices()->workupPromoSkus($skus_list, $products);
                }
            }
        }

        // Выполняем обработку артикулов
        foreach ($products as $k => &$p) {
            $sku = $p['skus'][$p['sku_id']];
            // Подставляем доступный артикул при необходимости
            if ($find_available && !$ignore_stock_count && (!$sku['available'] || ($sku['count'] <= 0 && $sku['count'] !== null)) && !empty($available_skus[$p['id']])) {
                $avail_sku = reset($available_skus[$p['id']]);
                $avail_sku['product_id'] = $p['id'];
                $p['skus'][$avail_sku['id']] = $avail_sku;
                $sku_ids[] = $avail_sku['id'];
                if ($avail_sku['image_id'] && !isset($images[$avail_sku['image_id']])) {
                    $images[$avail_sku['image_id']] = (new shopProductImagesModel())->getByField('id', $avail_sku['image_id']);
                }
            }
            shopRounding::roundSkus($p['skus'], array($p));
            $sku = $p['skus'][ifempty($avail_sku, 'id', $p['sku_id'])];

            // Для подарочных сертификатов пропускаем проверку доступности
            if ($is_giftcertificate_installed && $certificate_helper->isItemCertificate($k)) {
                $giftcertificates_helper->changeFrontendProductsCartItemCertificate($k, $sku, ref(['products' => [$p['id'] => $p]]), $certificates, $certificate_helper);
                $p['status'] = 1;
                $p['is_giftcertificate'] = 1;
            }

            // Если товар недоступен, удаляем его из набора
            $product_status = isset($p['status']) ? $p['status'] : (isset($p['product']['status']) ? $p['product']['status'] : 0);
            $is_available = $product_status && $sku['available'] && ($ignore_stock_count || $sku['count'] === null || $sku['count'] > 0);

            if (!$is_available) {
                unset($products[$k]);
                continue;
            }

            // Изменяем изображение
            if ($sku['image_id']) {
                $p['image_id'] = $sku['image_id'];
                $p['image_filename'] = isset($images[$p['image_id']]) ? $images[$p['image_id']]['filename'] : '';
                $p['image_description'] = isset($images[$p['image_id']]) ? $images[$p['image_id']]['description'] : '';
                $p['ext'] = isset($images[$p['image_id']]) ? $images[$p['image_id']]['ext'] : $p['ext'];
            }
            $p['sku_id'] = $sku['id'];
            if ($sku['compare_price'] == $sku['price']) {
                $sku['compare_price'] = 0;
            }
            $p['sku_code'] = $sku['sku'];
            $p['sku_name'] = $sku['name'];
            $p['count'] = (float) $sku['count'];
            $p['price'] = $sku['frontend_price'];
            $p['compare_price'] = $sku['frontend_compare_price'];
            $p['purchase_price'] = shop_currency($sku['purchase_price'], $p['currency'], $current_cur, false);
            $p['currency'] = $current_cur;

            // Если происходит создание заказа, тогда добавляем к названию товара название артикула
            if (waRequest::param('quickorder_create') && !empty($p['sku_name'])) {
                $p['name'] .= ' (' . $p['sku_name'] . ')';
            }

            if (!empty($p['services'])) {
                $p['active_services'] = array();
                foreach ($p['services'] as $service_id => $s) {
                    $variant_id = is_array($s) && isset($s['service_variant_id']) ? $s['service_variant_id'] : $s;
                    $service_id = is_array($s) && isset($s['service_id']) ? $s['service_id'] : $service_id;
                    $p['active_services'][$service_id] = $variant_id;
                }
            }
        }

        // Услуги
        if ($find_services && $products) {
            $params = array(
                'product_ids' => array_unique($product_ids),
                'sku_ids' => array_unique($sku_ids),
                'type_ids' => array_unique($type_ids),
            );
            $this->getServiceVars($products, $params);
        }
        return $products;
    }

    /**
     * Get services for products
     *
     * @param array $products
     * @param array $params
     */
    private function getServiceVars(&$products, $params)
    {
        $service_ids = array();
        $type_ids = $params['type_ids'];
        $product_ids = $params['product_ids'];
        $sku_ids = $params['sku_ids'];

        // get available services for all types of products
        $type_services_model = new shopTypeServicesModel();
        $rows = $type_services_model->getByField('type_id', $type_ids, true);
        $type_services = array();
        foreach ($rows as $row) {
            $service_ids[$row['service_id']] = $row['service_id'];
            $type_services[$row['type_id']][$row['service_id']] = true;
        }

        // get services for products and skus, part 1
        $product_services_model = new shopProductServicesModel();
        $rows = $product_services_model->getByProducts($product_ids);
        foreach ($rows as $i => $row) {
            if ($row['sku_id'] && !in_array($row['sku_id'], $sku_ids)) {
                unset($rows[$i]);
                continue;
            }
            $service_ids[$row['service_id']] = $row['service_id'];
        }

        $service_ids = array_unique(array_values($service_ids));

        // Get services
        $service_model = new shopServiceModel();
        $services = $service_model->getByField('id', $service_ids, 'id');
        shopRounding::roundServices($services);

        // get services for products and skus, part 2
        $product_services = $sku_services = array();
        shopRounding::roundServiceVariants($rows, $services);
        foreach ($rows as $row) {
            if (!$row['sku_id']) {
                $product_services[$row['product_id']][$row['service_id']]['variants'][$row['service_variant_id']] = $row;
            }
            if ($row['sku_id']) {
                $sku_services[$row['sku_id']][$row['service_id']]['variants'][$row['service_variant_id']] = $row;
            }
        }

        // Get service variants
        $variant_model = new shopServiceVariantsModel();
        $rows = $variant_model->getByField('service_id', $service_ids, true);
        shopRounding::roundServiceVariants($rows, $services);
        foreach ($rows as $row) {
            $services[$row['service_id']]['variants'][$row['id']] = $row;
            unset($services[$row['service_id']]['variants'][$row['id']]['id']);
        }

        // When assigning services into cart items, we don't want service ids there
        foreach ($services as &$s) {
            unset($s['id']);
        }
        unset($s);

        $migrate = !method_exists('shopProductServicesModel', 'workupItemServices');

        foreach ($products as $item_id => $item) {
            $p = $item;
            $item_services = array();
            // services from type settings
            if (isset($type_services[$p['type_id']])) {
                foreach ($type_services[$p['type_id']] as $service_id => &$s) {
                    $item_services[$service_id] = $services[$service_id];
                }
            }
            // services from product settings
            $product_id = isset($item['product_id']) ? $item['product_id'] : $item['id'];
            if (isset($product_services[$product_id])) {
                foreach ($product_services[$product_id] as $service_id => $s) {
                    if (!isset($s['status']) || $s['status']) {
                        if (!isset($item_services[$service_id])) {
                            $item_services[$service_id] = $services[$service_id];
                        }
                        // update variants
                        foreach ($s['variants'] as $variant_id => $v) {
                            if ($v['status']) {
                                if ($v['price'] !== null) {
                                    $item_services[$service_id]['variants'][$variant_id]['price'] = $v['price'];
                                }
                            } else {
                                unset($item_services[$service_id]['variants'][$variant_id]);
                            }
                            // default variant is different for this product
                            if ($v['status'] == shopProductServicesModel::STATUS_DEFAULT) {
                                $item_services[$service_id]['variant_id'] = $variant_id;
                            }
                        }
                    } elseif (isset($item_services[$service_id])) {
                        // remove disabled service
                        unset($item_services[$service_id]);
                    }
                }
            }
            // services from sku settings
            if (isset($sku_services[$item['sku_id']])) {
                foreach ($sku_services[$item['sku_id']] as $service_id => $s) {
                    if (!isset($s['status']) || $s['status']) {
                        // update variants
                        foreach ($s['variants'] as $variant_id => $v) {
                            if ($v['status']) {
                                if ($v['price'] !== null) {
                                    $item_services[$service_id]['variants'][$variant_id]['price'] = $v['price'];
                                }
                            } else {
                                unset($item_services[$service_id]['variants'][$variant_id]);
                            }
                        }
                    } elseif (isset($item_services[$service_id])) {
                        // remove disabled service
                        unset($item_services[$service_id]);
                    }
                }
            }
            foreach ($item_services as $s_id => &$s) {
                if (!$s['variants']) {
                    unset($item_services[$s_id]);
                    continue;
                }

                if ($s['currency'] == '%') {
                    if (!$migrate) {
                        shopProductServicesModel::workupItemServices($s, $item);
                    } else {
                        shopQuickorderPluginMigrate::workupItemServices($s, $item);
                    }
                }

                if (count($s['variants']) == 1) {
                    reset($s['variants']);
                    $v_id = key($s['variants']);
                    $v = $s['variants'][$v_id];
                    $s['variant_id'] = $v_id;
                    $s['price'] = $v['price'];
                    unset($s['variants']);
                }
                $s['id'] = $s_id;
            }
            unset($s);
            uasort($item_services, array('shopServiceModel', 'sortServices'));

            $products[$item_id]['services'] = $item_services;
        }

        foreach ($products as $item_id => $item) {
            $price = shop_currency($item['price'] * $item['quantity'], $item['currency'], null, false);
            if (isset($item['services'])) {
                foreach ($item['services'] as $s) {
                    if (!empty($s['id'])) {
                        if (isset($s['variants'])) {
                            $price += shop_currency($s['variants'][$s['variant_id']]['price'] * $item['quantity'], $s['currency'], null, false);
                        } else {
                            $price += shop_currency($s['price'] * $item['quantity'], $s['currency'], null, false);
                        }
                    }
                }
            }
            $products[$item_id]['full_price'] = $price;
        }
    }

}
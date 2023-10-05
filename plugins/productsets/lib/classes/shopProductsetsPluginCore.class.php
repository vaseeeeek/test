<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopProductsetsPluginCore
{
    private $validation;
    private $cart_items_quantity;

    public function __construct()
    {
        $this->validation = new shopProductsetsPluginValidation();
    }

    /**
     * Calculate discount for bundles
     *
     * @param array $items
     * @return array
     */
    public function calculateDiscount($items)
    {
        $result = array(
            'total_discount' => 0,
            'total_products_discount' => 0,
            'products' => [],
            'cart_ids' => []
        );

        $log = new shopProductsetsPluginLog();

        if ($items) {
            $products = array();
            foreach ($items as $k => $item) {
                if (empty($item['type']) || (!empty($item['type']) && $item['type'] == 'product')) {
                    $products[] = $item;
                }
            }

            if ($products) {
                $shop_cart = new shopProductsetsPluginCart();
                $set_model = new shopProductsetsPluginModel();
                $cart_sets = $shop_cart->getCartSets();
                $sets = $set_model->getSets(array('ids' => array_keys($cart_sets)));
                if ($sets && $cart_sets) {
                    $sku_ids = waUtils::getFieldValues($products, 'sku_id');
                    $this->cart_items_quantity = $shop_cart->getItemsQuantityBySkuIds($sku_ids);
                    $data_class = (new shopProductsetsData())->getProductData($sku_ids);
                    $products_data = $data_class->toArray();
                    // Настройки отображения
                    $sm = new shopProductsetsSettingsPluginModel();
                    $settings = $sm->getSettings(array_keys($sets), null, true);

                    $log->add('Unique sets' . ': ' . count($cart_sets));

                    // Выполняем перебор комплектов, добавленных пользователем в корзину
                    foreach ($cart_sets as $set_id => $cart_bundles) {
                        ksort($cart_bundles);

                        if (!isset($sets[$set_id]['processed'])) {
                            $sets[$set_id]['settings'] = isset($settings[$set_id]) ? $settings[$set_id] : array();
                            $sets[$set_id]['processed'] = 1;
                        }
                        $set = $sets[$set_id];

                        $log->add('*****');
                        $log->add('Set ID' . ' ' . $set['id'] . ' (' . 'total number of sets' . ': ' . count($cart_bundles) . ')');

                        foreach ($cart_bundles as $cart_id => $cart_set) {

                            $log->add('***');

                            $bundle_id = $cart_set['bundle_id'];
                            $cart_bundle_items = $cart_set['items'];
                            $validated_items = [];

                            $this->correctCartItemsQuantity();
                            $is_userbundle = (!$bundle_id && !empty($set['user_bundle']) && !isset($set['user_bundle']['processed']));

                            $log->add('Type of the set' . ': ' . ($is_userbundle ? 'User bundle' : 'Bundle'));

                            // Если переданный набор существует
                            if (!empty($set['bundle']['b' . $bundle_id]) || $is_userbundle) {
                                $bundle = !$is_userbundle ? $set['bundle']['b' . $bundle_id] : $set['user_bundle'];

                                $log->add('Add active product' . ': ' . $cart_set['include_product']);

                                // Проверяем наличие активного товара в наборе
                                if ($cart_set['include_product'] && !$this->checkActiveProduct($set, $bundle, $cart_bundle_items, $is_userbundle, $shop_cart, $validated_items)) {
                                    $log->add('ERROR:' . ' ' . 'Active product failed');
                                    continue;
                                }

                                // Проверка доступности набора
                                if (!$this->validation->isBundleAvailable($bundle, $is_userbundle ? 'userbundle' : 'bundle', $set_id)) {
                                    $log->add('ERROR:' . ' ' . 'Bundle is not available');
                                    $shop_cart->deleteByBundleId($bundle_id);
                                    continue;
                                }

                                // Перебираем все позиции набора
                                $i = -1;
                                if ($cart_set['include_product']) {
                                    ++$i;
                                }
                                $cart_bundle_items_values = array_values($cart_bundle_items);
                                $is_bundle_has_errors = 0;

                                if (!$is_userbundle) {
                                    $active_product = !empty($bundle['settings']['active_product']) ? $this->getActiveProduct($bundle, $cart_bundle_items) : [];
                                    foreach ($bundle['items'] as $item_key => $bundle_item) {
                                        // Пропускаем товар, который совпадает с активным
                                        if ($data_class->isProductEqualsToActiveProduct($bundle_item, $active_product)) {
                                            continue;
                                        }
                                        ++$i;
                                        $item_settings = $bundle_item['settings'];
                                        $cart_item = null;

                                        // Проверяем существование товара
                                        if (isset($cart_bundle_items[$item_key])) {
                                            $cart_item = $cart_bundle_items[$item_key];
                                            $cart_item['settings'] = $bundle_item['settings'];
                                        } // Проверяем, возможно используется альтернативный товар
                                        elseif (!empty($bundle_item['alternative']) && !empty($cart_bundle_items_values[$i]) && $cart_bundle_items_values[$i]['parent_id'] == $bundle_item['id']) {
                                            $cart_item = $cart_bundle_items_values[$i];
                                            $cart_item['settings'] = array_merge($item_settings, $bundle_item['alternative']['i' . $cart_bundle_items_values[$i]['bundle_item_id']]['settings']);
                                            $bundle_item = $bundle_item['alternative']['i' . $cart_bundle_items_values[$i]['bundle_item_id']];
                                        }

                                        // Проверяем, достаточно ли количества у товара
                                        if (!$this->checkCartItemQuantity($cart_item, $bundle_item, $item_settings, $is_userbundle, $validated_items)) {
                                            $log->add('ERROR:' . ' ' . 'Failed on quantity check');
                                            $log->add('Isset cart item' . ': ' . ($cart_item !== null ? 1 : 0));
                                            $log->add('Sku Id: ' . $bundle_item['sku_id'] . ', product ID: ' . $bundle_item['product_id']);
                                            $is_bundle_has_errors = 1;
                                            break;
                                        }
                                    }
                                } // Пользовательский комплект
                                else {
                                    // Если в наборе нет достаточного количества обязательных товаров - прекращаем обработку
                                    if (!empty($bundle['required']) && array_diff_key($bundle['required'], $cart_bundle_items)) {
                                        $log->add('ERROR:' . ' ' . 'Required items not enough');
                                        continue;
                                    }

                                    $log->add('Starting analyzing cart bundle items');

                                    foreach ($cart_bundle_items as $item_key => $cart_item) {

                                        // Работаем с обязательными товарами
                                        if (!empty($bundle['required'][$item_key])) {
                                            $cart_item['settings'] = $item_settings = $bundle['required'][$item_key]['settings'];

                                            // Проверяем, достаточно ли количества у товара
                                            if (!$this->checkCartItemQuantity($cart_item, $bundle['required'][$item_key], $item_settings, $is_userbundle, $validated_items)) {
                                                $log->add('ERROR:' . ' ' . 'Failed on quantity check');
                                                $log->add('Sku Id: ' . $cart_item['sku_id'] . ', product ID: ' . $cart_item['product_id']);
                                                $is_bundle_has_errors = 1;
                                                break;
                                            }
                                        } // Работаем с обычными товарами. Если встретился активный, его пропускаем, потому что он был обработан в самом начале
                                        elseif (!$cart_item['is_active'] && isset($bundle['groups'])) {

                                            $log->add('Starting analyzing groups');

                                            // Проверяем наличие товара в группах
                                            foreach ($bundle['groups'] as $group_key => $group) {

                                                // Проверяем наличие обычных товаров в группе
                                                if (isset($group['items'][$item_key])) {

                                                    // Если запрещен множественный выбор, удаляем использованную группу
                                                    if (empty($group['settings']['multiple'])) {
                                                        unset($bundle['groups'][$group_key]);
                                                    }

                                                    $item_settings = array_merge($group['items'][$item_key]['settings'], [
                                                        'choose_skus' => ifset($group['settings'], 'choose_skus', 0),
                                                        'choose_quantity' => ifset($group['settings'], 'choose_quantity', 0),
                                                    ]);

                                                    // Проверяем, достаточно ли количества у товара
                                                    $this->checkGroupItemQuantity($cart_item, $group['items'][$item_key], $bundle, $group, $item_settings,$validated_items);
                                                    continue 2;
                                                }
                                                // Проверяем наличие товаров в категориях, списках, типах
                                                // Самый затратный процесс
                                                elseif (!empty($group['types'])) {
                                                    $item_key = 't' . $cart_item['sku_id'];

                                                    if (!isset($group['type_items'])) {
                                                        // Получаем все товары, принадлежащие группе
                                                        $group['type_items'] = $bundle['groups'][$group_key]['type_items'] = (new shopProductsetsData())->getProductData()->getGroupTypesProducts($group['types']);
                                                        $group['type_items_product_ids'] = $bundle['groups'][$group_key]['type_items_product_ids'] = waUtils::getFieldValues($group['type_items'], 'sku_id', 'product_id');
                                                    }
                                                    if (isset($group['type_items'][$item_key]) || (!empty($group['settings']['choose_skus']) && isset($group['type_items_product_ids'][$cart_item['product_id']]))) {
                                                        $group_item = isset($group['type_items'][$item_key]) ? $group['type_items'][$item_key] : $group['type_items']['t' . $group['type_items_product_ids'][$cart_item['product_id']]];

                                                        // Удаляем обработанный товар из набора групп.
                                                        // В одной группе запрещено использовать дважды один и тот же товар
                                                        if (isset($group['type_items'][$item_key])) {
                                                            unset($group['type_items'][$item_key], $bundle['groups'][$group_key]['type_items'][$item_key]);
                                                        }

                                                        // Если запрещен множественный выбор, удаляем использованную группу
                                                        if (empty($group['settings']['multiple'])) {
                                                            unset($bundle['groups'][$group_key]);
                                                        }

                                                        $item_settings = [
                                                            'choose_skus' => ifset($group['settings'], 'choose_skus', 0),
                                                            'choose_quantity' => ifset($group['settings'], 'choose_quantity', 0),
                                                        ];

                                                        // Проверяем, достаточно ли количества у товара
                                                        $this->checkGroupItemQuantity($cart_item, $group_item, $bundle, $group, $item_settings, $validated_items);
                                                        continue 2;
                                                    }
                                                }
                                            }
                                        } else {
                                            if ($cart_item['is_active']) {
                                                $log->add('Skip active product');
                                            }

                                            $log->add('Isset group products: ' . isset($bundle['groups']));
                                        }
                                    }

                                    $sets[$set_id]['user_bundle']['processed'] = 1;
                                }

                                // Если набор не содержит критических ошибок и имеются товары, рассчитываем скидки
                                if (!$is_bundle_has_errors && $validated_items) {
                                    // Проверяем минимальное/максимальное количество товаров у пользовательского набора
                                    if ($is_userbundle && (
                                            (!empty($bundle['settings']['min']) && $bundle['settings']['min'] > count($validated_items))
                                            || (!empty($bundle['settings']['max']) && $bundle['settings']['max'] < count($validated_items))
                                        )
                                    ) {
                                        continue;
                                    }
                                    // Необходимо, чтобы ключи переданных товаров и ключи проверенных товаров полностью совпадали
                                    // Это будет означать, что комплект полностью собран
                                    if (!array_diff_key($cart_bundle_items, $validated_items)) {
                                        $this->decreaseCartItemsQuantity();
                                        $ruble_sign = !empty($set['general']['ruble_sign']) && $set['general']['ruble_sign'] == 'html' ? 'html' : 'rub';
                                        $round = ifempty($set, 'settings', 'other', 'round', 'not');
                                        $validated_items = $this->normalizeItems($data_class, $validated_items, $products_data, $bundle['settings'], $ruble_sign, $round);
                                        $bundle_discounts = $this->calculateDiscounts($validated_items, $bundle, $data_class, $round);

                                        $result['total_discount'] += $bundle_discounts['common_discount'];

                                        foreach ($bundle_discounts['items'] as $bundle_discount_item) {
                                            if (!empty($bundle_discount_item['discount'])) {
                                                $this->addDiscountProduct($result, $bundle_discount_item, $set, $cart_id, $products_data);
                                            }
                                        }
                                        if (!empty($bundle_discounts['common_discount'])) {
                                            $this->addCommonDiscount($result, $bundle_discounts['items'], $bundle_discounts['common_discount'], $set, $cart_id);
                                        }
                                        $log->add('SUCCESS');
                                    } else {
                                        $log->add('ERROR:' . ' ' . 'Validate items not enough');
                                    }
                                } else {
                                    $log->add('ERROR:' . ' ' . 'Bundle has errors');
                                }
                            } else {
                                $log->add('Bundle not exists.' . ' #' . $cart_set['bundle_id']);
                            }
                        }
                    }
                }
            }
        }
        $log->save($result);
        return $result;
    }

    /**
     * Is group item quantity enough to continue proceed
     *
     * @param array $cart_item - item from shop_productsets_cart_items
     * @param array $group_item
     * @param array $bundle
     * @param array $group
     * @param array $item_settings
     * @param array $validated_items
     */
    private function checkGroupItemQuantity($cart_item, $group_item, &$bundle, $group, $item_settings, &$validated_items)
    {
        // Добавляем скидку для группы
        if ($bundle['settings']['discount_type'] == 'each') {
            if ($group['settings']['discount_type'] == 'common') {
                if ($group['settings']['currency'] === '%') {
                    $item_settings = array_merge($item_settings, $this->getDiscountSettings($group));
                } else {
                    // Для общих скидок в валюте устанавливаем флаг, что необходимо отдельно просчитать скидку для группы
                    $bundle['check_group_discounts'] = 1;
                    $item_settings['discount'] = 0;
                    $item_settings += ['group_id' => $group['id']];
                }
            } elseif ($group['settings']['discount_type'] == 'each') {
                $item_settings += array_merge($item_settings, $this->getDiscountSettings($group_item));
            }
        }
        $cart_item['settings'] = $item_settings;

        // Проверяем, достаточно ли количества у товара
        $this->checkCartItemQuantity($cart_item, $group_item, $item_settings, 1,$validated_items);
    }

    /**
     * Get discount settings
     *
     * @param array $data
     * @return array
     */
    private function getDiscountSettings($data)
    {
        return ['discount' => $data['settings']['discount'], 'currency' => $data['settings']['currency']];
    }

    /**
     * Is item quantity enough to continue proceed
     *
     * @param array $cart_item - item from shop_productsets_cart_items
     * @param array $bundle_item - item in the bundle from admin page. If skus changing is enabled for the bundle, than this value
     *                                  will be differ from the $cart_item['sku_id']
     * @param array $item_settings
     * @param bool $is_userbundle
     * @param array $validated_items - items, passed all validations
     * @return bool
     */
    private function checkCartItemQuantity($cart_item, $bundle_item, $item_settings, $is_userbundle, &$validated_items)
    {
        // Если товара не существует, или нельзя менять артикулы, но в корзине передан другой, или ID товара не совпадает
        if (!$cart_item
            || (empty($item_settings['choose_skus']) && $bundle_item['sku_id'] !== $cart_item['sku_id'])
            || ($is_userbundle && $bundle_item['product_id'] !== $cart_item['product_id'])) {
            if ($this->validation->isItemRequired($item_settings)) {
                return false;
            }
        } else {
            // Проверяем, хватает ли кол-ва
            if ($item_quantity = $this->validation->checkItemQuantity($this->cart_items_quantity, $cart_item)) {
                $cart_item['quantity'] = $item_quantity;
                $validated_items['i' . $cart_item['bundle_item_id']] = $cart_item;
            } elseif ($this->validation->isItemRequired($item_settings)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Does active product exist and is it enough
     *
     * @param array $set
     * @param array $bundle
     * @param array $cart_bundle_items - items from from shop_productsets_cart_items
     * @param bool $is_userbundle
     * @param shopCart $shop_cart
     * @param array $validated_items - items, passed all validations
     * @return bool
     */
    private function checkActiveProduct($set, $bundle, $cart_bundle_items, $is_userbundle, $shop_cart, &$validated_items)
    {
        if (!empty($bundle['settings']['active_product'])) {

            // Если нет активного товара, переходим к другому набору
            if (!$active_product = $this->getActiveProduct($bundle, $cart_bundle_items)) {
                $shop_cart->deleteByBundleId($is_userbundle ? 0 : $bundle['id'], $set['id']);
                return false;
            }

            // Если отображение комплекта для активного товара недоступно, переходим к другому набору
            if (!$this->validation->isSetAvailableForProduct($set, new shopProduct($active_product['product_id']))) {
                $shop_cart->deleteByBundleId($is_userbundle ? 0 : $bundle['id'], $set['id']);
                return false;
            }

            $active_product['settings'] = $bundle['active']['settings'];
            // Проверяем, хватает ли кол-ва
            if (!$item_quantity = $this->validation->checkItemQuantity($this->cart_items_quantity, $active_product)) {
                return false;
            }

            $active_product['quantity'] = $item_quantity;
            $validated_items['i' . $active_product['bundle_item_id']] = $active_product;
        }
        return true;
    }

    /**
     * Get active product for the bundle
     *
     * @param array $bundle
     * @param array $cart_bundle_items
     * @return array
     */
    private function getActiveProduct($bundle, $cart_bundle_items)
    {
        if (isset($cart_bundle_items['i' . $bundle['active']['id']]) && $cart_bundle_items['i' . $bundle['active']['id']]['is_active']) {
            return $cart_bundle_items['i' . $bundle['active']['id']];
        }
        return [];
    }

    private function decreaseCartItemsQuantity()
    {
        // Обнуляем значение, на которое было уменьшено количество товаров
        foreach ($this->cart_items_quantity as &$item) {
            $item['decrease'] = 0;
        }
    }

    private function correctCartItemsQuantity()
    {
        // Если количество уменьшалось и обработка набора была прервана, возвращаем данные о количестве для корректной работы
        foreach ($this->cart_items_quantity as &$item) {
            if (!empty($item['decrease'])) {
                $item['quantity'] += $item['decrease'];
            }
            $item['decrease'] = 0;
        }
    }

    /**
     * Normalize bundle cart items and calculate individual discounts
     *
     * @param shopProductsetsProductData $product_data_class
     * @param array $items
     * @param array $products
     * @param array $bundle_settings
     * @param string $ruble_sign
     * @param string $round
     * @return array
     */
    private function normalizeItems($product_data_class, $items, $products, $bundle_settings, $ruble_sign = 'rub', $round = 'not')
    {
        foreach ($items as &$item) {
            $item['type'] = 'sku';
            $item = $product_data_class->normalizeItem($item, $products, $bundle_settings, $ruble_sign, $round);
        }
        unset($item);

        return $items;
    }

    /**
     * Calculate common and available discounts
     *
     * @param array $items
     * @param array $bundle
     * @param shopProductsetsProductData $product_data_class
     * @param string $round
     * @return array
     */
    private function calculateDiscounts($items, $bundle, $product_data_class, $round = 'not')
    {
        $bundle['items'] = $items;
        unset($bundle['active'], $bundle['required']);
        $set = $product_data_class->calculateDiscounts(array(
            'bundle' => array($bundle)
        ), $round);
        return $set['bundle'][0];
    }

    /**
     * Add information about all discounts of product to result array
     *
     * @param array $result
     * @param array $item
     * @param array $set
     * @param int $cart_id
     * @param array $products_data
     */
    private function addDiscountProduct(&$result, $item, $set, $cart_id, $products_data)
    {
        $discount = floatval($item['discount'] * $item['quantity']);

        // Не даем начислить больше, чем нужно
        $max_discount = $item['quantity'] * $products_data[$item['sku_id']]['price'];
        if ($discount > $max_discount) {
            $discount = $max_discount;
        }

        // Собираем информацию о всех скидках для товаров
        $result['total_discount'] += $discount;
        $result['total_products_discount'] += $discount;

        // Собираем информацию о всех скидках для товара
        if (!isset($result['products'][$item['sku_id']])) {
            $result['products'][$item['sku_id']] = array(
                'sku_id' => $item['sku_id'],
                'product_id' => $item['product_id'],
                'total_discount' => (float) $discount,
                'total_quantity' => (float) $item['quantity'],
            );
        } else {
            $result['products'][$item['sku_id']]['total_discount'] += (float) $discount;
            $result['products'][$item['sku_id']]['total_quantity'] += (float) $item['quantity'];
        }

        // Собираем информацию о скидках для комплектов и наборов
        $this->createCartInfoDiscount($result, $cart_id, $set);

        $result['cart_ids'][$cart_id]['total_discount'] += $discount;
        $result['cart_ids'][$cart_id]['products'][$item['sku_id']] = $item['quantity'];
    }

    /**
     * Add information about all common discounts to result array
     *
     * @param array $result
     * @param array $items
     * @param float $discount
     * @param array $set
     * @param int $cart_id
     */
    private function addCommonDiscount(&$result, $items, $discount, $set, $cart_id)
    {
        // Собираем информацию о скидках для комплектов и наборов
        $this->createCartInfoDiscount($result, $cart_id, $set);
        $result['cart_ids'][$cart_id]['total_discount'] += $discount;

        foreach ($items as $item) {
            $result['cart_ids'][$cart_id]['products'][$item['sku_id']] = $item['quantity'];
        }
    }

    /**
     * Create result array for discount
     *
     * @param array $result
     * @param int $cart_id
     * @param array $set
     */
    private function createCartInfoDiscount(&$result, $cart_id, $set)
    {
        if (!isset($result['cart_ids'][$cart_id])) {
            $result['cart_ids'][$cart_id] = [
                'total_discount' => 0,
                'products' => [],
                'name' => ifempty($set['general']['name'], _wp('Set #' . $set['id']))
            ];
        }
    }

}


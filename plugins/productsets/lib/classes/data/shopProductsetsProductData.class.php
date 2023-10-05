<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopProductsetsProductData extends shopProductsetsHtmlBuilder
{
    const IMAGE_SIZE = '96x96';
    const FRONTEND_IMAGE_SIZE = '256x0';
    protected $data;
    private $active_product;
    private static $ignore_stock_count;
    private static $is_frontend;
    private static $wa;
    private $settings;
    private $active_set;

    public function __construct($sku_ids)
    {
        $this->settings = (new shopProductsetsPluginHelper())->getSettings();
        self::$wa = wa('shop');
        self::$ignore_stock_count = self::$wa->getConfig()->getGeneralSettings('ignore_stock_count');
        self::$is_frontend = self::$wa->getEnv() == 'frontend';
        if ($sku_ids) {
            $this->data = $this->getProducts($sku_ids);
        }
    }

    /**
     * Normalize set products format
     *
     * @param array $set
     * @param array $products
     * @return array
     */
    public function normalizeProducts($set, $products = [])
    {
        if (!$products && $this->data) {
            $products = $this->data;
        }
        if (!$products) {
            return $set;
        }

        $this->active_set = $set;
        $find_product_skus = [];
        $replace_skus = false;
        $ruble_sign = !empty($set['general']['ruble_sign']) && $set['general']['ruble_sign'] == 'html' ? 'html' : 'rub';

        if (!empty($set['bundle'])) {
            foreach ($set['bundle'] as $b_id => &$bundle) {
                if (!empty($bundle['items'])) {
                    foreach ($bundle['items'] as $b_item_id => &$item) {
                        if (!empty($products[$item['sku_id']])) {
                            $item = $this->normalizeItem($item, $products, $bundle['settings'], $ruble_sign);

                            // Данный параметр необходим, чтобы в случае наличия альтернативных товаров, заменить ими отсутствующий текущий
                            $alt_has_available_skus = 0;

                            if (!empty($item['alternative'])) {
                                foreach ($item['alternative'] as $k => &$it) {
                                    if (!empty($products[$it['sku_id']])) {
                                        // Удаляем товар, который совпадает с активным
                                        if ($this->isProductEqualsToActiveProduct($it) && !empty($bundle['active'])) {
                                            unset($set['bundle'][$b_id]['items'][$b_item_id]['alternative'][$k]);
                                        } else {
                                            $it = $this->normalizeItem($it, $products, $bundle['settings'], $ruble_sign);
                                            $it['settings'] = $it['settings'] + $item['settings'];
                                            // Если текущего товара нет в наличии, пытаемся подобрать ему замену
                                            if (!$this->isProductAvailable($it)) {
                                                if ($this->isProductHasAvailableSkus($it)) {
                                                    $it['force_change_sku'] = 999999999;
                                                    $find_product_skus[$it['sku_id']] = $it['product_id'];
                                                    if (!$alt_has_available_skus) {
                                                        $alt_has_available_skus = $k;
                                                    }
                                                } /* Скрываем альтернативные товары, которых нет в наличии */
                                                else {
                                                    unset($set['bundle'][$b_id]['items'][$b_item_id]['alternative'][$k]);
                                                }
                                            } elseif (!$alt_has_available_skus) {
                                                $alt_has_available_skus = $k;
                                            }
                                        }
                                    } else {
                                        unset($set['bundle'][$b_id]['items'][$b_item_id]['alternative'][$k]);
                                    }

                                    unset($it);
                                }
                            }

                            // Если текущего товара нет в наличии, пытаемся подобрать ему замену
                            if (!$this->isProductAvailable($item)) {
                                // Если у товара можно выбирать артикулы
                                if ($this->isProductHasAvailableSkus($item)) {
                                    $find_product_skus[$item['sku_id']] = $item['product_id'];
                                    $item['force_change_sku'] = 999999999;
                                } // Если есть доступный альтернативный товар
                                elseif ($alt_has_available_skus) {
                                    $item['force_change_sku'] = $alt_has_available_skus;
                                    $replace_skus = true;
                                } elseif (!empty($bundle['settings']['hide_empty_stocks']) && !empty($item['settings']['delete_product'])) {
                                    unset($set['bundle'][$b_id]['items'][$b_item_id]);
                                }
                            } // Удаляем или подменяем товар, который совпадает с активным
                            elseif ($this->isProductEqualsToActiveProduct($item) && !empty($bundle['active'])) {
                                if ($alt_has_available_skus) {
                                    $item['force_change_sku'] = $alt_has_available_skus;
                                    $replace_skus = true;
                                } else {
                                    unset($set['bundle'][$b_id]['items'][$b_item_id]);
                                }
                            }
                        } else {
                            unset($set['bundle'][$b_id]['items'][$b_item_id]);
                        }
                        unset($item);
                    }
                }
                if (!empty($bundle['active']) && self::$is_frontend) {
                    if ($this->active_product) {
                        $bundle['active']['product_id'] = $this->active_product['id'];
                        $bundle['active']['sku_id'] = $this->active_product['sku_id'];
                        $bundle['active'] = $this->normalizeItem($bundle['active'], $products, $bundle['settings'], $ruble_sign);

                        // Если текущего товара нет в наличии, пытаемся подобрать ему замену
                        $this->isItemInStock($bundle['active'], $find_product_skus);
                    } else {
                        unset($set['bundle'][$b_id]['active']);
                    }
                }
            }
        }

        $userbundle_items_count = 0;
        if (!empty($set['user_bundle']['groups'])) {
            foreach ($set['user_bundle']['groups'] as $group_key => &$group) {
                if (!empty($group['items'])) {
                    $settings = $set['user_bundle']['settings']['discount_type'] == 'each' ? $group['settings'] : $set['user_bundle']['settings'];
                    foreach ($group['items'] as $item_key => &$item) {
                        if (!empty($products[$item['sku_id']])) {
                            $item = $this->normalizeItem($item, $products, $settings, $ruble_sign);
                            $item['settings'] = array_merge($item['settings'], [
                                'choose_skus' => ifset($group['settings'], 'choose_skus', 0),
                                'choose_quantity' => ifset($group['settings'], 'choose_quantity', 0),
                            ]);

                            // Если текущего товара нет в наличии, удаляем его
                            if (!$this->isItemInStock($item, $find_product_skus)) {
                                unset($set['user_bundle']['groups'][$group_key]['items'][$item_key]);
                            }
                        } else {
                            unset($set['user_bundle']['groups'][$group_key]['items'][$item_key]);
                        }
                        unset($item);
                    }
                    // Если запрещен множественный выбор, считаем, что максимальное кол-во товаров, которые можно добавить в набор, равно 1
                    $userbundle_items_count += (!empty($group['settings']['multiple']) ? count($set['user_bundle']['groups'][$group_key]['items']) : 1);
                }
            }
        }
        if (!empty($set['user_bundle']['required'])) {
            foreach ($set['user_bundle']['required'] as $k => &$item) {
                if (!empty($products[$item['sku_id']])) {
                    $item = $this->normalizeItem($item, $products, $set['user_bundle']['settings'], $ruble_sign);

                    // Если обязательного товара нет в наличии, удаляем весь комплект
                    if (!$this->isItemInStock($item, $find_product_skus)) {
                        unset($set['user_bundle']);
                    }
                } else {
                    unset($set['user_bundle']['required'][$k]);
                }
                unset($item);
            }
            if (!empty($set['user_bundle']['required'])) {
                $userbundle_items_count += count($set['user_bundle']['required']);
            }
        }
        if (!empty($set['user_bundle']['active']) && self::$is_frontend) {
            if ($this->active_product) {
                $set['user_bundle']['active']['product_id'] = $this->active_product['id'];
                $set['user_bundle']['active']['sku_id'] = $this->active_product['sku_id'];
                $set['user_bundle']['active'] = $this->normalizeItem($set['user_bundle']['active'], $products, $set['user_bundle']['settings'], $ruble_sign);

                // Если активного товара нет в наличии, удаляем весь блок комплектов
                if (!$this->isItemInStock($set['user_bundle']['active'], $find_product_skus)) {
                    unset($set['user_bundle']);
                }
            } else {
                unset($set['user_bundle']['active']);
            }
        }

        if (!empty($set['user_bundle'])) {
            $set['user_bundle']['items_quantity'] = $userbundle_items_count;
        }

        if (!self::$is_frontend) {
            if (!empty($set['settings']['product']['products'])) {
                $set['display'] = [];
                foreach ($set['settings']['product']['products'] as $dirty_sku_id => $p_id) {
                    $sku_id = substr($dirty_sku_id, 1);
                    if (!empty($products[$sku_id])) {
                        $key = 's' . $sku_id;
                        $set['display'][$key] = $this->normalizeItem(array(
                            'id' => $p_id,
                            'sku_id' => $sku_id,
                            'type' => 'product'
                        ), $products, [], $ruble_sign);
                        $set['display'][$key]['label'] = $set['display'][$key]['name'];
                    }
                }
            }
        } else {
            // Получаем информацию об артикулах, которые есть в наличии и производим замену отсутствующих товаров
            $available_skus = $new_sku_ids = [];
            if ($find_product_skus && !self::$ignore_stock_count) {
                $skus_model = new shopProductSkusModel();
                $sql = "SELECT * FROM {$skus_model->getTableName()} 
                        WHERE `product_id` IN (i:ids) AND (`count` IS NULL OR `count` > 0) AND `available` = 1 ORDER BY `sort`";
                foreach ($skus_model->query($sql, array("ids" => $find_product_skus))->fetchAll('product_id', 2) as $p_id => $avail_skus) {
                    $available_skus[$p_id] = $avail_skus;
                    $new_sku_ids += waUtils::getFieldValues($avail_skus, 'id', 'id');
                }
                // Добавляем новые артикулы в обработку
                $products += $this->getProducts($new_sku_ids);
            }
            if ($replace_skus || $available_skus) {
                // Выполняем подмену артикулов
                $set = $this->replaceSkus($set, $available_skus, $products, $ruble_sign);
            }

            // Дополнительные проверки
            $set = $this->afterNormalize($set);

            // Удаляем повторяющиеся товары для комплектов пользователя
            $this->removeDuplicates($set);
        }

        return $set;
    }

    /**
     * Some additional checks for the set
     *
     * @param array $set
     * @return array
     */
    private function afterNormalize($set)
    {
        // Проверяем, была ли проверка настройки "Если в наборе имеются позиции, которых нет в наличии и нельзя заказать, тогда скрыть набор"
        if (!empty($set['bundle']) && !isset($set['skus_replaced'])) {
            foreach ($set['bundle'] as $bundle_k => $bundle) {
                // Если необходимо скрывать набор в случае отсутствия остатков у любого из товаров.
                if (!empty($bundle['settings']['hide_on_empty_stocks'])) {
                    if (!empty($bundle['items'])) {
                        foreach ($bundle['items'] as $bundle_items_k => $item) {
                            if (!$this->isProductAvailable($item)) {
                                // Прекращаем обработку комплекта и скрываем его
                                unset($set['bundle'][$bundle_k]);
                                continue;
                            }
                        }
                    }

                    if (!empty($bundle['active']) && $this->active_product) {
                        // Если необходимо скрывать набор в случае отсутствия остатков у любого из товаров.
                        if (!empty($bundle['settings']['hide_on_empty_stocks']) && !$this->isProductAvailable($bundle['active'])) {
                            // Прекращаем обработку комплекта и скрываем его
                            unset($set['bundle'][$bundle_k]);
                            continue;
                        }
                    }
                }
            }
        }
        return $set;
    }

    /**
     * Check, if item is available or has available skus.
     *
     * @param array $item
     * @param array $find_product_skus
     * @return array|int
     */
    private function isItemInStock(&$item, &$find_product_skus)
    {
        // Если текущего товара нет в наличии, пытаемся подобрать ему замену
        if (!$this->isProductAvailable($item)) {
            // Если у товара можно выбирать артикулы
            if ($this->isProductHasAvailableSkus($item)) {
                $find_product_skus[$item['sku_id']] = $item['product_id'];
                $item['force_change_sku'] = 999999999;
            } else {
                return false;
            }
        }
        return true;
    }

    /**
     * @param array $set
     * @param string $round
     * @return mixed
     */
    public function calculateDiscounts($set, $round = '')
    {
        $ruble_sign = (!empty($set['general']['ruble_sign']) && $set['general']['ruble_sign'] == 'html') ? 'html' : 'rub';
        $round = $round ? $round : ifempty($set, 'settings', 'other', 'round', 'not');
        if (!empty($set['bundle'])) {
            foreach ($set['bundle'] as &$bundle) {
                $bundle['common_discount'] = ifset($bundle['common_discount'], 0);

                // Скидка в зависимости от количества
                if ($this->isAvailDiscount($bundle)) {
                    if (!empty($bundle['items']) || !empty($bundle['active'])) {
                        $this->getAvailDiscount($bundle, $ruble_sign, $round);
                    }
                }

                // Общая скидка на комплект в валюте
                if ($this->isCommonDiscountInCurrency($bundle)) {
                    $bundle['common_discount'] += $bundle['settings']['frontend_discount'];
                }

                // Общая скидка в валюте для групп (пользовательские наборы)
                if (!empty($bundle['check_group_discounts'])) {
                    $bundle['common_discount'] += $this->getGroupCommonDiscounts($bundle);
                }

                $bundle['common_discount'] = shopProductsetsPluginHelper::round($bundle['common_discount'], $round);
                $this->getTotalPrices($bundle);
            }
        }
        return $set;
    }

    /**
     * Get common group discounts
     *
     * @param array $bundle
     * @return float|int
     */
    private function getGroupCommonDiscounts($bundle)
    {
        $total_discount = 0;
        foreach ($bundle['items'] as $item) {
            // Считаем допустимую скидку для каждого товара
            if (!empty($item['settings']['group_id']) && !empty($bundle['groups']['g' . $item['settings']['group_id']]['settings']['frontend_discount'])) {
                $group_discount = &$bundle['groups']['g' . $item['settings']['group_id']]['settings']['frontend_discount'];
                if ($item['clear_price'] * $item['quantity'] < $group_discount) {
                    $item_discount = $item['clear_price'] * $item['quantity'];
                    $group_discount -= $item_discount;
                } else {
                    $item_discount = $group_discount;
                    $group_discount = 0;
                }
                $total_discount += $item_discount;
                unset($group_discount);
            }
        }
        return $total_discount;
    }

    /**
     * If type of discount is "available"
     *
     * @param array $bundle
     * @return bool
     */
    private function isAvailDiscount($bundle)
    {
        return (!empty($bundle['settings']['discount_type']) && $bundle['settings']['discount_type'] == 'avail')
            || (!empty($bundle['discount_type']) && $bundle['discount_type'] == 'avail');
    }

    /**
     * If type of discount is "common" and it is not percentage
     *
     * @param array $bundle
     * @return bool
     */
    private function isCommonDiscountInCurrency($bundle)
    {
        return !empty($bundle['settings']['discount_type']) && $bundle['settings']['discount_type'] == 'common' && $bundle['settings']['currency'] !== '%' && !empty($bundle['settings']['frontend_discount']);
    }

    /**
     * Get bundle total values and total discount value
     *
     * @param array $bundle
     */
    private function getTotalPrices(&$bundle)
    {
        // Собираем товары и подсчитываем количество активны
        $items = $this->getInstockItems($bundle);

        $bundle['original_total'] = 0;
        $bundle['total'] = 0;
        $bundle['discount'] = 0;

        foreach ($items as $item) {
            $quantity = !empty($item['settings']['choose_quantity']) && isset($item['quantity']) ? $item['quantity'] : (isset($item['settings']['quantity']) ? $item['settings']['quantity'] : 1);
            $bundle['original_total'] += $item['clear_original_price'] * $quantity;
            $bundle['discount'] += ($item['clear_original_price'] - $item['clear_price']) * $quantity;
            $bundle['total'] += $item['clear_price'] * $quantity;
        }

        $bundle['total'] -= $bundle['common_discount'];
        $bundle['discount'] += $bundle['common_discount'];

        if ($bundle['total'] < 0) {
            $bundle['total'] = 0;
            $bundle['discount'] = $bundle['original_total'];
        }
    }

    /**
     * If discount specified, change product price and compare price
     *
     * @param array $data
     * @param float $discount
     * @param string $ruble_sign
     * @return array
     */
    private function changePrices($data, $discount, $ruble_sign = 'rub')
    {
        if ($data && $discount > 0) {
            // Если не было зачеркнутой цены, создадим ее из старой цены, потому что у товара имеется скидка
            if (empty($data['clear_compare_price'])) {
                $data['compare_price'] = $data['price'];
                $data['clear_compare_price'] = $data['clear_price'];
            }
            // Меняем цену товара
            $data['clear_price'] -= $discount;
            if ($data['clear_price'] < 0) {
                $data['clear_price'] = 0;
            }
            $data['price'] = $ruble_sign == 'html' ? shopProductsetsPluginHelper::shop_currency_html($data['clear_price'], true) : shopProductsetsPluginHelper::shop_currency($data['clear_price'], true);
            $data['discount'] = $discount;
        }
        return $data;
    }

    /**
     * Calculate available types of discount.
     *
     * @param array $bundle
     * @param string $ruble_sign
     * @param string $round
     */
    private function getAvailDiscount(&$bundle, $ruble_sign = 'rub', $round = 'not')
    {
        // Собираем товары и подсчитываем количество активных
        $items = $this->getInstockItems($bundle);

        if (ifset($bundle['settings']['avail_discount_type'], 'common') == 'every') {
            $this->getAvailEveryDiscount($bundle, $items, $ruble_sign, $round);
        } else {
            $this->getAvailCommonDiscount($bundle, $items, $ruble_sign, $round);
        }
    }

    /**
     * Get in stock items from the bundle including active item
     *
     * @param array $bundle
     * @return array
     */
    private function getInstockItems(&$bundle)
    {
        $items = [];
        if (!empty($bundle['items'])) {
            foreach ($bundle['items'] as &$item) {
                if (!$item['not_in_stock']) {
                    $items[] = &$item;
                }
            }
            unset($item);
        }

        if (!empty($bundle['required'])) {
            foreach ($bundle['required'] as &$item) {
                $items[] = &$item;
            }
            unset($item);
        }

        if (!empty($bundle['active']) && !$bundle['active']['not_in_stock']) {
            $items[] = &$bundle['active'];
        }
        return $items;
    }

    /**
     * Calculate available-common type of discount.
     *
     * @param array $bundle
     * @param array $items
     * @param string $ruble_sign
     * @param string $round
     */
    private function getAvailCommonDiscount(&$bundle, &$items, $ruble_sign = 'rub', $round = 'not')
    {
        $count = count($items) - 1;

        if ($count !== -1 && !empty($bundle['settings']['chain']['value'])) {
            // Не даем общему количеству активных товаров уйти за пределы доступных скидочных позиций
            end($bundle['settings']['chain']['value']);
            $max_count = key($bundle['settings']['chain']['value']);
            if ($count > $max_count) {
                $count = $max_count;
            }
            // Получаем значение скидки: процент или валюта
            $discount_value = $this->getChainDiscountValue($bundle, $count);
            $discount_currency = $this->getChainCurrencyValue($bundle, $count);

            $discount_each = !empty($bundle['settings']['chain']['each'][$count]) ? $bundle['settings']['chain']['each'][$count] : 0;
            if ($discount_value > 0 && ($discount_currency == '%' || $discount_each)) {
                foreach ($items as &$item) {
                    if (!$item['not_in_stock']) {
                        $product_discount = $discount_currency == '%' ? $discount_value * $item['clear_price'] / 100 : $discount_value;
                        $product_discount = shopProductsetsPluginHelper::round($product_discount, $round);
                        $item = $this->changePrices($item, $product_discount, $ruble_sign);
                    }
                }
                unset($item);
            } elseif ($discount_value > 0 && !$discount_each) {
                $bundle['common_discount'] += $discount_value;
            }
        }
    }

    /**
     * Get chain discount value by chain key
     *
     * @param array $bundle
     * @param int $chain_key
     * @return float
     */
    private function getChainDiscountValue($bundle, $chain_key)
    {
        return !empty($bundle['settings']['chain']['frontend_value'][$chain_key]) ? $bundle['settings']['chain']['frontend_value'][$chain_key] : 0;
    }

    /**
     * Get chain currency value by chain key
     *
     * @param array $bundle
     * @param int $chain_key
     * @return float
     */
    private function getChainCurrencyValue($bundle, $chain_key)
    {
        return !empty($bundle['settings']['chain']['currency'][$chain_key]) ? $bundle['settings']['chain']['currency'][$chain_key] : 0;
    }

    /**
     * Calculate available-every type of discount.
     *
     * @param array $bundle
     * @param array $items
     * @param string $ruble_sign
     * @param string $round
     */
    private function getAvailEveryDiscount(&$bundle, &$items, $ruble_sign = 'rub', $round = 'not')
    {
        if ($items) {
            foreach ($items as $c => &$item) {
                // Получаем значение скидки: процент или валюта
                $discount_value = $this->getChainDiscountValue($bundle, $c);
                $discount_currency = $this->getChainCurrencyValue($bundle, $c);

                if ($discount_value > 0) {
                    $product_discount = $discount_currency == '%' ? $discount_value * $item['clear_price'] / 100 : $discount_value;
                    $product_discount = shopProductsetsPluginHelper::round($product_discount, $round);
                    $item = $this->changePrices($item, $product_discount, $ruble_sign);
                }
            }
        }
    }

    /**
     * @param array $item
     * @param array $available_skus
     * @param array $products
     * @param array $parent_item - alternative products have parent item with all settings
     * @param array $bundle_settings
     * @param string $ruble_sign
     * @return array
     */
    private function getAvailableSku($item, $available_skus, $products, $parent_item = [], $bundle_settings = [], $ruble_sign = 'rub')
    {
        $new_item = $item;
        // Если необходимо произвести замену артикула на активный
        if (!empty($item['force_change_sku'])) {
            // Если необходимо заменить на альтернативный товар
            if ($item['force_change_sku'] !== 999999999) {
                $item_copy = $item;
                $new_item = $item['alternative'][$item['force_change_sku']];
                // Перезаписываем активный товар
                // Добавляем к нему информацию об альтернативных товарах
                $new_item['alternative'] = $item_copy['alternative'];
                $new_item['settings'] = ifempty($new_item, 'settings', []);
                if (!empty($item_copy['settings'])) {
                    $new_item['settings'] += $item_copy['settings'];
                }
                unset($item_copy['alternative']);
                // Перезаписываем альтернативный товар
                $new_item['alternative'][$item['force_change_sku']] = $item_copy;
                unset($new_item['alternative'][$item['force_change_sku']]['force_change_sku']);
            } elseif (isset($available_skus[$item['product_id']])) {
                $item['settings'] = ifempty($item, 'settings', []) + ifempty($parent_item, 'settings', []);
                // Получаем первый попавшийся доступный товар
                $avail_sku = $this->getFirstAvailableSku($available_skus[$item['product_id']], ifempty($item, 'settings', []));
                if ($avail_sku) {
                    $avail_sku['product_id'] = $item['product_id'];
                    $avail_sku['type'] = 'sku';
                    $avail_sku['settings'] = ifempty($avail_sku, 'settings', []);
                    if (!empty($item['settings'])) {
                        $avail_sku['settings'] += $item['settings'];
                    }
                    // Перезаписываем активный товар
                    $new_item = $this->normalizeItem($avail_sku, $products, $bundle_settings, $ruble_sign);
                }
            }
        }
        return $new_item;
    }

    private function replace_key($array, $old_key, $new_key)
    {
        $keys = array_keys($array);
        if (false === $index = array_search($old_key, $keys)) {
            return $array;
        }
        $keys[$index] = $new_key;
        return array_combine($keys, array_values($array));
    }

    /**
     * Replace unavailable skus
     *
     * @param array $set
     * @param array $available_skus
     * @param array $products
     * @param $ruble_sign
     * @return array
     */
    private function replaceSkus($set, $available_skus, $products, $ruble_sign)
    {
        if (!empty($set['bundle'])) {
            foreach ($set['bundle'] as $bundle_k => $bundle) {
                if (!empty($bundle['items'])) {
                    foreach ($bundle['items'] as $bundle_items_k => $item) {
                        $current_item = $this->getAvailableSku($item, $available_skus, $products, [], $bundle['settings'], $ruble_sign);

                        if ($current_item['_id'] !== $item['_id']) {
                            $new_key = 'i' . $current_item['_id'];
                            $set['bundle'][$bundle_k]['items'] = $this->replace_key($set['bundle'][$bundle_k]['items'], $bundle_items_k, $new_key);
                            $set['bundle'][$bundle_k]['items'][$new_key] = $current_item;

                            if (!empty($item['alternative'])) {
                                $set['bundle'][$bundle_k]['items'][$new_key]['alternative'] = $this->replace_key($item['alternative'], $new_key, $bundle_items_k);
//                                $set['bundle'][$bundle_k]['items'][$new_key]['alternative'][$bundle_items_k] = $item;
                                unset($item['alternative'][$new_key]);
                                /* Скрываем альтернативные товары, которых нет в наличии */
                                unset($set['bundle'][$bundle_k]['items'][$new_key]['alternative'][$bundle_items_k]);
                            }
                            $bundle_items_k = $new_key;
                        } else {
                            $set['bundle'][$bundle_k]['items'][$bundle_items_k] = $current_item;
                        }
                        if (!empty($item['alternative'])) {
                            foreach ($item['alternative'] as $k => $it) {
                                $set['bundle'][$bundle_k]['items'][$bundle_items_k]['alternative'][$k] = $this->getAvailableSku($it, $available_skus, $products, $item, $bundle['settings'], $ruble_sign);
                            }
                        }

                        // Если необходимо скрывать набор в случае отсутствия остатков у любого из товаров.
                        if (!$this->isProductAvailable($current_item)) {
                            if (!empty($bundle['settings']['hide_on_empty_stocks'])) {
                                // Прекращаем обработку комплекта и скрываем его
                                unset($set['bundle'][$bundle_k]);
                                continue;
                            } elseif (!empty($bundle['settings']['hide_empty_stocks']) && !empty($current_item['settings']['delete_product'])) {
                                unset($set['bundle']['items'][$bundle_items_k]);
                            }
                        }
                    }
                }

                if (!empty($bundle['active']) && $this->active_product) {
                    $set['bundle'][$bundle_k]['active'] = $this->getAvailableSku($bundle['active'], $available_skus, $products, [], $bundle['settings'], $ruble_sign);
                    // Если необходимо скрывать набор в случае отсутствия остатков у любого из товаров.
                    if (!empty($bundle['settings']['hide_on_empty_stocks']) && !$this->isProductAvailable($set['bundle'][$bundle_k]['active'])) {
                        // Прекращаем обработку комплекта и скрываем его
                        unset($set['bundle'][$bundle_k]);
                        continue;
                    }
                }
            }
        }
        if (!empty($set['user_bundle']['groups'])) {
            foreach ($set['user_bundle']['groups'] as $group_k => $group) {
                if (!empty($group['items'])) {
                    foreach ($group['items'] as $group_item_k => $item) {
                        $settings = $set['user_bundle']['settings']['discount_type'] == 'each' ? $group['settings'] : $set['user_bundle']['settings'];
                        $set['user_bundle']['groups'][$group_k]['items'][$group_item_k] = $this->getAvailableSku($item, $available_skus, $products, [], $settings, $ruble_sign);
                    }
                }
            }
        }
        if (!empty($set['user_bundle']['required'])) {
            foreach ($set['user_bundle']['required'] as $required_k => $item) {
                $set['user_bundle']['required'][$required_k] = $this->getAvailableSku($item, $available_skus, $products, [], $set['user_bundle']['settings'], $ruble_sign);
            }
        }
        if (!empty($set['user_bundle']['active']) && $this->active_product) {
            $set['user_bundle']['active'] = $this->getAvailableSku($set['user_bundle']['active'], $available_skus, $products, [], $set['user_bundle']['settings'], $ruble_sign);
        }

        // Устанавливаем флаг, что комплект был обработан данной функцией
        $set['skus_replaced'] = 1;

        return $set;
    }

    /**
     * Get info about products
     *
     * @param array[int] $product_skus - sku IDs of products
     * @return array
     */
    private function getProducts($product_skus)
    {
        $pm = new shopProductModel();
        $psm = new shopProductSkusModel();
        $pim = new shopProductImagesModel();
        $sql = "SELECT p.*, 
                       p.count as product_count,
                       ps.id, 
                       ps.available,
                       ps.count as `count`, 
                       ps.price as price, 
                       ps.compare_price as compare_price, 
                       ps.purchase_price as purchase_price, 
                       ps.compare_price as original_compare_price, 
                       ps.primary_price, 
                       ps.sku, 
                       ps.name as sku_name, 
                       ps.product_id, 
                       pi.ext as image_ext, 
                       pi.filename as sku_image_filename,
                       ps.image_id as sku_image_id
                FROM {$psm->getTableName()} ps
                LEFT JOIN {$pm->getTableName()} p ON p.id = ps.product_id
                LEFT JOIN {$pim->getTableName()} pi ON (ps.image_id IS NOT NULL AND ps.image_id = pi.id) OR (ps.image_id IS NULL AND ps.product_id = pi.product_id)
                WHERE ps.id ";
        if (is_array($product_skus)) {
            $sql .= "IN ('" . implode("','", $psm->escape($product_skus, 'int')) . "')";
        } else {
            $sql .= "= '" . (int) $product_skus . "'";
        }
        $sql .= " GROUP BY ps.id";
        $skus = $pm->query($sql)->fetchAll('id');
        if ($skus && self::$is_frontend) {

            $settings = (new shopProductsetsPluginHelper())->getSettings();

            if (!empty($settings['use_frontend_products'])) {

                $event_params = array("skus" => &$skus);
                self::$wa->event('frontend_products', $event_params);

                // Если после вызова хука были изменены зачеркнутые цены, возвращаем их обратно.
                /*foreach ($skus as &$sku) {
                    if ($sku['compare_price'] < $sku['original_compare_price']) {
                        $sku['compare_price'] = $sku['original_compare_price'];
                    }
                    unset($sku);
                }*/
            }

            if (method_exists(new shopProductsCollection(), 'promoProductPrices')) {
                self::promoProductPrices()->workupPromoSkus($skus, []);
            }

            // TODO Возможно многократное округление другими плагинами
            shopRounding::roundSkus($skus);

            $cat_ids = [];
            foreach ($skus as &$p) {
                if (!empty($p['category_id'])) {
                    $cat_ids[] = $p['category_id'];
                }
                if (!isset($p['primary_sku_price'])) {
                    $p['primary_sku_price'] = shopProductsetsPluginHelper::shop_currency($p['price'], $p['currency'], self::$wa->getConfig()->getCurrency(true), false);
                }
            }
            $cat_ids = array_unique($cat_ids);
            if ($cat_ids) {
                $cm = new shopCategoryModel();
                $categories = $cm->getById($cat_ids);
                foreach ($skus as &$p) {
                    if (!empty($p['category_id'])) {
                        $p['category_url'] = $categories[$p['category_id']]['full_url'];
                    }
                }
            }
            foreach ($skus as &$p) {
                $route_params = array('product_url' => $p['url']);
                if (isset($p['category_url'])) {
                    $route_params['category_url'] = $p['category_url'];
                }
                $p['frontend_url'] = self::$wa->getRouteUrl('shop/frontend/product', $route_params);
            }
        }
        return $skus;
    }

    /**
     * The same method as in shopProductsCollection
     * @return shopPromoProductPrices
     * @throws waException
     */
    private static function promoProductPrices()
    {
        static $promo_product_prices;

        $routing_url = wa()->getRouting()->getRootUrl();
        $storefront = wa()->getConfig()->getDomain() . ($routing_url ? '/' . $routing_url : '');

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

    /**
     * Get product information
     *
     * @param int|array|shopProduct $p
     * @return array
     */
    public function getProduct($p)
    {
        if (is_int($p)) {
            $product = (new shopProduct($p))->getData();
        } else {
            $product = ($p instanceof shopProduct) ? $p->getData() : $p;
        }
        return $product;
    }

    public function setActiveProduct($product)
    {
        $this->active_product = $product;
    }

    public function isProductEqualsToActiveProduct($product, $active = [])
    {
        $active_product = $active ? $active : $this->active_product;
        return self::$is_frontend && $active_product && $product['sku_id'] == $active_product['sku_id'];
    }

    private function isProductAvailable($product)
    {
        $is_backend = !self::$is_frontend;
        return ($is_backend || self::$ignore_stock_count) ? true : ((!$product['product']['available'] || ($product['product']['count'] <= 0 && $product['product']['count'] !== null) || ($product['product']['count'] !== null && empty($product['settings']['choose_quantity']) && !empty($product['settings']['quantity']) && $product['product']['count'] < $product['settings']['quantity'])) ? false : true);
    }

    private function isProductHasAvailableSkus($product)
    {
        return !empty($product['settings']['choose_skus']) && ($product['product']['product_count'] === null || $product['product']['product_count'] > 0);
    }

    private function getFirstAvailableSku($skus, $settings = [])
    {
        if (empty($settings['choose_quantity']) && !empty($settings['quantity'])) {
            foreach ($skus as $sku) {
                if ($sku['count'] === null || $sku['count'] >= (int) $settings['quantity']) {
                    return $sku;
                }
            }
        } else {
            return reset($skus);
        }
        return null;
    }

    /**
     * Prepare product item for template
     *
     * @param array $item
     * @param array $products
     * @param array $settings
     * @param string $ruble_sign
     * @param string $round
     * @return array
     */
    public function normalizeItem($item, $products, $settings = [], $ruble_sign = 'rub', $round = '')
    {
        static $app_static_url;

        if ($app_static_url === null) {
            $app_static_url = self::$wa->getAppStaticUrl('shop', true);
        }

        $is_backend = !self::$is_frontend;
        if (!isset($item['sku_id'])) {
            $item['sku_id'] = $item['id'];
        }

        $primary_price = isset($products[$item['sku_id']]['primary_sku_price']) ? $products[$item['sku_id']]['primary_sku_price'] : $products[$item['sku_id']]['primary_price'];
        $compare_price = (float) $products[$item['sku_id']]['compare_price'];
        $purchase_price = (float) $products[$item['sku_id']]['purchase_price'];

        $data = array(
            'id' => $products[$item['sku_id']]['product_id'],
            '_id' => $item['id'],
            'skuId' => $item['sku_id'],
            'name' => waString::escapeAll($products[$item['sku_id']]['name']),
            'skuName' => ($products[$item['sku_id']]['sku_name'] ? waString::escapeAll($products[$item['sku_id']]['sku_name']) : ($products[$item['sku_id']]['sku'] ? waString::escapeAll($products[$item['sku_id']]['sku']) : (!$products[$item['sku_id']]['sku_name'] && !$products[$item['sku_id']]['sku'] ? _wp('sku ID') . ': #' . $item['sku_id'] : ''))),
            'price' => $ruble_sign == 'html' ? shopProductsetsPluginHelper::shop_currency_html($primary_price) : shopProductsetsPluginHelper::shop_currency($primary_price),
            'clear_price' => (float) shopProductsetsPluginHelper::shop_currency($primary_price, null, null, false),
            'compare_price' => 0,
            'purchase_price' => $purchase_price > 0 ? shopProductsetsPluginHelper::shop_currency($purchase_price, $products[$item['sku_id']]['currency'], null, false) : 0,
            'original_compare_price' => 0,
            'original_compare_price_cur' => 0,
            'clear_compare_price' => 0
        );
        $data['original_price'] = $data['price'];
        $data['clear_original_price'] = $data['clear_price'];

        if ($is_backend) {
            $data['stocks'] = shopHelper::getStockCountIcon($products[$item['sku_id']]['count']);
            $data['type'] = $item['type'];
        } else {
            $data['skuName'] = ($products[$item['sku_id']]['sku_name'] ? waString::escapeAll($products[$item['sku_id']]['sku_name']) : '');
        }

        // Изображение
        $image = $app_static_url . 'plugins/productsets/img/image-fishki.png';

        if (!$is_backend) {
            $image_width = !empty($this->settings['frontend_image_size']['width']) ? (int) $this->settings['frontend_image_size']['width'] : 0;
            $image_height = !empty($this->settings['frontend_image_size']['height']) ? (int) $this->settings['frontend_image_size']['height'] : (isset($this->settings['frontend_image_size']['height']) && $this->settings['frontend_image_size']['height'] == '' ? '' : 0);
            $image_size = $image_width . ($image_height !== '' ? 'x' . $image_height : '');
            if ($image_size == '0x0') {
                $image_size = self::FRONTEND_IMAGE_SIZE;
            }
        }
        if (isset($products[$item['sku_id']]['image_id'])) {
            $image = shopImage::getUrl(array(
                'id' => $products[$item['sku_id']]['image_id'],
                'product_id' => $data['id'],
                'ext' => $products[$item['sku_id']]['ext'],
                'filename' => $products[$item['sku_id']]['image_filename'],
            ), $is_backend ? self::IMAGE_SIZE : $image_size);
        }
        if ($item['type'] == 'sku' && $products[$item['sku_id']]['sku_image_id']) {
            $image = shopImage::getUrl(array(
                'id' => $products[$item['sku_id']]['sku_image_id'],
                'product_id' => $data['id'],
                'ext' => $products[$item['sku_id']]['image_ext'],
                'filename' => $products[$item['sku_id']]['sku_image_filename'],
            ), $is_backend ? self::IMAGE_SIZE : $image_size);
        }
        $data['image'] = $image;

        if ($compare_price < $data['clear_price']) {
            $compare_price = 0;
        }
        if ($compare_price > 0) {
            $data['compare_price'] = $ruble_sign == 'html' ? shopProductsetsPluginHelper::shop_currency_html($compare_price, $products[$item['sku_id']]['currency']) : shopProductsetsPluginHelper::shop_currency($compare_price, $products[$item['sku_id']]['currency']);
            $data['original_compare_price'] = $data['clear_compare_price'] = (float) shopProductsetsPluginHelper::shop_currency($compare_price, $products[$item['sku_id']]['currency'], null, false);
            $data['original_compare_price_cur'] = $ruble_sign == 'html' ? shopProductsetsPluginHelper::shop_currency_html($data['original_compare_price'], $products[$item['sku_id']]['currency']) : shopProductsetsPluginHelper::shop_currency($data['original_compare_price'], $products[$item['sku_id']]['currency']);
        }

        if ($is_backend) {
            $data['obj'] = json_encode($data);
        } else {
            $data['product'] = $products[$item['sku_id']];
            $data['not_in_stock'] = !self::$ignore_stock_count && $data['product']['count'] !== null && $data['product']['count'] <= 0;
        }
        $data += $item;

        // Меняем цены, если есть скидка
        if (!$is_backend) {
            $product_discount = 0;
            $round = $round ? $round : ifempty($this->active_set, 'settings', 'other', 'round', 'not');
            if (isset($data['settings']['discount']) && $settings['discount_type'] !== 'common') {
                $data['settings']['discount'] = shopProductsetsPluginHelper::round($data['settings']['discount'], $round);
                $data['settings']['frontend_discount'] = ($data['settings']['discount'] && $data['settings']['currency'] !== '%') ? shopProductsetsPluginHelper::shop_currency($data['settings']['discount'], $data['settings']['currency'], null, false) : $data['settings']['discount'];
            }
            // Общая скидка в процентах
            if ($settings['discount_type'] == 'common' && $settings['discount'] && $settings['currency'] === '%') {
                $product_discount = $settings['frontend_discount'] * $data['clear_price'] / 100;
            } // Индивидуальная скидка
            elseif ($settings['discount_type'] == 'each' && !empty($data['settings']['frontend_discount'])) {
                $product_discount = $data['settings']['currency'] == '%' ? $data['settings']['frontend_discount'] * $data['clear_price'] / 100 : $data['settings']['frontend_discount'];
            }
            $product_discount = shopProductsetsPluginHelper::round($product_discount, $round);
            $data = $this->changePrices($data, $product_discount, $ruble_sign);
        }

        return $data;
    }

    /**
     * Collect all sku IDs from the set
     *
     * @param array $set
     * @param bool $with_product_ids
     * @return array
     */
    public function collectProductSkuIds($set, $with_product_ids = false)
    {
        // Получаем информацию о товарах в случае их наличия
        $product_skus = [];
        if (!empty($set['bundle'])) {
            foreach ($set['bundle'] as $bundle) {
                if (!empty($bundle['items'])) {
                    foreach ($bundle['items'] as $item) {
                        $product_skus[$item['sku_id']] = $item['product_id'];
                        if (!empty($item['alternative'])) {
                            foreach ($item['alternative'] as $it) {
                                $product_skus[$it['sku_id']] = $it['product_id'];
                            }
                        }
                    }
                }
            }
        }
        if (!empty($set['user_bundle']['groups'])) {
            foreach ($set['user_bundle']['groups'] as $group) {
                if (!empty($group['items'])) {
                    foreach ($group['items'] as $item) {
                        $product_skus[$item['sku_id']] = $item['product_id'];
                    }
                }
            }
        }
        if (!empty($set['user_bundle']['required'])) {
            foreach ($set['user_bundle']['required'] as $item) {
                $product_skus[$item['sku_id']] = $item['product_id'];
            }
        }
        if (!empty($set['settings']['product']['products'])) {
            foreach ($set['settings']['product']['products'] as $dirty_sku_id => $p_id) {
                $product_skus[substr($dirty_sku_id, 1)] = $p_id;
            }
        }
        return $with_product_ids ? $product_skus : array_keys($product_skus);
    }

    /**
     * Collect bundle sku IDs
     *
     * @param array $bundle
     * @return array
     */
    public function getBundleSkuIds($bundle)
    {
        $sku_ids = [];
        if (!empty($bundle['items'])) {
            foreach ($bundle['items'] as $item) {
                $sku_ids[$item['sku_id']] = $item['sku_id'];
                if (!empty($item['alternative'])) {
                    foreach ($item['alternative'] as $it) {
                        $sku_ids[$it['sku_id']] = $it['sku_id'];
                    }
                }
            }
        }
        return $sku_ids;
    }

    /**
     * Get products from group types
     *
     * @param array $types
     * @return array
     */
    public function getGroupTypesProducts($types)
    {
        static $data = [];

        $items = [];
        foreach ($types as $type) {
            switch ($type['type']) {
                case 'categories':
                    $type_name = 'category';
                    break;
                case 'sets':
                    $type_name = 'set';
                    break;
                case 'types':
                    $type_name = 'type';
                    break;
            }
            if (!empty($type_name) && !empty($type['settings'][$type['type']])) {
                $hash = "{$type_name}/" . $type['settings'][$type['type']];
                if (!isset($data[$hash])) {
                    $collection = new shopProductsCollection($hash, ['no_plugins_frontend_products' => 1]);
                    $count = $collection->count();
                    $count = (empty($type['settings']['field']) && $count > 30) ? 30 : $count;
                    $collection->orderBy('id', 'ASC');
                    $products = $collection->getProducts('id, currency, sku_id', 0, (!empty($type['settings']['field']) && !empty($type['settings']['value'])) ? $type['settings']['value'] : $count);
                    if ($products) {
                        foreach ($products as $product) {
                            $uid = 't' . $product['sku_id'];
                            if (!isset($items[$uid])) {
                                $items[$uid] = [
                                    'id' => $uid,
                                    'group_id' => $type['group_id'],
                                    'product_id' => $product['id'],
                                    'sku_id' => $product['sku_id'],
                                    'type' => 'product',
                                    'sort_id' => 0,
                                    'settings' => [
                                        '_id' => $uid,
                                        'quantity' => 1,
                                        'discount' => !empty($type['settings']['discount']) ? $type['settings']['discount'] : 0,
                                        'currency' => !empty($type['settings']['currency']) ? $type['settings']['currency'] : 0,
                                    ]
                                ];
                            }
                        }
                    }
                    $data[$hash] = $items;
                } else {
                    $items = $data[$hash];
                }
            }
        }
        return $items;
    }

    /**
     * Remove duplicated products for user bundles
     *
     * @param array $set
     */
    public function removeDuplicates(&$set)
    {
        // Удаляем повторяющиеся товары
        if (!empty($set['user_bundle'])) {
            $touched = [];

            if (!empty($set['user_bundle']['active'])) {
                $touched[$set['user_bundle']['active']['sku_id']] = $set['user_bundle']['active']['sku_id'];
            }
            if (!empty($set['user_bundle']['required'])) {
                foreach ($set['user_bundle']['required'] as $k => $item) {
                    if (isset($touched[$item['sku_id']])) {
                        unset($set['user_bundle']['required'][$k]);
                        $set['user_bundle']['items_quantity']--;
                    } else {
                        $touched[$item['sku_id']] = $item['sku_id'];
                    }
                }
            }
            if (!empty($set['user_bundle']['groups'])) {
                foreach ($set['user_bundle']['groups'] as $group_key => $group) {
                    if (!empty($group['items'])) {
                        foreach ($group['items'] as $k => $item) {
                            if (isset($touched[$item['sku_id']])) {
                                unset($set['user_bundle']['groups'][$group_key]['items'][$k]);
                                if (!empty($group['settings']['multiple'])) {
                                    $set['user_bundle']['items_quantity']--;
                                }
                            } else {
                                $touched[$item['sku_id']] = $item['sku_id'];
                            }
                        }
                    }
                }
            }
        }
    }

}
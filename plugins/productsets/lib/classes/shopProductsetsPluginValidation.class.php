<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopProductsetsPluginValidation extends shopProductsetsPluginHelper
{

    /**
     * Check set availability for product
     *
     * @param array $set
     * @param array|object $product
     * @return bool
     */
    public function isSetAvailableForProduct($set, $product)
    {
        $result = true;
        // Если имеется товар, для которого необходимо проверить доступность комплектов, выполняем проверки
        if ($product && !empty($set['settings']['product'])) {
            $product_page_validation = $set['settings']['product'];
            $result = false;

            // Проверка для конкретных товаров
            if (!empty($product_page_validation['products'])) {
                if (!in_array($product['id'], $product_page_validation['products'])) {
                    $result = false;
                } else {
                    return true;
                }
            }

            // Проверка для типов товаров
            if (!empty($product_page_validation['types'])) {
                if (!in_array($product['type_id'], $product_page_validation['types'])) {
                    $result = false;
                } else {
                    return true;
                }
            }

            // Проверка для списков
            if (!empty($product_page_validation['sets'])) {
                if (!$this->productSetValidation($product['id'], $product_page_validation['sets'])) {
                    $result = false;
                } else {
                    return true;
                }
            }

            // Проверка для категорий
            if (!empty($product_page_validation['categories'])) {
                if (!$this->productCategoryValidation($product['id'], $product_page_validation['categories'])) {
                    $result = false;
                } else {
                    return true;
                }
            }

            // Проверка для категорий и подкатегорий
            if (!empty($product_page_validation['categories_sub'])) {
                if (!$this->productCategoryValidation($product['id'], $product_page_validation['categories_sub'], true)) {
                    $result = false;
                } else {
                    return true;
                }
            }
        }
        return $result;
    }

    /**
     * Check set availability for category
     *
     * @param array $set
     * @param array $category
     * @return bool
     */
    public function isSetAvailableForCategory($set, $category)
    {
        $result = true;
        if ($category) {
            $result = false;
            if (!empty($set['settings']['category'])) {
                $category_id = is_array($category) ? $category['id'] : (int) $category;
                // Категории
                if (!empty($set['settings']['category']['categories']) && in_array($category_id, $set['settings']['category']['categories'])) {
                    $result = true;
                }
                // Категории и подкатегории
                if (!$result && !empty($set['settings']['category']['categories_sub'])) {
                    $childs_ids = (new shopProductsetsData())->getCategoryData()->getChildIds($set['settings']['category']['categories_sub']);
                    $result = !in_array($category_id, $childs_ids) ? false : true;
                }
            }
        }
        return $result;
    }

    /**
     * Check set availability for demand
     *
     * @param array $set
     * @param string $demand
     * @return bool
     */
    public function isSetAvailableForDemand($set, $demand)
    {
        if ($demand) {
            if (!empty($set['settings']['ondemand']) && $set['settings']['ondemand'] == $demand) {
                return true;
            }
            return false;
        }
        return true;
    }

    /**
     *
     *
     * @param int $set_id
     * @param array $storefront_limitations
     * @return bool
     */
    public function isSetAvailableForStorefront($set_id, $storefront_limitations)
    {
        $active_storefront = (new shopProductsetsPluginHelper())->getActiveStorefront();

        // Проверка ограничения по витринам
        if (isset($storefront_limitations[$set_id]) &&
            (($storefront_limitations[$set_id]['operator'] === 'eq' && !in_array($active_storefront, $storefront_limitations[$set_id]['storefront']))
                || ($storefront_limitations[$set_id]['operator'] === 'neq' && in_array($active_storefront, $storefront_limitations[$set_id]['storefront'])))
        ) {
            return false;
        }
        return true;
    }

    /**
     * Check if product belongs to sets
     *
     * @param int $product_id
     * @param array[string] $set_ids
     * @return bool
     */
    private function productSetValidation($product_id, $set_ids)
    {
        static $results;

        $result = false;
        foreach ($set_ids as $set_id) {
            // Создаем уникальный хеш операции
            $hash = $this->getRequestHash($set_id, $product_id);
            if (isset($results[$hash])) {
                $result = $results[$hash];
                if ($result === true) {
                    break;
                } else {
                    continue;
                }
            }

            $results[$hash] = $result;

            $collection = new shopProductsCollection('set/' . $set_id, ['no_plugins_frontend_products' => 1]);
            $collection->addWhere("p.id =  '" . (int) $product_id . "'");
            if ($collection->count()) {
                $result = true;
                $results[$hash] = $result;
                break;
            }
        }

        return $result;
    }

    /**
     * Check if product belongs to categories
     *
     * @param int $product_id
     * @param array[int] $category_ids
     * @param bool $include_subcat
     * @return bool
     */
    private function productCategoryValidation($product_id, $category_ids, $include_subcat = false)
    {
        static $results = array();

        $result = false;
        $category_model = self::getModel('category');
        foreach ($category_ids as $category_id) {
            // Создаем уникальный хеш операции
            $hash = $this->getRequestHash($category_id, $product_id, $include_subcat);
            if (isset($results[$hash])) {
                $result = $results[$hash];
                if ($result === true) {
                    break;
                } else {
                    continue;
                }
            }
            $results[$hash] = $result;

            $category_ids = array();
            $category = $category_model->getById($category_id);
            // Если категория статическая
            if ($category['type'] == shopCategoryModel::TYPE_STATIC) {
                $category_ids[] = $category_id;
                if ($include_subcat) {
                    $descendants = $category_model->descendants($category)->where('type = ' . shopCategoryModel::TYPE_STATIC)->fetchAll('id');
                    if ($descendants) {
                        $category_ids = array_merge($category_ids, array_keys($descendants));
                    }
                }
                $collection = new shopProductsCollection('id/' . $product_id, ['no_plugins_frontend_products' => 1]);
                $alias = $collection->addJoin('shop_category_products');
                $collection->addWhere($alias . ".category_id IN(" . implode(',', $category_ids) . ")");
            } else {
                // Получаем все товары динамической категории
                $collection = new shopProductsCollection('category/' . $category_id, ['no_plugins_frontend_products' => 1]);
                $collection->addWhere("p.id =  '" . (int) $product_id . "'");
            }
            if ($collection->count()) {
                $result = true;
                $results[$hash] = $result;
                break;
            }
        }

        return $result;
    }

    /**
     * @param array $bundle
     * @param string $type
     * @param int $set_id
     * @return bool
     */
    public function isBundleAvailable($bundle, $type = 'bundle', $set_id = 0)
    {
        // Проверка времени жизни набора
        if (!empty($bundle['settings']['lifetime']) && !empty($bundle['settings']['schedule'])) {
            $schedule = $bundle['settings']['schedule'];
            $start = $schedule['start'];
            $end = $schedule['end'];
            $start_timestamp = strtotime((int) $start['year'] . '-' . (int) $start['month'] . '-' . (int) $start['day'] . " " . (int) $start['hour'] . ":" . (int) $start['minute']);
            $end_timestamp = strtotime((int) $end['year'] . '-' . (int) $end['month'] . '-' . (int) $end['day'] . " " . (int) $end['hour'] . ":" . (int) $end['minute']);

            // Если конечный срок публикации меньше начального, то удаляем конечный срок
            if ($end_timestamp <= $start_timestamp) {
                $end_timestamp = 0;
            }

            $today = time();
            if (($today < $start_timestamp) || ($end_timestamp && $today > $end_timestamp)) {
                // Удаляем неактивный набор, если есть соответствующая настройка
                if (!empty($bundle['settings']['delete_inactive'])) {
                    if ($type == 'bundle') {
                        (new shopProductsetsBundlePluginModel())->deleteByBundleId($bundle['id']);
                    } else {
                        (new shopProductsetsUserbundlePluginModel())->deleteBySetId($set_id);
                    }
                }
                return false;
            }
        }
        return true;
    }

    /**
     * Check, if item is required in the bundle
     *
     * @param array $item_settings
     * @return bool
     */
    public function isItemRequired($item_settings)
    {
        if (empty($item_settings['delete_product']) || !empty($item_settings['discount_required'])) {
            return true;
        }
        return false;
    }

    /**
     * Check quantity of cart item
     *
     * @param array $cart_items_quantity
     * @param array $item
     * @return float
     */
    public function checkItemQuantity(&$cart_items_quantity, $item)
    {
        $quantity_to_decrease = 0;
        // Проверяем, хватает ли кол-ва
        if (isset($cart_items_quantity[$item['sku_id']])) {
            // Количество добавленного товара при создании набора
            $first_added_quantity = $item['quantity'];
            // Количество товара в корзине
            $quantity = $cart_items_quantity[$item['sku_id']]['quantity'];
            // Минимальное количество товара
            $min_quantity = !empty($item['settings']['quantity']) ? $item['settings']['quantity'] : 1;

            if ($min_quantity <= $quantity && $first_added_quantity <= $quantity) {
                // Уменьшаем количество доступного товара
                if (empty($item['settings']['choose_quantity']) || $min_quantity > $first_added_quantity) {
                    $quantity_to_decrease = $min_quantity;
                } // Если разрешено менять кол-во товара, вычитаем значение, переданное при создании набора
                else {
                    $quantity_to_decrease = $first_added_quantity;
                }
                $cart_items_quantity[$item['sku_id']]['decrease'] += $quantity_to_decrease;
                $cart_items_quantity[$item['sku_id']]['quantity'] -= $quantity_to_decrease;
            }
        }
        return $quantity_to_decrease;
    }

    /**
     * If bundles or userbundles are not active, remove it from the set
     *
     * @param array $set
     */
    public function removeInactiveBundles(&$set)
    {
        if (empty($set['settings']['bundle_status'])) {
            unset($set['bundle']);
        }
        if (empty($set['settings']['user_bundle_status'])) {
            unset($set['user_bundle']);
        }
    }
}
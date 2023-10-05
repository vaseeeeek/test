<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopFlexdiscountData
{

    protected static $types_data = null;
    // Комплект
    private static $bundle = array();
    // Последний ID правила, в котором используйтся комплект
    private static $last_bundle_id = null;
    private static $typesMethods = array(
        'cat' => 'category',
        'cat_all' => 'category',
        'set' => 'set',
        'type' => 'type',
        'product' => 'product',
        'feature' => 'feature',
        'services' => 'services',
        'product_services' => 'services',
        'num_prod' => 'product',
        'num_cat' => array('category', 'product'),
        'num_cat_all' => array('category', 'product'),
        'num_set' => array('set', 'product'),
        'num_type' => array('type', 'product'),
        'num_all_cat' => 'category',
        'num_all_cat_all' => 'category',
        'num_all_set' => 'set',
        'num_all_type' => 'type',
        'num_feat' => 'feature',
        'sum_cat' => 'category',
        'sum_cat_all' => 'category',
        'sum_feat' => 'feature',
        'total_feat' => 'feature',
        'ucat' => 'ucat',
        'user' => 'user',
        'shipping' => 'shipping',
        'payment' => 'payment',
        'order_prod' => 'product',
        'order_prod_int' => 'product',
        'order_prod_cat' => array('category', 'product'),
        'order_prod_cat_all' => array('category', 'product'),
        'order_prod_cat_int' => array('category', 'product'),
        'order_prod_cat_all_int' => array('category', 'product'),
        'storefront' => 'storefront',
        'time' => 'time',
        'product_tags' => 'tags',
        'count_orders' => 'orderStatus',
        'order_count_int' => 'orderStatus',
        'product_stock' => 'stocks',
        'user_data' => 'userData2',
        'customer_source' => 'customerSource',
        'user_country' => 'country',
        'params' => 'params',
    );
    // Массив моделей
    private static $models = array();

    private static function getInstance()
    {
        static $instance = null;
        if ($instance === null) {
            $instance = new self();
        }
        return $instance;
    }

    /**
     * Get product tags
     *
     * @param array $product_ids
     * @return array
     */
    protected static function getProductTags($product_ids)
    {
        static $tags = array();
        $return_tags = $find_tags = array();
        foreach ($product_ids as $p_id) {
            if (isset($tags[$p_id])) {
                $return_tags[$p_id] = $tags[$p_id];
            } else {
                $find_tags[$p_id] = $p_id;
            }
        }
        if ($find_tags) {
            $tag_model = new shopProductTagsModel();
            $sql = "SELECT tag_id, product_id FROM " . $tag_model->getTableName() . " WHERE product_id IN (i:id)";
            $result = $tag_model->query($sql, array('id' => $find_tags))->fetchAll('product_id', 2);
            $tags += $result;
            $return_tags += $result;
        }
        return $return_tags;
    }

    /**
     * Filter products belong to category.
     *
     * @param int $category_id
     * @param bool $include_subcat
     * @param array $items
     * @return array
     */
    protected static function getCategoryProducts($category_id, $include_subcat = false, $items = [])
    {
        $app = new shopFlexdiscountApp();

        $products = shopFlexdiscountApp::get('runtime.shop/products', []);
        if ($items) {
            $products = $app->set('runtime.shop/products', shopFlexdiscountApp::getHelper()->prepareShopProducts($items));
        }
        if (!$products) {
            return [];
        }
        $products = array_keys($products);

        // Создаем уникальный хеш операции
        $hash = shopFlexdiscountApp::getFunction()->getRequestHash($category_id, $include_subcat, $products);
        $cached_result = shopFlexdiscountApp::get("runtime.shop/category_products.{$hash}");
        if ($cached_result !== null) {
            return $cached_result;
        }

        $category_ids = [];
        $category_model = self::getModel('category');
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
            $collection = new shopFlexdiscountProductsCollection($products);
            $alias = $collection->addJoin('shop_category_products');
            $collection->addWhere($alias . ".category_id IN(" . implode(',', $category_ids) . ")");
            $products = $collection->getProducts('id, currency', 0, $collection->count());
        } else {
            // Получаем все товары динамической категории
            $collection = new shopFlexdiscountProductsCollection('category/' . $category_id);
            $collection->addWhere('p.id IN (' . implode(',', $products) . ')');
            $products = $collection->getProducts("id, currency", 0, $collection->count());
        }

        return $app->set("runtime.shop/category_products.{$hash}", $products ? array_keys($products) : []);
    }

    /**
     * Get products belong to set
     *
     * @param int $set_id
     * @param array $items
     * @return array
     */
    protected static function getSetProducts($set_id, $items = [])
    {
        $app = new shopFlexdiscountApp();
        $products = shopFlexdiscountApp::get('runtime.shop/products', []);
        if ($items) {
            $products = $app->set('runtime.shop/products', shopFlexdiscountApp::getHelper()->prepareShopProducts($items));
        }

        if (!$products) {
            return [];
        }
        $products = array_keys($products);

        // Создаем уникальный хеш операции
        $hash = shopFlexdiscountApp::getFunction()->getRequestHash($set_id, $products);
        $cached_result = shopFlexdiscountApp::get("runtime.shop/set_products.{$hash}");
        if ($cached_result !== null) {
            return $cached_result;
        }

        $collection = new shopFlexdiscountProductsCollection('set/' . $set_id);
        $collection->addWhere('p.id IN (' . implode(',', $products) . ')');
        $products = $collection->getProducts("id, currency", 0, $collection->count(), true, 1);

        return $app->set("runtime.shop/set_products.{$hash}", $products ? array_keys($products) : []);
    }

    /**
     * If $rule = null - Check, if it is a bundle rule.
     * If isset $rule - Set information about the bundle
     *
     * @param array|null $rule
     * @return boolean
     */
    protected static function isBundle($rule = null)
    {
        static $is_bundle = array();
        // Если не передано параметров, получаем информацию о последнем добавленном наборе
        if ($rule === null && self::$last_bundle_id !== null && isset($is_bundle[self::$last_bundle_id])) {
            return $is_bundle[self::$last_bundle_id];
        } elseif ($rule === null) {
            return false;
        }
        self::$last_bundle_id = $rule['id'];
        $is_bundle[self::$last_bundle_id] = !empty($rule['bundle']);
        return $is_bundle[self::$last_bundle_id];
    }

    /**
     * Get current bundle
     *
     * @return mixed
     */
    protected static function getBundle()
    {
        return self::$bundle[self::$last_bundle_id];
    }

    /**
     * Add items to the bundle
     *
     * @param null|array $items
     * @param null|int $bundle_quantity
     */
    protected static function addToBundle($items = null, $bundle_quantity = null)
    {
        static $quantity = 1;
        if ($items !== null) {
            if (!isset(self::$bundle[self::$last_bundle_id])) {
                self::$bundle[self::$last_bundle_id] = array();
            }
            $bundle_items = array();
            foreach ($items as $i) {
                $bundle_items[$i['sku_id']] = $i;
                $bundle_items[$i['sku_id']]['bundle_quantity'] = $quantity;
            }
            self::$bundle[self::$last_bundle_id][] = $bundle_items;
        }
        $quantity = $bundle_quantity !== null ? $bundle_quantity : $quantity;
    }

    /**
     * Reset bundle items.
     *
     * @param null|int $last
     */
    protected static function resetBundle($last = null)
    {
        if ($last !== null && !empty(self::$bundle[$last])) {
            self::$bundle[$last] = array();
        } elseif ($last === null && isset(self::$bundle[self::$last_bundle_id])) {
            self::$bundle[self::$last_bundle_id] = array();
        } elseif ($last === null && !self::$last_bundle_id) {
            self::$bundle = array();
        }
    }

    /**
     * Get product params by ID
     *
     * @param int $product_id
     * @return string
     */
    public static function getProductParams($product_id)
    {
        $cached_result = shopFlexdiscountApp::get("runtime.shop/product_params.{$product_id}");
        if ($cached_result === null) {
            $products = shopFlexdiscountApp::get('runtime.shop/products', []);
            self::findProductParams(array_keys($products));
        }

        return shopFlexdiscountApp::get("runtime.shop/product_params.{$product_id}", '');
    }

    /**
     * Get product features. Add features array to products
     *
     * @param array $products
     * @return array
     */
    protected static function getFeatures(&$products)
    {
        if (!$products) {
            return array();
        }

        $app = new shopFlexdiscountApp();

        $features = shopFlexdiscountApp::get('runtime.features/all');
        $product_features = shopFlexdiscountApp::get('runtime.features/product_features');
        $processed = shopFlexdiscountApp::get('runtime.features/processed', ['products' => [], 'skus' => []]);

        // Определяем для каких товаров необходимо найти характеристики
        $find_features = false;
        $product_skus = shopFlexdiscountApp::get('runtime.shop/product_sku_ids', []);

        $shop_products = shopFlexdiscountApp::get('runtime.shop/products', []);

        $update_shop_products = [];
        foreach ($products as &$item) {
            // Добавляем товары в набор
            $item['product_id'] = shopFlexdiscountApp::getHelper()->getProductId($item);
            if ($item['product_id'] !== 0 && !isset($shop_products[$item['product_id']])) {
                $update_shop_products[] = $item;
                $find_features = true;
            }
            // Добавляем информцию об артикулах в набор
            if (!empty($item['sku_id']) && $item['product_id'] !== 0 && !isset($product_skus[$item['sku_id']])) {
                $product_skus[$item['sku_id']] = $item['product_id'];
                $find_features = true;
            }
        }

        if ($update_shop_products) {
            $shop_products = $app->set('runtime.shop/products', $app::getHelper()->prepareShopProducts($update_shop_products, $shop_products));
        }

        // Если не был осуществлен поиск характеристик для некоторых товаров, выполним его
        $product_ids = array_keys($shop_products);
        // Необработанные товары
        $not_processed = array();
        foreach ($product_ids as $product_id) {
            if (!isset($processed['products'][$product_id])) {
                $not_processed[$product_id] = $shop_products[$product_id];
            }
        }

        // Необработанные артикулы
        $not_processed_skus = array();
        if ($product_skus) {
            $product_sku_ids = array_keys($product_skus);
            foreach ($product_sku_ids as $product_sku_id) {
                if (!isset($processed['skus'][$product_sku_id])) {
                    $not_processed_skus[$product_sku_id] = $product_skus[$product_sku_id];
                }
            }
        }

        if ($not_processed) {
            $product_ids = array_keys($not_processed);
        }
        if ($not_processed_skus) {
            $product_sku_ids = array_keys($not_processed_skus);
        }

        // Если не было выборки по характеристикам или требуется найти характеристики для товаров
        if ($features === null || $find_features || $not_processed || $not_processed_skus) {

            $processed['products'] += array_combine($product_ids, $product_ids);

            $product_features_model = new shopProductFeaturesModel();
            $rows = $product_features_model->getByField(array(
                'product_id' => $product_ids,
                'sku_id' => null
            ), true);
            // Получаем характеристики артикулов
            if ($product_skus) {
                $processed['skus'] += array_combine($product_sku_ids, $product_sku_ids);
                $sku_rows = $product_features_model->getByField(array(
                    'sku_id' => $product_sku_ids
                ), true);
                $rows = array_merge($rows, $sku_rows);
            }

            /* Ищем товары, у которых в качестве артикулов используются характеристики */
            $selectable_product_ids = $selectable_features = array();
            foreach ($shop_products as &$p) {
                if (!empty($p['sku_type'])) {
                    $selectable_product_ids[] = $p['id'];
                }
            }
            unset($p);
            if ($selectable_product_ids) {
                $sql = 'SELECT pf.*, "1" as prod_select FROM shop_product_features pf
                    JOIN shop_product_features_selectable pfs ON pf.product_id = pfs.product_id AND pf.feature_id = pfs.feature_id
                    WHERE pf.sku_id IS NOT NULL AND pf.product_id IN (i:ids)';
                $rows = array_merge($rows, $product_features_model->query($sql, array('ids' => $selectable_product_ids))->fetchAll());
            }
            if (!$rows) {
                return array();
            }
            $tmp = array();
            foreach ($rows as $row) {
                if (isset($row['prod_select'])) {
                    $selectable_features[$row['product_id']][$row['sku_id']][$row['feature_id']] = $row['feature_value_id'];
                }
                $tmp[$row['feature_id']] = true;
            }
            $feature_model = new shopFeatureModel();
            $sql = 'SELECT * FROM ' . $feature_model->getTableName() . " WHERE id IN (i:ids)";
            if ($features === null) {
                $features = [];
            }
            $features += $feature_model->query($sql, array('ids' => array_keys($tmp)))->fetchAll('id');

            $type_values = $prod_feat = $active_keys = array();
            foreach ($rows as $row) {
                if (empty($features[$row['feature_id']])) {
                    continue;
                }
                $f = $features[$row['feature_id']];
                $type = preg_replace('/\..*$/', '', $f['type']);
                if ($type != shopFeatureModel::TYPE_BOOLEAN && $type != shopFeatureModel::TYPE_DIVIDER) {
                    $type_values[$type][$row['feature_value_id']] = $row['feature_value_id'];
                }

                $key = $row['sku_id'] !== null ? 's' . $row['sku_id'] : 'p' . $row['product_id'];
                // Распределяем товары по артикулам и единицам без артикулов
                if (!isset($product_features[$key])) {
                    if ($row['sku_id'] !== null && isset($product_skus[$row['sku_id']]) && isset($shop_products[$product_skus[$row['sku_id']]])) {
                        $product_features[$key] = $shop_products[$product_skus[$row['sku_id']]];
                        $product_features[$key]['index_key'] = $key;
                        $active_keys[$key] = $key;
                    } elseif ($row['sku_id'] === null) {
                        $product_features[$key] = $shop_products[$row['product_id']];
                        $product_features[$key]['index_key'] = $key;
                        $active_keys[$key] = $key;
                    }
                }

                if ($f['multiple']) {
                    $prod_feat[$key][$f['id']][$row['feature_value_id']] = $row['feature_value_id'];
                } else {
                    $prod_feat[$key][$f['id']] = $row['feature_value_id'];
                }
            }
            foreach ($type_values as $type => $value_ids) {
                $model = shopFeatureModel::getValuesModel($type);
                $type_values[$type] = $model->getValues('id', $value_ids);
            }

            $tmp = array();
            foreach ($product_features as $k => $p) {
                if (!isset($active_keys[$k])) {
                    continue;
                }
                // Если товар не получил ключ при распределении, тогда пропускаем его
                if (!isset($p['index_key'])) {
                    unset($product_features[$k]);
                    continue;
                }
                if (isset($selectable_features[$k])) {
                    $product_features[$k]['selectable_features'] = $selectable_features[$k];
                }
                $tmp[(int) $product_features[$k]['type_id']] = true;
            }

            // get type features for correct sort
            $type_features = array();
            if ($tmp) {
                $type_features_model = new shopTypeFeaturesModel();
                $sql = "SELECT type_id, feature_id FROM " . $type_features_model->getTableName() . "
                WHERE type_id IN (i:type_id) ORDER BY sort";
                $rows = $type_features_model->query($sql, array('type_id' => array_keys($tmp)))->fetchAll();
                foreach ($rows as $row) {
                    $type_features[$row['type_id']][] = $row['feature_id'];
                }
            }

            foreach ($product_features as &$p) {
                if (!isset($active_keys[$p['index_key']])) {
                    continue;
                }
                $p['features'] = $p['feature_values'] = array();
                if (!empty($type_features[$p['type_id']])) {
                    foreach ($type_features[$p['type_id']] as $feature_id) {
                        if (empty($features[$feature_id])) {
                            continue;
                        }
                        $f = $features[$feature_id];
                        $type = preg_replace('/\..*$/', '', $f['type']);
                        if (isset($prod_feat[$p['index_key']][$feature_id])) {
                            $value_ids = $prod_feat[$p['index_key']][$feature_id];
                            if ($type == shopFeatureModel::TYPE_BOOLEAN || $type == shopFeatureModel::TYPE_DIVIDER) {
                                /**
                                 * @var shopFeatureValuesBooleanModel|shopFeatureValuesDividerModel $model
                                 */
                                $model = shopFeatureModel::getValuesModel($type);
                                $values = $model->getValues('id', $value_ids);
                                $p['features'][$f['id']] = reset($values);
                            } else {
                                if (is_array($value_ids)) {
                                    $p['features'][$f['id']] = array();
                                    foreach ($value_ids as $v_id) {
                                        if (isset($type_values[$type][$feature_id][$v_id])) {
                                            $p['features'][$f['id']][$v_id] = $type_values[$type][$feature_id][$v_id];
                                        }
                                    }
                                } elseif (isset($type_values[$type][$feature_id][$value_ids])) {
                                    $p['features'][$f['id']] = $type_values[$type][$feature_id][$value_ids];
                                }
                            }
                            $p['feature_values'][$f['id']] = $prod_feat[$p['index_key']][$feature_id];
                        } elseif ($type == shopFeatureModel::TYPE_DIVIDER) {
                            $p['features'][$f['id']] = '';
                        }
                    }
                }
            }
            unset($p);

            foreach ($features as $f) {
                $features[$f['id']]['values'] = isset($type_values[$f['type']][$f['id']]) ? $type_values[$f['type']][$f['id']] : array();
            }
            (new shopFlexdiscountApp())->set('runtime.features/all', $features);
            (new shopFlexdiscountApp())->set('runtime.features/product_features', $product_features);
            (new shopFlexdiscountApp())->set('runtime.features/processed', $processed);
        }

        foreach ($products as &$item) {
            $key = 'p' . $item['product_id'];
            // Получаем данные артикулов, если они имеются
            if (isset($product_features['s' . $item['sku_id']]['features'])) {
                $key = 's' . $item['sku_id'];
            }

            $item['product']['features'] = isset($product_features[$key]['features']) ? $product_features[$key]['features'] : array();
            // Если помимо артикулов имеется информация о товаре в целом, добавляем ее в выборку по характеристикам
            if (isset($product_features['p' . $item['product_id']]['features'])) {
                $item['product']['features'] += $product_features['p' . $item['product_id']]['features'];
            }
            $item['product']['feature_values'] = isset($product_features[$key]['feature_values']) ? $product_features[$key]['feature_values'] : array();
            if (isset($product_features['p' . $item['product_id']]['feature_values'])) {
                $item['product']['feature_values'] += $product_features['p' . $item['product_id']]['feature_values'];
            }
            if (isset($product_features[$key]['selectable_features'])) {
                $item['product']['selectable_features'] = $product_features[$key]['selectable_features'];
            }
        }

        return $features;
    }

    /**
     * Get product total sales.
     *
     * @param array $products
     * @param array $period
     * @param string $sum_type : 'not_sum', 'sum'
     * @param bool $skip_all_check
     * @return array
     */
    protected static function getTotalSales($products, $period, $sum_type = 'not_sum', $skip_all_check = false)
    {
        if (!$products) {
            return array();
        }

        $key = $period['start'] . '-' . $period['end'];

        $total_sales = shopFlexdiscountApp::get('runtime.shop/product_total_sales', []);

        // Если выборки по всем товарам не было, проводим ее
        if (!isset($total_sales[$key][$sum_type]) && !$skip_all_check) {
            self::getTotalSales(shopFlexdiscountApp::get('runtime.shop/products', []), $period, $sum_type, true);
        }

        // Определяем для каких товаров необходимо найти количество продаж
        $find = array();
        foreach ($products as &$item) {
            $id = shopFlexdiscountApp::getHelper()->getProductId($item, $sum_type == 'not_sum' ? 'sku' : 'product', isset($item['id']) ? $item['id'] : 0);
            if (!isset($total_sales[$key][$sum_type][$id]) && $id !== 0) {
                $find[$id] = $id;
            }
        }
        unset($item);

        // Если не было выборки по продажам или требуется найти продажи для товаров
        if ($find) {
            $order_model = new shopOrderModel();
            $order_subtotal = '(o.total+o.discount-o.tax-o.shipping)';
            $sql = "SELECT oi.product_id, oi.sku_id,
                    SUM(oi.price * o.rate * oi.quantity) subtotal,
                    SUM(IF({$order_subtotal} <= 0, 0, oi.price*o.rate*oi.quantity*o.discount / {$order_subtotal})) AS discount
                FROM " . $order_model->getTableName() . " o
                    JOIN shop_order_items oi
                        ON o.id = oi.order_id
                    JOIN shop_product_skus AS ps
                        ON oi.sku_id=ps.id 
                            AND oi.type = 'product'
                    WHERE oi." . ($sum_type == 'not_sum' ? 'sku' : 'product') . "_id IN ('" . implode("','", $find) . "') AND o.paid_date IS NOT NULL";
            if ($period['start']) {
                $sql .= " AND o.paid_date >= '" . $period['start'] . "'";
            }
            if ($period['end']) {
                $sql .= " AND o.paid_date <= '" . $period['end'] . "'";
            }
            $sql .= " GROUP BY oi." . ($sum_type == 'not_sum' ? 'sku' : 'product') . "_id";
            foreach ($order_model->query($sql) as $value) {
                $total_sales[$key][$sum_type][$value[($sum_type == 'not_sum' ? 'sku' : 'product') . '_id']] = $value['subtotal'] - $value['discount'];
            }
        }
        foreach ($products as $item) {
            $id = shopFlexdiscountApp::getHelper()->getProductId($item, $sum_type == 'not_sum' ? 'sku' : 'product', isset($item['id']) ? $item['id'] : 0);
            $total_sales[$key][$sum_type][$id] = isset($total_sales[$key][$sum_type][$id]) ? $total_sales[$key][$sum_type][$id] : 0;
        }

        (new shopFlexdiscountApp)->set('runtime.shop/product_total_sales', $total_sales);

        return $total_sales[$key][$sum_type];
    }

    /**
     * Get product total number of sales.
     *
     * @param array $products
     * @param array $period
     * @param string $sum_type : 'not_sum', 'sum'
     * @param bool $skip_all_check
     * @return array
     */
    protected static function getTotalNumberSales($products, $period, $sum_type = 'not_sum', $skip_all_check = false)
    {
        if (!$products) {
            return array();
        }

        $key = $period['start'] . '-' . $period['end'];

        $total_number_sales = shopFlexdiscountApp::get('runtime.shop/product_total_number_sales', []);

        // Если выборки по всем товарам не было, проводим ее
        if (!isset($total_number_sales[$key][$sum_type]) && !$skip_all_check) {
            self::getTotalNumberSales(shopFlexdiscountApp::get('runtime.shop/products', []), $period, $sum_type, true);
        }

        // Определяем для каких товаров необходимо найти количество продаж
        $find = array();
        foreach ($products as &$item) {
            $id = shopFlexdiscountApp::getHelper()->getProductId($item, $sum_type == 'not_sum' ? 'sku' : 'product', isset($item['id']) ? $item['id'] : 0);
            if (!isset($total_number_sales[$key][$sum_type][$id]) && $id !== 0) {
                $find[$id] = $id;
            }
        }
        unset($item);

        // Если не было выборки по продажам или требуется найти продажи для товаров
        if ($find) {
            $order_model = new shopOrderModel();
            $sql = "SELECT oi.product_id, oi.sku_id, SUM(oi.quantity) as  quant
                FROM " . $order_model->getTableName() . " o
                    JOIN shop_order_items oi
                        ON o.id = oi.order_id
                    JOIN shop_product_skus AS ps
                        ON oi.sku_id=ps.id 
                            AND oi.type = 'product'
                    WHERE oi." . ($sum_type == 'not_sum' ? 'sku' : 'product') . "_id IN ('" . implode("','", $find) . "')";
            if ($period['start']) {
                $sql .= " AND o.paid_date >= '" . $period['start'] . "'";
            }
            if ($period['end']) {
                $sql .= " AND o.paid_date <= '" . $period['end'] . "'";
            }
            $sql .= " GROUP BY oi." . ($sum_type == 'not_sum' ? 'sku' : 'product') . "_id";
            foreach ($order_model->query($sql) as $value) {
                $total_number_sales[$key][$sum_type][$value[($sum_type == 'not_sum' ? 'sku' : 'product') . '_id']] = $value['quant'];
            }
        }

        foreach ($products as $item) {
            $id = shopFlexdiscountApp::getHelper()->getProductId($item, $sum_type == 'not_sum' ? 'sku' : 'product', isset($item['id']) ? $item['id'] : 0);
            $total_number_sales[$key][$sum_type][$id] = isset($total_number_sales[$key][$sum_type][$id]) ? $total_number_sales[$key][$sum_type][$id] : 0;
        }

        (new shopFlexdiscountApp)->set('runtime.shop/product_total_number_sales', $total_number_sales);

        return $total_number_sales[$key][$sum_type];
    }

    /**
     * Get product stock change from log table
     *
     * @param array $products
     * @param string $sum_type : 'not_sum', 'sum'
     * @param string $action : 'increase', 'decrease'
     * @param array $period
     * @param bool $skip_max_date
     * @return array
     */
    protected static function getProductStockChange($products, $sum_type = 'not_sum', $action = 'increase', $period = [], $skip_max_date = false)
    {
        if (!$products) {
            return [];
        }

        $app = new shopFlexdiscountApp();
        $helper = $app::getHelper();
        $keys = [];

        $product_stock_change = $app::get('runtime.shop/product_stock_change', []);
        $start = ifset($period, 'start', '');
        $end = ifset($period, 'end', '');
        $datetime_key = '-d-' . $start . '-' . $end;

        foreach ($products as $k => $product) {
            if ($sum_type == 'not_sum') {
                // Если для артикула уже были получены данные, пропускаем обработку
                if (isset($product_stock_change[$sum_type . '-' . $action . $datetime_key . '-s' . $helper->getProductId($product, 'sku')])) {
                    unset($products[$k]);
                    continue;
                }
            } else {
                // Если для товара уже были получены данные, пропускаем обработку
                if (isset($product_stock_change[$sum_type . '-' . $action . $datetime_key . '-p' . $helper->getProductId($product)])) {
                    unset($products[$k]);
                    continue;
                }
            }
            $keys[] = $sum_type == 'not_sum' ? $helper->getProductId($product, 'sku') : $helper->getProductId($product);
        }

        if ($keys) {
            $id = $sum_type == 'not_sum' ? 'sku_id' : 'product_id';
            $spsl = new shopProductStocksLogModel();
            $condition = ($action == 'increase' ? '(diff_count > 0 OR after_count IS NULL)' : '(diff_count < 0 OR (before_count IS NULL AND after_count = 0))');
            $sql = "SELECT t1.datetime, t1.sku_id, t1.product_id FROM {$spsl->getTableName()} t1 ";
            if (!$skip_max_date) {
                $sql .= "INNER JOIN
                    (
                        SELECT sku_id, MAX(`datetime`) max_date
                        FROM {$spsl->getTableName()}
                        WHERE {$id} IN ('" . implode("','", $keys) . "') AND {$condition}
                        GROUP BY {$id}
                    ) t2 ON t1.{$id} = t2.{$id} AND t1.datetime = t2.max_date ";
            }
            $sql .= "WHERE 1=1 ";
            if ($start) {
                $sql .= "AND t1.datetime >= '" . $start . "' ";
            }
            if ($end) {
                $sql .= "AND t1.datetime <= '" . $end . "' ";
            }
            if ($skip_max_date) {
                $sql .= " AND t1.{$id} IN ('" . implode("','", $keys) . "') AND {$condition} ";
            }
            $sql .= "GROUP BY t1.{$id} ";
            foreach ($spsl->query($sql) as $r) {
                $product_stock_change[$sum_type . '-' . $action . $datetime_key . ($sum_type == 'not_sum' ? '-s' . $r['sku_id'] : '-p' . $r['product_id'])] = $r['datetime'];
            }
        }

        return $app->set('runtime.shop/product_stock_change', $product_stock_change);
    }

    /**
     * Get sku stocks.
     *
     * @return array
     */
    protected static function getSkuStocksCount()
    {
        $stocks = shopFlexdiscountApp::get('runtime.shop/product_sku_stocks', []);
        $product_skus = shopFlexdiscountApp::get('runtime.shop/product_sku_ids', []);

        // Если имеются товары, которые еще не были обработаны
        if (array_diff_key($product_skus, $stocks)) {
            // Определяем для каких товаров необходимо найти остатки
            $find = array();
            foreach ($product_skus as $sku_id => $product_id) {
                if ($sku_id !== 0) {
                    $find[$sku_id] = $sku_id;
                }
            }
            unset($item);

            if ($find) {
                $stock_counts = shopFlexdiscountHelper::getStockCounts($find);
                if ($stock_counts) {
                    foreach ($stock_counts as $sku_id => $stock_value) {
                        if (method_exists('shopHelper', 'fillVirtulStock') && is_callable(array('shopHelper', 'fillVirtulStock'))) {
                            $stocks[$sku_id] = shopHelper::fillVirtulStock($stock_value);
                        } else {
                            $stocks[$sku_id] = $stock_value;
                        }
                    }
                }
            }

            (new shopFlexdiscountApp())->set('runtime.shop/product_sku_stocks', $stocks);
        }

        return $stocks;
    }

    /**
     * Get user addresses
     *
     * @return array
     */
    protected static function getUserAddress()
    {
        $addresses = [];
        $contact = shopFlexdiscountApp::getContact()->get();
        if ($contact) {
            $addresses = $contact->get('address');
        }
        return $addresses;
    }

    /**
     * Get types data (arrays of categories, sets, features etc), which is used in conditions
     *
     * @param array $conditions
     * @param array $targets
     * @return array
     */
    protected static function getTypesData($conditions = array(), $targets = array())
    {
        if (self::$types_data === null) {
            self::$types_data = array();
            $condition_methods = self::getTypeMethods($conditions);
            $target_methods = self::getTypeMethods($targets);
            $methods = array_merge_recursive($condition_methods, $target_methods);

            $instance = self::getInstance();
            foreach ($methods as $type => $params) {
                $method_name = 'get' . ucfirst($type) . 'DataMethod';
                if (method_exists($instance, $method_name)) {
                    self::$types_data[$type] = self::$method_name($params);
                }
            }
        }
        return self::$types_data;
    }

    /**
     * Get unique types methods, which should be called to get types data
     *
     * @param array $conditions
     * @return array
     */
    private static function getTypeMethods($conditions)
    {
        $methods = array();
        if (isset($conditions['conditions'])) {
            foreach ($conditions['conditions'] as $condition) {
                $type = self::getTypeMethods($condition);
                if ($type) {
                    $methods = array_merge_recursive($methods, $type);
                }
            }
        } elseif (!empty($conditions['type'])) {
            $type = $conditions['type'];
            if (isset(self::$typesMethods[$type])) {
                if (is_array(self::$typesMethods[$type])) {
                    foreach (self::$typesMethods[$type] as $t) {
                        $value = self::getTypeValue($type, $t, $conditions);
                        if ($value !== $type) {
                            if (isset($conditions['product_type'])) {
                                $methods[$t][$conditions['product_type']][] = $value;
                            } else {
                                $methods[$t][] = $value;
                            }
                        } else {
                            $methods[$t] = $t;
                        }
                    }
                } else {
                    $value = self::getTypeValue($type, self::$typesMethods[$type], $conditions);
                    if ($value !== $type) {
                        if (isset($conditions['product_type'])) {
                            $methods[self::$typesMethods[$type]][$conditions['product_type']][] = $value;
                        } else {
                            $methods[self::$typesMethods[$type]][] = $value;
                        }
                    } else {
                        $methods[self::$typesMethods[$type]] = self::$typesMethods[$type];
                    }
                }
            }
        } elseif (is_array($conditions) && !empty($conditions[0]['target'])) {
            foreach ($conditions as $c) {
                if (isset($c['condition'])) {
                    $type = self::getTypeMethods($c['condition']);
                    if ($type) {
                        $methods = array_merge_recursive($methods, $type);
                    }
                }
            }
        }
        return $methods;
    }

    /**
     * Get type value from condition params
     *
     * @param string $type - current type
     * @param string $type_method - method name, which build the type
     * @param array $params - condition params
     * @return string
     */
    private static function getTypeValue($type, $type_method, $params)
    {
        if (in_array($type_method, array('product', 'user', 'country', 'userData2', 'stocks'))) {
            if (in_array($type, array('order_prod', 'product', 'user', 'order_prod_int'))) {
                return isset($params['value']) ? $params['value'] : '';
            } elseif (in_array($type, array('num_prod', 'num_cat', 'num_cat_all', 'num_set', 'num_type', 'order_prod_cat', 'user_country', 'order_prod_cat_all', 'user_data', 'stocks'))) {
                return isset($params['field']) ? $params['field'] : '';
            }
        } elseif ($type_method == 'feature') {
            return $params['field'];
        }
        return $type;
    }

    /**
     * Get abstract product data.
     *
     * @param bool|array $extra_data - Add some data so that the abstract product can skip rule validations
     * @return array
     */
    public static function getAbstractProduct($extra_data = [])
    {
        $data = array("id" => 0, "sku_id" => 0, "name" => "", "product_id" => 0, "price" => 1000, "compare_price" => 0, "purchase_price" => 0, "currency" => shopFlexdiscountApp::get('system')['current_currency'], "product" => array("id" => 0));
        if ($extra_data) {
            $data['sku_id'] = 9999999999;
            if (is_array($extra_data)) {
                foreach ($extra_data as $product_key => $product_value) {
                    $data[$product_key] = $product_value;
                    $data['primary_' . $product_key] = $product_value;
                }
            }
        }
        return $data;
    }

    /*     * *
     * Data methods
     * * */

    private static function getCategoryDataMethod()
    {
        // Категории товаров
        $categories = (new shopCategoryModel())->getTree(null);
        return shopFlexdiscountHelper::getCategoriesTree($categories);
    }

    private static function getSetDataMethod()
    {
        // Списки товаров
        return (new shopSetModel())->getAll('id');
    }

    private static function getTypeDataMethod()
    {
        // Типы товаров
        return (new shopTypeModel())->getTypes(true);
    }

    private static function getFeatureDataMethod($ids)
    {
        $return = array("features" => array(), "values" => array());
        // Значения характеристик товаров
        $sfm = new shopFeatureModel();
        $return['features'] = $sfm->getFeatures(true, null, 'id');
        if ($ids) {
            foreach ($ids as $id) {
                if ($id && isset($return['features'][$id])) {
                    $return['values'][$id] = $return['features'][$id];
                }
            }
            $return['values'] = $sfm->getValues($return['values'], true);
        }
        return $return;
    }

    public function getServicesData()
    {
        // Услуги
        static $data = null;
        if ($data === null) {
            $ssm = new shopServiceModel();
            $ssvm = new shopServiceVariantsModel();
            $sql = "SELECT s.id, s.name, IF(COUNT(sv.id) > 1, 1, 0) as selectable  FROM {$ssm->getTableName()} s LEFT JOIN {$ssvm->getTableName()} sv ON s.id = sv.service_id GROUP BY s.id";
            $services = $ssm->query($sql)->fetchAll('id');
            $variants = $ssvm->select("id, service_id, name")->fetchAll();
            $data = array('services' => $services, 'variants' => $variants);
        }
        return $data;
    }

    private static function getServicesDataMethod()
    {
        $instance = self::getInstance();
        return $instance->getServicesData();
    }

    private static function getCountryDataMethod($params)
    {
        $instance = new self();
        return $instance->getCountryData($params);
    }

    public function getCountryData($params = array())
    {
        $return = array("fields" => array(), "values" => array());
        $instance = new self();
        foreach ((new waCountryModel())->all() as $k => $c) {
            $return['fields'][$k] = array(
                'id' => $c['iso3letter'],
                'name' => $c['name']
            );
        }
        // Отбираем регионы
        if ($params) {
            // Массив стран, для которых нужно собрать регионы
            $fetch_countries = array();
            foreach ($params as $p) {
                if ($p) {
                    $fetch_countries[$p] = $p;
                }
            }
            if ($fetch_countries) {
                $return['values'] = $instance->getRegionsData($fetch_countries);
            }
        }
        return $return;
    }

    public function getRegionsData($countries)
    {
        $regions = array();
        $country_regions = (new waRegionModel())->getByCountry($countries);
        if ($country_regions) {
            foreach ($country_regions as $r) {
                $regions[$r['country_iso3']][$r['code']] = $r['name'];
            }
        }
        return $regions;
    }

    private static function getProductDataMethod($data)
    {
        $return = array("product" => array(), "sku" => array());
        if (!empty($data['product'])) {
            $hash = 'id/' . implode(",", $data['product']);
            $collection = new shopFlexdiscountProductsCollection($hash);
            $return['product'] = $collection->getProducts("*", 0, $collection->count());
        }
        if (!empty($data['sku'])) {
            $product_model = new shopProductModel();
            $psm = new shopProductSkusModel();
            $sql = "SELECT s.id, s.product_id, s.name as sku_name, s.sku, p.name as name FROM {$psm->getTableName()} s "
                . "LEFT JOIN {$product_model->getTableName()} p ON p.id = s.product_id "
                . "WHERE s.id IN ('" . implode("','", $psm->escape($data['sku'], 'int')) . "')";
            $return['sku'] = $psm->query($sql)->fetchAll('id');
        }
        return $return;
    }

    public function getTagsData()
    {
        // Теги
        static $tags = null;
        if ($tags === null) {
            $tags = (new shopTagModel())->select("id, name")->fetchAll();
        }
        return $tags;
    }

    private static function getTagsDataMethod()
    {
        $instance = self::getInstance();
        return $instance->getTagsData();
    }

    public function getParamsData()
    {
        // Дополнительные параметры товара
        static $params = null;
        if ($params === null) {
            $params = array();
            $params_model = new shopProductParamsModel();
            foreach ($params_model->query("SELECT DISTINCT(name) as name FROM {$params_model->getTableName()} GROUP BY name") as $r) {
                $params[] = array(
                    'id' => $r['name'],
                    'name' => $r['name'],
                );
            }
        }
        return $params;
    }

    private static function getParamsDataMethod()
    {
        $instance = self::getInstance();
        return $instance->getParamsData();
    }

    private static function getUcatDataMethod()
    {
        // Категории контакта
        try {
            // Проверяем наличие плагина Контакты PRO
            wa('contacts')->getPlugin('pro');
            $contact_categories = (new contactsViewModel())->getAllViews(null, true);
            contactsViewModel::setIcons($contact_categories);
        } catch (Exception $ex) {
            $contact_categories = (new waContactCategoryModel())->getAll('id');
        }
        return $contact_categories;
    }

    private static function getUserDataMethod($ids)
    {
        $hash = 'id/' . implode(",", $ids);
        $collection = new waContactsCollection($hash);
        return $collection->getContacts("*", 0, $collection->count());
    }

    private static function getShippingDataMethod()
    {
        $instance = self::getInstance();
        return $instance->getShippingData();
    }

    public function getShippingData()
    {
        // Плагины доставки
        $plugins = shopShipping::getList();
        $instances = (new shopPluginModel())->listPlugins(shopPluginModel::TYPE_SHIPPING);
        foreach ($instances as $k => $instance) {
            if (!isset($plugins[$instance['plugin']])) {
                unset($instances[$k]);
                continue;
            }
        }
        return $instances;
    }

    private static function getPaymentDataMethod()
    {
        $instance = self::getInstance();
        return $instance->getPaymentData();
    }

    public function getPaymentData()
    {
        // Плагины оплаты
        $plugins = shopPayment::getList();
        $instances = (new shopPluginModel())->listPlugins(shopPluginModel::TYPE_PAYMENT);
        foreach ($instances as $k => $instance) {
            if (!isset($plugins[$instance['plugin']])) {
                unset($instances[$k]);
                continue;
            }
        }
        return $instances;
    }

    private static function getStorefrontDataMethod()
    {
        // Домены и их правила маршрутизации
        wa('site');
        return (new siteDomainModel())->getAll('id');
    }

    public function getOrderStatusData()
    {
        // Статусы заказов
        static $data = null;
        if ($data === null) {
            $order_status = (new shopWorkflow())->getAllStates();
            $data[] = array(
                'id' => 'all_statuses',
                'name' => '* * * ' . _wp('All statuses') . ' * * *'
            );
            if ($order_status) {
                foreach ($order_status as $os) {
                    $data[] = array(
                        'id' => $os->id,
                        'name' => $os->name
                    );
                }
                $data[] = array(
                    'id' => 'paid_shipped',
                    'name' => $order_status['paid']->name . ' + ' . $order_status['shipped']->name
                );
                $data[] = array(
                    'id' => 'paid_completed',
                    'name' => $order_status['paid']->name . ' + ' . $order_status['completed']->name
                );
                $data[] = array(
                    'id' => 'shipped_completed',
                    'name' => $order_status['shipped']->name . ' + ' . $order_status['completed']->name
                );
                $data[] = array(
                    'id' => 'paid_shipped_completed',
                    'name' => $order_status['paid']->name . ' + ' . $order_status['shipped']->name . ' + ' . $order_status['completed']->name
                );
            }
        }
        return $data;
    }

    private static function getOrderStatusDataMethod()
    {
        $instance = new self();
        return $instance->getOrderStatusData();
    }

    public function getStocksData()
    {
        // Склады
        static $data = null;
        if ($data === null) {
            $stocks = shopFlexdiscountHelper::getStocks();
            $data[] = array('id' => 'all', 'class' => 'show-stock-options', 'name' => _wp('all stocks'));
            $data[] = array('id' => 'any', 'class' => 'show-stock-options', 'name' => _wp('any stock'));
            $data[] = array('id' => 'each', 'class' => 'show-stock-options', 'name' => _wp('each stock'));
            if ($stocks) {
                foreach ($stocks as $s_id => $s) {
                    $data[] = array(
                        'id' => $s_id,
                        'name' => $s['name']
                    );
                }
            }
        }
        return $data;
    }

    private static function getStocksDataMethod()
    {
        $instance = new self();
        return $instance->getStocksData();
    }

    public function getUserData()
    {
        // Данные пользователя
        static $data = null;
        if ($data === null) {
            $fields = waContactFields::getAll();
            if ($fields) {
                foreach ($fields as $k => $d) {
                    if (!$d instanceof waContactCompositeField) {
                        $data[] = array(
                            'id' => $k,
                            'name' => $d->getName(null, true)
                        );
                    }
                }
            }
        }
        return $data;
    }

    public function getCustomerSource()
    {
        $sources = [];

        $sales_model = new shopSalesModel();
        if (version_compare(shopFlexdiscountApp::get('system')['wa']->getVersion(), '7.4.0', '>=')) {
            $sales_channels = $sales_model->getAllSalesChannels();
        } else {
            $sql = "SELECT op.value AS channel, MAX(op.order_id) AS time
                FROM shop_order_params AS op
                WHERE op.name='referer_host'
                GROUP BY channel
                ORDER BY time DESC";
            $sales_channels = array_values(array_filter(array_keys($sales_model->query($sql)->fetchAll('channel', true))));
        }
        if ($sales_channels) {
            foreach ($sales_channels as $channel) {
                $sources[] = [
                    'id' => $channel,
                    'name' => $channel
                ];
            }
        }
        return $sources;
    }

    private static function getCustomerSourceDataMethod()
    {
        $instance = new self();
        return $instance->getCustomerSource();
    }

    private static function getUserData2DataMethod()
    {
        $instance = new self();
        return $instance->getUserData();
    }

    protected static function getModel($name = 'category')
    {
        if (!isset(self::$models[$name])) {
            if (in_array($name, array('category', 'product'))) {
                $class_name = 'shop' . ucfirst($name) . 'Model';
                self::$models[$name] = new $class_name();
            }
        }
        return self::$models[$name];
    }

    /**
     * Find product params
     *
     * @param array $product_ids
     */
    private static function findProductParams($product_ids)
    {
        $find_ids = [];
        $product_params = shopFlexdiscountApp::get("runtime.shop/product_params");
        foreach ($product_ids as $product_id) {
            if (!isset($product_params[$product_id])) {
                $find_ids[] = $product_id;
            }
        }
        if ($find_ids) {
            $product_params_model = new shopProductParamsModel();
            $rows = $product_params_model->getByField('product_id', $find_ids, true);
            foreach ($rows as $row) {
                $product_params[$row['product_id']][$row['name']] = $row['value'];
            }
            foreach ($find_ids as $p_id) {
                if (!isset($product_params[$p_id])) {
                    $product_params[$p_id] = '';
                }
            }
            (new shopFlexdiscountApp())->set("runtime.shop/product_params", $product_params);
        }
    }

    /**
     * @param int $order_id
     * @param bool $update
     * @return array
     * @deprecated
     */
    public static function getOrderCalculateDiscount($order_id = 0, $update = false)
    {
        return shopFlexdiscountApp::getOrder()->getOrderCalculateDiscount($order_id, $update);
    }

    /**
     * @param $products
     * @deprecated
     */
    public function setShopProducts($products)
    {
        if ($products) {
            (new shopFlexdiscountApp())->set('runtime.shop/products', shopFlexdiscountApp::getHelper()->prepareShopProducts($products));
        }
    }

}

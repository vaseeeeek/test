<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopAutobadgeData
{
    protected static $types_data = null;
    // Все товары со страницы магазина
    private static $shop_products = array();
    // Информация о заказе
    private static $order = array();
    // Параметры товара
    private static $product_params = null;
    private static $typesMethods = array(
        'cat' => 'category',
        'cat_all' => 'category',
        'set' => 'set',
        'type' => 'type',
        'product' => 'product',
        'feature' => 'feature',
        'params' => 'params',
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
        'product_stock' => 'stocks',
        'time' => 'time',
        'product_tags' => 'tags',
        'theme' => 'theme',
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
     * Add to array of all products on the page
     *
     * @param array $products
     */
    public function setShopProducts($products)
    {
        if (!empty($products)) {
            foreach ($products as $p) {
                $product = ($p instanceof shopProduct) ? $p->getData() : $p;
                $product_id = is_array($product) ? (isset($product['product_id']) ? (int) $product['product_id'] : (isset($product['product']['id']) ? (int) $product['product']['id'] : (isset($product['id']) ? (int) $product['id'] : 0))) : ($product ? (int) $product : 0);
                if (!isset(self::$shop_products[$product_id]) && $product_id !== 0) {
                    self::$shop_products[$product_id] = $product;
                }
            }
        }
    }

    public static function getShopProducts()
    {
        return self::$shop_products;
    }

    /**
     * Get information about order
     *
     * @param string $name
     * @return array|string
     */
    public static function getOrderInfo($name = null)
    {
        static $inited = 0;
        if (!empty(self::$order['id']) && !$inited) {
            self::$order += (new shopOrderModel())->select('create_datetime, total, currency')->where('id = "' . (int) self::$order['id'] . '"')->fetchAssoc();
            self::$order['params'] = (new shopOrderParamsModel())->get(self::$order['id']);
            $inited = 1;
        }
        return $name ? (!empty(self::$order[$name]) ? self::$order[$name] : '') : self::$order;
    }

    /**
     * Set order information
     *
     * @param array $order
     * @return array
     */
    protected static function setOrderInfo($order)
    {
        self::$order = $order;
        unset(self::$order['items'], self::$order['contact']);
        return self::$order;
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
     * @return array
     */
    public static function getCategoryProducts($category_id, $include_subcat = false)
    {
        static $results = array();

        $products = self::getShopProducts();
        if (!$products) {
            return array();
        }
        $products = array_keys($products);

        // Создаем уникальный хеш операции
        $hash = self::getRequestHash($category_id, $include_subcat, $products);
        if (isset($results[$hash])) {
            return $results[$hash];
        }

        $category_ids = array();
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
            $collection = new shopAutobadgeProductsCollection($products);
            $alias = $collection->addJoin('shop_category_products');
            $collection->addWhere($alias . ".category_id IN(" . implode(',', $category_ids) . ")");
            $products = $collection->getProducts('id, currency', 0, $collection->count());
        } else {
            // Получаем все товары динамической категории
            $collection = new shopAutobadgeProductsCollection('category/' . $category_id);
            $collection->addWhere('p.id IN (' . implode(',', $products) . ')');
            $products = $collection->getProducts("id, currency", 0, $collection->count());
        }

        $results[$hash] = $products ? array_keys($products) : array();
        return $results[$hash];
    }

    /**
     * Get products belong to set
     *
     * @param int $set_id
     * @return array
     */
    public static function getSetProducts($set_id)
    {
        static $results = array();

        $products = self::getShopProducts();
        if (!$products) {
            return array();
        }
        $products = array_filter(array_keys($products));

        // Создаем уникальный хеш операции
        $hash = self::getRequestHash($set_id, $products);
        if (isset($results[$hash])) {
            return $results[$hash];
        }

        $set = (new shopSetModel())->getById($set_id);
        $collection = new shopAutobadgeProductsCollection('set/' . $set_id);
        if (!empty($set) && $set['type'] == shopSetModel::TYPE_STATIC) {
            $collection->addWhere('p.id IN (' . implode(',', $products) . ')');
        }
        $products = $collection->getProducts("id, currency", 0, $collection->count());

        $results[$hash] = $products ? array_keys($products) : array();
        return $results[$hash];
    }

    /**
     * Find product params
     *
     * @param array $product_ids
     */
    private static function findProductParams($product_ids)
    {
        $product_params_model = new shopProductParamsModel();
        $rows = $product_params_model->getByField('product_id', $product_ids, true);
        foreach ($rows as $row) {
            self::$product_params[$row['product_id']][$row['name']] = $row['value'];
        }
        foreach ($product_ids as $p_id) {
            if (!isset(self::$product_params[$p_id])) {
                self::$product_params[$p_id] = '';
            }
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
        if (!isset(self::$product_params[$product_id])) {
            $products = self::getShopProducts();
            self::findProductParams(array_keys($products));
        }

        return isset(self::$product_params[$product_id]) ? self::$product_params[$product_id] : '';
    }

    /**
     * Get product features. Add features array to products
     *
     * @param array $products
     * @return array
     */
    public static function getFeatures(&$products)
    {
        static $features = null;
        static $product_features = array();

        if (!$products) {
            return array();
        }

        $instance = self::getInstance();

        // Определяем для каких товаров необходимо найти характеристики
        $find_features = false;
        foreach ($products as &$item) {
            $item['product_id'] = isset($item['product_id']) ? (int) $item['product_id'] : (isset($item['product']['id']) ? (int) $item['product']['id'] : (isset($item['id']) ? $item['id'] : 0));
            if (!isset($product_features[$item['product_id']]['features']) && $item['product_id'] !== 0) {
                $instance->setShopProducts(array($item));
                $find_features = true;
            }
        }

        // Если не было выборки по характеристикам или требуется найти характеристики для товаров
        if ($features === null || $find_features) {

            $product_features = self::getShopProducts();

            $product_features_model = new shopProductFeaturesModel();
            $rows = $product_features_model->getByField(array(
                'product_id' => array_keys($product_features),
                'sku_id' => null
            ), true);

            /* Ищем товары, у которых в качестве артикулов используются характеристики */
            $selectable_product_ids = $selectable_features = array();
            foreach ($product_features as &$p) {
                $p['id'] = isset($p['product_id']) ? (int) $p['product_id'] : (isset($p['product']['id']) ? (int) $p['product']['id'] : $p['id']);
                if (!empty($p['sku_type']) || !empty($p['product']['sku_type'])) {
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
            $features = $feature_model->query($sql, array('ids' => array_keys($tmp)))->fetchAll('id');

            $type_values = $prod_feat = array();
            foreach ($rows as $row) {
                if (empty($features[$row['feature_id']])) {
                    continue;
                }
                $f = $features[$row['feature_id']];
                $type = preg_replace('/\..*$/', '', $f['type']);
                if ($type != shopFeatureModel::TYPE_BOOLEAN && $type != shopFeatureModel::TYPE_DIVIDER) {
                    $type_values[$type][$row['feature_value_id']] = $row['feature_value_id'];
                }
                if ($f['multiple']) {
                    $prod_feat[$row['product_id']][$f['id']][$row['feature_value_id']] = $row['feature_value_id'];
                } else {
                    $prod_feat[$row['product_id']][$f['id']] = $row['feature_value_id'];
                }
            }
            foreach ($type_values as $type => $value_ids) {
                $model = shopFeatureModel::getValuesModel($type);
                if ($model) {
                    $type_values[$type] = $model->getValues('id', $value_ids);
                }
            }

            $tmp = array();
            foreach ($product_features as $k => $p) {
                $product_features[$k]['type_id'] = isset($p['type_id']) ? $p['type_id'] : (isset($p['product']['type_id']) ? $p['product']['type_id'] : 0);
                if (isset($selectable_features[$k])) {
                    $product_features[$k]['selectable_features'] = $selectable_features[$k];
                }
                $tmp[(int) $product_features[$k]['type_id']] = true;
            }

            // get type features for correct sort
            $type_features_model = new shopTypeFeaturesModel();
            $sql = "SELECT type_id, feature_id FROM " . $type_features_model->getTableName() . "
                WHERE type_id IN (i:type_id) ORDER BY sort";
            $rows = $type_features_model->query($sql, array('type_id' => array_keys($tmp)))->fetchAll();
            $type_features = array();
            foreach ($rows as $row) {
                $type_features[$row['type_id']][] = $row['feature_id'];
            }

            foreach ($product_features as &$p) {
                $p['features'] = $p['feature_values'] = array();
                if (!empty($type_features[$p['type_id']])) {
                    foreach ($type_features[$p['type_id']] as $feature_id) {
                        if (empty($features[$feature_id])) {
                            continue;
                        }
                        $f = $features[$feature_id];
                        $type = preg_replace('/\..*$/', '', $f['type']);
                        if (isset($prod_feat[$p['id']][$feature_id])) {
                            $value_ids = $prod_feat[$p['id']][$feature_id];
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
                            $p['feature_values'][$f['id']] = $prod_feat[$p['id']][$feature_id];
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
        }

        foreach ($products as &$item) {
            $item['product']['features'] = isset($product_features[$item['product_id']]['features']) ? $product_features[$item['product_id']]['features'] : array();
            $item['product']['feature_values'] = isset($product_features[$item['product_id']]['feature_values']) ? $product_features[$item['product_id']]['feature_values'] : array();
            if (isset($product_features[$item['product_id']]['selectable_features'])) {
                $item['product']['selectable_features'] = $product_features[$item['product_id']]['selectable_features'];
            }
        }

        return $features;
    }

    /**
     * Get product total sales.
     *
     * @param array $products
     * @param array $period
     * @param bool $skip_all_check
     * @return array
     */
    public static function getTotalSales($products, $period, $skip_all_check = false)
    {
        static $total_sales = null;

        if (!$products) {
            return array();
        }

        $key = $period['start'] . '-' . $period['end'];

        // Если выборки по всем товарам не было, проводим ее
        if (!isset($total_sales[$key]) && !$skip_all_check) {
            self::getTotalSales(self::getShopProducts(), $period, true);
        }

        // Определяем для каких товаров необходимо найти количество продаж
        $find = array();
        foreach ($products as &$item) {
            $item['product_id'] = isset($item['product_id']) ? (int) $item['product_id'] : (isset($item['product']['id']) ? (int) $item['product']['id'] : $item['id']);
            if (!isset($total_sales[$key][$item['product_id']]) && $item['id'] !== 0) {
                $find[$item['product_id']] = $item['product_id'];
            }
        }
        unset($item);

        // Если не было выборки по продажам или требуется найти продажи для товаров
        if ($find) {
            $order_model = new shopOrderModel();
            $order_subtotal = '(o.total+o.discount-o.tax-o.shipping)';
            $sql = "SELECT oi.product_id,
                    SUM(oi.price * o.rate * oi.quantity) subtotal,
                    SUM(IF({$order_subtotal} <= 0, 0, oi.price*o.rate*oi.quantity*o.discount / {$order_subtotal})) AS discount
                FROM " . $order_model->getTableName() . " o
                    JOIN shop_order_items oi
                        ON o.id = oi.order_id
                    JOIN shop_product_skus AS ps
                        ON oi.sku_id=ps.id 
                            AND oi.type = 'product'
                    WHERE oi.product_id IN ('" . implode("','", $find) . "') AND o.paid_date IS NOT NULL";
            if ($period['start']) {
                $sql .= " AND o.paid_date >= '" . $period['start'] . "'";
            }
            if ($period['end']) {
                $sql .= " AND o.paid_date <= '" . $period['end'] . "'";
            }
            $sql .= " GROUP BY oi.product_id";
            foreach ($order_model->query($sql) as $value) {
                $total_sales[$key][$value['product_id']] = $value['subtotal'] - $value['discount'];
            }
        }

        foreach ($products as $item) {
            $total_sales[$key][$item['product_id']] = isset($total_sales[$key][$item['product_id']]) ? $total_sales[$key][$item['product_id']] : 0;
        }
        return $total_sales[$key];
    }

    /**
     * Get product total number of sales.
     *
     * @param array $products
     * @param array $period
     * @param bool $skip_all_check
     * @return array
     */
    public static function getTotalNumberSales($products, $period, $skip_all_check = false)
    {
        static $total_number_sales = null;

        if (!$products) {
            return array();
        }

        $key = $period['start'] . '-' . $period['end'];

        // Если выборки по всем товарам не было, проводим ее
        if (!isset($total_number_sales[$key]) && !$skip_all_check) {
            self::getTotalNumberSales(self::getShopProducts(), $period, true);
        }

        // Определяем для каких товаров необходимо найти количество продаж
        $find = array();
        foreach ($products as &$item) {
            $item['product_id'] = isset($item['product_id']) ? (int) $item['product_id'] : (isset($item['product']['id']) ? (int) $item['product']['id'] : $item['id']);
            if (!isset($total_number_sales[$key][$item['product_id']]) && $item['id'] !== 0) {
                $find[$item['product_id']] = $item['product_id'];
            }
        }
        unset($item);

        // Если не было выборки по продажам или требуется найти продажи для товаров
        if ($find) {
            $order_model = new shopOrderModel();
            $sql = "SELECT oi.product_id, SUM(oi.quantity) as  quant
                FROM " . $order_model->getTableName() . " o
                    JOIN shop_order_items oi
                        ON o.id = oi.order_id
                    JOIN shop_product_skus AS ps
                        ON oi.sku_id=ps.id 
                            AND oi.type = 'product'
                    WHERE oi.product_id IN ('" . implode("','", $find) . "')";
            if ($period['start']) {
                $sql .= " AND o.paid_date >= '" . $period['start'] . "'";
            }
            if ($period['end']) {
                $sql .= " AND o.paid_date <= '" . $period['end'] . "'";
            }
            $sql .= " GROUP BY oi.product_id";
            foreach ($order_model->query($sql) as $value) {
                $total_number_sales[$key][$value['product_id']] = $value['quant'];
            }
        }

        foreach ($products as $item) {
            $total_number_sales[$key][$item['product_id']] = isset($total_number_sales[$key][$item['product_id']]) ? $total_number_sales[$key][$item['product_id']] : 0;
        }
        return $total_number_sales[$key];
    }

    /**
     * Get sku stocks.
     *
     * @param string $type - sku|product
     * @return array
     * @throws waException
     */
    protected static function getSkuStocksCount($type = 'sku')
    {
        static $product_stocks_count = [];
        $products = (new shopAutobadgeData())->getShopProducts();
        // Определяем для каких товаров необходимо найти остатки
        $find = $find_product_id = array();
        foreach ($products as &$item) {
            $id = self::getProductId($item, 'sku');
            $key = $type . self::getProductId($item, $type);
            if ($id !== 0 && !isset($product_stocks_count[$key])) {
                $find[$id] = $id;
                $product_id = self::getProductId($item, 'product');
                $find_product_id[$product_id] = $product_id;
            }
        }
        unset($item);

        if ($find) {

            if ($type === 'product') {

                $sku_model = new shopProductSkusModel();
                $product_skus = $find_sku_ids = [];
                $sql = "SELECT id, product_id, `count` FROM {$sku_model->getTableName()} WHERE product_id IN (?)";
                foreach ($sku_model->query($sql, [$find_product_id]) as $r) {
                    $product_skus[$r['product_id']][$r['id']] = $r['count'];
                    $find_sku_ids[] = $r['id'];
                }

                $stock_counts = shopAutobadgeHelper::getStockCounts($find_sku_ids);

                $stock_counts_with_virtual = [];

                foreach ($product_skus as $p_id => $skus) {
                    foreach ($skus as $sku_id => $count) {
                        if (isset($stock_counts[$sku_id])) {
                            if (method_exists('shopHelper', 'fillVirtulStock') && is_callable(array('shopHelper', 'fillVirtulStock'))) {
                                $stock_counts_with_virtual[$sku_id] = shopHelper::fillVirtulStock($stock_counts[$sku_id]);
                            } else {
                                $stock_counts_with_virtual[$sku_id] = $stock_counts[$sku_id];
                            }
                        }
                    }
                }

                foreach ($product_skus as $id => $product_sku) {
                    $key = $type . $id;
                    foreach ($product_sku as $sku_id => $count) {
                        if (!isset($product_stocks_count[$key])) {
                            $product_stocks_count[$key] = array('count' => 0);
                        }
                        $stocks = shopAutobadgeHelper::getStocks();
                        if ($stocks) {
                            // Суммируем значение остатков на складах
                            foreach ($stocks as $st_id => $st) {
                                if (isset($stock_counts_with_virtual[$sku_id][$st_id])) {
                                    if (!isset($product_stocks_count[$key][$st_id])) {
                                        $product_stocks_count[$key][$st_id] = 0;
                                    }
                                    $product_stocks_count[$key][$st_id] += $stock_counts_with_virtual[$sku_id][$st_id];
                                } elseif ($count === null) {
                                    $product_stocks_count[$key][$st_id] = 2147483647;
                                }
                            }
                        }

                        $product_stocks_count[$key]['count'] = (($count === 2147483647 || $count === null) ? 2147483647 : ($count + $product_stocks_count[$key]['count']));
                    }
                }
            } else {
                $stock_counts = shopAutobadgeHelper::getStockCounts($find);

                foreach ($products as $item) {
                    $id = self::getProductId($item, 'sku');
                    $key = $type . $id;
                    if (isset($stock_counts[$id])) {
                        if (method_exists('shopHelper', 'fillVirtulStock') && is_callable(array('shopHelper', 'fillVirtulStock'))) {
                            $product_stocks_count[$key] = shopHelper::fillVirtulStock($stock_counts[$id]);
                        } else {
                            $product_stocks_count[$key] = $stock_counts[$id];
                        }
                    }
                }
            }
        }
        return $product_stocks_count;
    }

    /**
     *
     * @param array $item
     * @param string $product_type - sku or product
     * @return int
     */
    protected static function getProductId($item, $product_type = 'product')
    {
        if ($product_type == 'sku') {
            return isset($item['sku_id']) ? (int) $item['sku_id'] : (isset($item['product']['sku_id']) ? (int) $item['product']['sku_id'] : 0);
        } else {
            return isset($item['product_id']) ? (int) $item['product_id'] : (isset($item['product']['id']) ? (int) $item['product']['id'] : (isset($item['id']) ? $item['id'] : 0));
        }
    }

    /**
     * Get types data (arrays of categories, sets, features etc), which is used in conditions
     *
     * @param array $conditions
     * @return array
     */
    protected static function getTypesData($conditions = array())
    {
        if (self::$types_data === null) {
            self::$types_data = array();
            $methods = self::getTypeMethods($conditions);

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
        if ($type_method == 'product' || $type_method == 'user') {
            if (in_array($type, array('order_prod', 'product', 'user', 'order_prod_int'))) {
                return isset($params['value']) ? $params['value'] : '';
            } elseif (in_array($type, array('num_prod', 'num_cat', 'num_cat_all', 'num_set', 'num_type', 'order_prod_cat', 'order_prod_cat_all'))) {
                return isset($params['field']) ? $params['field'] : '';
            }
        } elseif ($type_method == 'feature') {
            return $params['field'];
        }
        return $type;
    }

    public static function getProductWorkflow($product, $sku_id = 0)
    {
        if (is_int($product)) {
            $product = new shopProduct($product);
            if (!$product['id']) {
                return array();
            }
        } else {
            $product = ($product instanceof shopProduct) ? $product->getData() : $product;
            if (!isset($product['sku_id'])) {
                return array();
            }
        }
        $sku_id = $sku_id ? $sku_id : $product['sku_id'];

        $cache_work = new waRuntimeCache('autobadge_product_workflows');
        $workflows = $cache_work->get();

        if (isset($workflows[$sku_id])) {
            return $workflows[$sku_id];
        }
        return array();
    }

    protected static function getAbstractProduct()
    {
        return array("id" => 0, "sku_id" => 0, "name" => "", "product_id" => 0, "price" => 0, "compare_price" => 0, "purchase_price" => 0, "currency" => wa('shop')->getConfig()->getCurrency(false), "product" => array("id" => 0));
    }

    /*     * *
     * Data methods
     * * */

    private static function getCategoryDataMethod()
    {
        // Категории товаров
        $categories = (new shopCategoryModel())->getTree(null);
        return shopAutobadgeHelper::getCategoriesTree($categories);
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

    public function getStocksData()
    {
        // Склады
        static $data = null;
        if ($data === null) {
            $stocks = shopAutobadgeHelper::getStocks();
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

    private static function getProductDataMethod($data)
    {
        $return = array("product" => array(), "sku" => array());
        if (!empty($data['product'])) {
            $hash = 'id/' . implode(",", $data['product']);
            $collection = new shopAutobadgeProductsCollection($hash);
            $return['product'] = $collection->getProducts("*", 0, $collection->count());
        }
        if (!empty($data['sku'])) {
            $product_model = new shopProductModel();
            $psm = new shopProductSkusModel();
            $sql = "SELECT s.id, s.product_id, s.name as sku_name, s.sku, p.name as name FROM {$psm->getTableName()} s "
                . "LEFT JOIN {$product_model->getTableName()} p ON p.id = s.product_id "
                . "WHERE s.id IN ('" . implode("','", $data['sku']) . "')";
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
        $instances = (new shopPluginModel())->listPlugins(shopPluginModel::TYPE_SHIPPING, array('all' => true));
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
        $instances = (new shopPluginModel())->listPlugins(shopPluginModel::TYPE_PAYMENT, array('all' => true));
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

    private static function getThemeDataMethod()
    {
        return wa('shop')->getThemes();
    }

    public function getTargetData()
    {
        $target = array();
        /**
         * Наклейки плагина
         */
        $target['autobadge'] = array(
            "name" => _wp("Your template badges"),
            "fields" => array()
        );
        $sat = new shopAutobadgeTemplatePluginModel();
        $templates = $sat->getAll('id');
        if ($templates) {
            foreach ($templates as $db_id => $db) {
                $templates[$db_id]['settings'] = unserialize($db['settings']);
                $target['autobadge']['fields']["autobadge-" . $db_id] = $db['name'];
            }
        }

        /**
         * Стандартные наклейки
         */
        $default_badges = shopProductModel::badges();
        if ($default_badges) {
            $target['default'] = array(
                "name" => _wp("Default Webasyst badges"),
                "fields" => array()
            );
            foreach ($default_badges as $db_id => $db) {
                $target['default']['fields']["default-" . $db_id] = $db['name'];
            }
        }

        return $target;
    }

    protected static function getModel($name = 'category')
    {
        if (!isset(self::$models[$name])) {
            if (in_array($name, array('category'))) {
                $class_name = 'shop' . ucfirst($name) . 'Model';
                self::$models[$name] = new $class_name();
            }
        }
        return self::$models[$name];
    }

    /**
     * Create hash from parameters. Uses for saving request values by hash
     *
     * @return string
     */
    protected static function getRequestHash()
    {
        $args = func_get_args();
        if ($args) {
            $string = '';
            foreach ($args as $arg) {
                if (is_array($arg)) {
                    sort($arg);
                    $string .= json_encode($arg);
                } elseif (is_bool($arg)) {
                    $string .= !!$arg;
                } else {
                    $string .= $arg;
                }
            }
            $hash = md5($string);

            return $hash;
        }
        return '';
    }

}

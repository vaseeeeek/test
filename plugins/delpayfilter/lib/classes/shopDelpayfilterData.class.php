<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopDelpayfilterData
{

    protected static $user = array();
    protected static $types_data = null;
    // Все категории с товарами
    private static $categories = null;
    // Дерево категорий
    private static $category_tree = array();
    // Выборка по динамичным товарам
    private static $dynamic_products = array();
    // Выборка по спискам
    private static $sets = array();
    // Все услуги в заказе
    private static $order_services = array();
    private static $order_items = array();
    private static $typesMethods = array(
        'cat' => 'category',
        'cat_all' => 'category',
        'num_all_cat' => 'category',
        'num_all_cat_all' => 'category',
        'num_all_set' => 'set',
        'num_all_type' => 'type',
        'set' => 'set',
        'type' => 'type',
        'product' => 'product',
        'feature' => 'feature',
        'services' => 'services',
        'num_prod' => 'product',
        'num_cat' => array('category', 'product'),
        'num_set' => array('set', 'product'),
        'num_type' => array('type', 'product'),
        'num_cat_all' => array('category', 'product'),
        'num_feat' => 'feature',
        'sum_cat' => 'category',
        'sum_cat_all' => 'category',
        'sum_feat' => 'feature',
        'total_feat' => 'feature',
        'ucat' => 'ucat',
        'user' => 'user',
        'user_country' => 'country',
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
        'order_status' => 'orderStatus',
        'product_stock' => 'stocks',
        'user_data' => 'userData2',
    );

    protected static function setOrderItems($items)
    {
        self::$order_items = $items;
    }

    protected static function getOrderItems()
    {
        return self::$order_items;
    }

    /**
     * Get products, belong to categories
     *
     * @return array
     */
    public static function getCategories()
    {
        if (self::$categories === null) {
            $scp = new shopCategoryProductsModel();
            self::$categories = $scp->select("product_id, category_id")->fetchAll('category_id', 2);
            // Дерево категорий
            self::getCategoryTree();
        }
        return self::$categories;
    }

    /**
     * Get categories tree
     *
     * @return array
     */
    public static function getCategoriesTree()
    {
        return self::getCategoryTree();
    }

    /**
     * Build category tree
     *
     * @return array
     */
    private static function getCategoryTree()
    {
        if (self::$category_tree) {
            return self::$category_tree;
        }
        $category_model = new shopCategoryModel();
        $categories = $category_model->getTree(null);
        // Указатели на узлы дерева
        $tree = array();
        $tree[0]['path'] = array();
        $finish = false;
        // Не заканчиваем, пока не закончатся категории, или пока ни одну из оставшихся некуда будет деть
        while (!empty($categories) && !$finish) {
            $flag = false;
            foreach ($categories as $k => $category) {
                if (isset($tree[$category['parent_id']])) {
                    $tree[$category['id']] = $category;
                    $tree[$category['id']]['path'] = array_merge((array) $tree[$category['parent_id']]['path'], array($tree[$category['id']]));
                    unset($categories[$k]);
                    $flag = true;
                }
            }
            if (!$flag) {
                $finish = true;
            }
        }
        $ids = array_reverse(array_keys($tree));
        foreach ($ids as $id) {
            if ($id > 0) {
                $tree[$id]['children'][] = $id;
                if (isset($tree[$tree[$id]['parent_id']]['children'])) {
                    $tree[$tree[$id]['parent_id']]['children'] = array_merge($tree[$id]['children'], $tree[$tree[$id]['parent_id']]['children']);
                } else {
                    $tree[$tree[$id]['parent_id']]['children'] = $tree[$id]['children'];
                }
            }
        }
        $tree[0]['children'] = array(0);
        $tree[0]['type'] = 0;

        self::$category_tree = $tree;
    }

    /**
     * Get order services
     *
     * @return array
     */
    protected static function getServices()
    {
        return self::$order_services;
    }

    /**
     * Add order services
     */
    public static function addToServices($service_id, $service_variant_id)
    {
        self::$order_services[$service_id][$service_variant_id] = $service_variant_id;
    }

    /**
     * Get products belong to category
     *
     * @param int $category_id
     * @param bool $include_subcat
     * @return array
     */
    public static function getCategoryProducts($category_id, $include_subcat = false)
    {
        $products = array();
        $categories = self::getCategories();
        // Если категория существует
        if (isset(self::$category_tree[$category_id])) {
            $children = self::$category_tree[$category_id]['children'];
            // Если категория не динамическая
            if (!self::$category_tree[$category_id]['type']) {
                // Включаем товары из подкатегорий при необходимости
                if ($include_subcat) {
                    foreach ($children as $child) {
                        if (isset($categories[$child])) {
                            $products = array_merge($products, $categories[$child]);
                        }
                    }
                } else {
                    $products = isset($categories[$category_id]) ? $categories[$category_id] : array();
                }
            } // Если категория динамическая
            else {
                // Если выборки по данной категории не было, то делаем запрос
                if (!isset(self::$dynamic_products[$category_id])) {
                    // Получаем все товары динамической категории
                    $pc = new shopProductsCollection('category/' . $category_id);
                    $dynamic_products = $pc->getProducts("*", 0, $pc->count());
                    self::$dynamic_products[$category_id] = array_keys($dynamic_products);
                }
                $products = self::$dynamic_products[$category_id];
            }
        }
        return $products;
    }

    /**
     * Get products belong to set
     *
     * @param int $set_id
     * @return array
     */
    public static function getSetProducts($set_id)
    {
        if (!isset(self::$sets[$set_id])) {
            $pc = new shopProductsCollection('set/' . $set_id);
            $products = $pc->getProducts("*", 0, $pc->count());
            self::$sets[$set_id] = $products ? array_keys($products) : array();
        }
        return self::$sets[$set_id];
    }

    /**
     * Get product features. Add features array to products
     *
     * @param array $items
     * @return array
     */
    public static function getFeatures(&$items)
    {
        static $features = null;
        static $product_features = array();

        if ($features === null) {
            $order_items = self::getOrderItems();
            $products = array();
            // Выбираем товары
            foreach ($order_items as $item) {
                $product_id = isset($item['product_id']) ? (int) $item['product_id'] : (isset($item['product']['id']) ? (int) $item['product']['id'] : 0);
                $products[$product_id] = $item['product'];
            }
            if (!$products) {
                return array();
            }

            $product_features = $products;

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
                $type_values[$type] = $model->getValues('id', $value_ids);
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

        foreach ($items as &$item) {
            $item['product']['features'] = isset($product_features[$item['product_id']]['features']) ? $product_features[$item['product_id']]['features'] : array();
            $item['product']['feature_values'] = isset($product_features[$item['product_id']]['feature_values']) ? $product_features[$item['product_id']]['feature_values'] : array();
            if (isset($product_features[$item['product_id']]['selectable_features'])) {
                $item['product']['selectable_features'] = $product_features[$item['product_id']]['selectable_features'];
            }
        }

        return $features;
    }

    /**
     * Get sku stocks.
     *
     * @param array $products
     * @return array
     */
    public static function getSkuStocksCount($products)
    {
        static $stocks = null;
        if ($stocks === null) {
            // Определяем для каких товаров необходимо найти остатки
            $find = array();
            foreach ($products as &$item) {
                if ($item['id'] !== 0) {
                    $find[$item['sku_id']] = $item['sku_id'];
                }
            }
            unset($item);

            if ($find) {
                $stock_counts = shopDelpayfilterHelper::getStockCounts($find);
            }

            foreach ($products as $item) {
                if (isset($stock_counts[$item['sku_id']])) {
                    if (method_exists('shopHelper', 'fillVirtulStock') && is_callable(array('shopHelper', 'fillVirtulStock'))) {
                        $stocks[$item['sku_id']] = shopHelper::fillVirtulStock($stock_counts[$item['sku_id']]);
                    } else {
                        $stocks[$item['sku_id']] = $stock_counts[$item['sku_id']];
                    }
                }
            }
        }
        return $stocks;
    }

    /**
     * Get user, if enabled mode of checking email or phone.
     * If user is unauthorized, and contact was found in DB, then user will be replaced by contact we have found
     *
     * @param array $rule
     * @return waAuthUser|waContact|waUser
     * @throws waException
     */
    protected static function getUser($rule)
    {
        $user = wa()->getUser();

        if (!$user->getId()) {

            $order_params = shopDelpayfilterData::getCurrentOrderParams();
            $user = !empty($order_params['contact']) ? $order_params['contact'] : $user;

            $instance = new self();

            $email = $phone = "";
            if (!empty($rule['check_email'])) {
                $email = $instance->getFirstData($user, 'email');
            }
            if (!empty($rule['check_phone'])) {
                $phone = $instance->getFirstData($user, 'phone');
            }

            $hash = ($email ? 'email=' . $email : '') . ($email && $phone ? '&' : '') . ($phone ? 'phone=' . $phone : '');

            if ($hash) {
                $collection = new waContactsCollection('/search/' . $hash);
                $contact_ids = array_keys($collection->getContacts());
                if ($contact_ids) {
                    $customer_model = new shopCustomerModel();
                    $customers = $customer_model->select('contact_id')->where('contact_id IN(i:ids)', array('ids' => $contact_ids))->fetchAll(null, true);
                    if ($customers) {
                        $user = new waContact(reset($customers));
                    }
                }
            }
        }

        return $user;
    }

    /**
     * Get first data from the contact field
     *
     * @param waContact|waAuthUser|waUser $user
     * @param string $field_id
     * @return string
     */
    protected function getFirstData($user, $field_id)
    {
        $data = $user->get($field_id);
        if (is_array($data)) {
            if (isset($data['value'])) {
                $value = $data['value'];
            } else {
                $first = reset($data);
                $value = ifset($first['value'], '');
            }
        } else {
            $value = $data;
        }
        return $value;
    }

    /**
     * Get user addresses
     *
     * @return array
     * @throws waException
     */
    protected static function getUserAddress()
    {
        $addresses = array();
        $contact = wao(new shopDelpayfilterHelper())->getContact();
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

            $instance = get_class();
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
                            $methods[$t][] = $value;
                        } else {
                            $methods[$t] = $t;
                        }
                    }
                } else {
                    $value = self::getTypeValue($type, self::$typesMethods[$type], $conditions);
                    if ($value !== $type) {
                        $methods[self::$typesMethods[$type]][] = $value;
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
            } elseif (in_array($type, array('num_prod', 'num_cat', 'num_cat_all', 'num_set', 'num_type', 'order_prod_cat', 'order_prod_cat_all', 'user_country', 'user_data', 'stocks'))) {
                return isset($params['field']) ? $params['field'] : '';
            }
        }
        return $type;
    }

    protected static function getAbstractProduct()
    {
        return array(0 => array("id" => 0, "sku_id" => 0, "product_id" => 0, "price" => 0, "compare_price" => 0, "purchase_price" => 0, "currency" => wa('shop')->getConfig()->getCurrency(false), "product" => array("id" => 0)));
    }

    /*     * *
     * Data methods
     * * */

    private static function getCategoryDataMethod()
    {
        // Категории товаров
        $scm = new shopCategoryModel();
        $categories = $scm->getTree(null);
        return shopDelpayfilterHelper::getCategoriesTree($categories);
    }

    private static function getSetDataMethod()
    {
        // Списки товаров
        $ssm = new shopSetModel();
        return $ssm->getAll('id');
    }

    private static function getTypeDataMethod()
    {
        // Типы товаров
        $stm = new shopTypeModel();
        return $stm->getTypes(true);
    }

    private static function getFeatureDataMethod()
    {
        // Значения характеристик товаров
        $sfm = new shopFeatureModel();
        return $sfm->getFeatures(true, null, 'id', true);
    }

    private static function getProductDataMethod($ids)
    {
        $hash = 'id/' . implode(",", $ids);
        $collection = new shopProductsCollection($hash);
        return $collection->getProducts("*", 0, $collection->count());
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

    private static function getUcatDataMethod()
    {
        // Категории контакта
        try {
            // Проверяем наличие плагина Контакты PRO
            wa('contacts')->getPlugin('pro');
            $view_model = new contactsViewModel();
            $contact_categories = $view_model->getAllViews(null, true);
            contactsViewModel::setIcons($contact_categories);
        } catch (Exception $ex) {
            $ccm = new waContactCategoryModel();
            $contact_categories = $ccm->getAll('id');
        }
        return $contact_categories;
    }

    private
    static function getServicesDataMethod()
    {
        $instance = new self();
        return $instance->getServicesData();
    }

    private static function getUserDataMethod($ids)
    {
        $hash = 'id/' . implode(",", $ids);
        $collection = new waContactsCollection($hash);
        return $collection->getContacts("*", 0, $collection->count());
    }

    private static function getShippingDataMethod()
    {
        $instance = new self();
        return $instance->getShippingData();
    }

    public function getShippingData()
    {
        // Плагины доставки
        $model = new shopPluginModel();
        $plugins = shopShipping::getList();
        $instances = $model->listPlugins(shopPluginModel::TYPE_SHIPPING);
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
        $instance = new self();
        return $instance->getPaymentData();
    }

    public function getPaymentData()
    {
        // Плагины оплаты
        $model = new shopPluginModel();
        $plugins = shopPayment::getList();
        $instances = $model->listPlugins(shopPluginModel::TYPE_PAYMENT);
        foreach ($instances as $k => $instance) {
            if (!isset($plugins[$instance['plugin']])) {
                unset($instances[$k]);
                continue;
            }
        }
        return $instances;
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
        foreach (wao(new waCountryModel())->all() as $k => $c) {
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
        $country_regions = wao(new waRegionModel())->getByCountry($countries);
        if ($country_regions) {
            foreach ($country_regions as $r) {
                $regions[$r['country_iso3']][$r['code']] = $r['name'];
            }
        }
        return $regions;
    }

    private static function getStorefrontDataMethod()
    {
        // Домены и их правила маршрутизации
        wa('site');
        $domain_model = new siteDomainModel();
        $domains = $domain_model->getAll('id');
        return $domains;
    }

    public function getOrderStatusData()
    {
        // Статусы заказов
        static $data = null;
        if ($data === null) {
            $order_status = wao(new shopWorkflow())->getAllStates();
            if ($order_status) {
                foreach ($order_status as $os) {
                    $data[] = array(
                        'id' => $os->id,
                        'name' => $os->name
                    );
                }
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
            $stocks = shopDelpayfilterHelper::getStocks();
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

    private static function getUserData2DataMethod()
    {
        $instance = new self();
        return $instance->getUserData();
    }

    /**
     * Get order params from shop/checkout. Keep in touch with Quickorder plugin
     *
     * @return array
     * @throws waException
     */
    public static function getCurrentOrderParams()
    {
        $params = wa()->getStorage()->get('shop/checkout', array());

        if (empty($params['shipping'])) {
            $params['shipping'] = array('id' => 0);
        }

        /* SS8 */
        if (isset($params['order'])) {
            $params = array_merge($params['order']);

            $cache = new waRuntimeCache('delpayfilter_checkout_params');
            if ($cache->isCached()) {
                $checkout_params = $cache->get();

                // Преобразуем метод доставки к нужному виду
                if (!empty($checkout_params['shipping']['variant_id'])) {
                    $parts = explode('.', $checkout_params['shipping']['variant_id']);
                    $params['shipping']['id'] = $parts[0];
                    $params['shipping']['rate_id'] = $parts[1];
                }
                if (!empty($checkout_params['payment'])) {
                    $params['payment'] = $checkout_params['payment'];
                }
                $params = array_merge($params, $checkout_params);
            }
        }
        $params['contact'] = wao(new shopDelpayfilterHelper())->getContact();

        if ($shipping_id = waRequest::post('shipping_id')) {
            $params['shipping']['id'] = $shipping_id;
        }
        if ($payment_id = waRequest::post('payment_id')) {
            $params['payment'] = $payment_id;
        }

        // Учитываем плагин "Купить в 1 клик" (quickorder)
        if (waRequest::param('plugin', '') == 'quickorder') {
            $quickorder_cart = new shopQuickorderPluginCart(waRequest::post('qformtype'));
            $params['quickorder_cart'] = $quickorder_cart;
            // Доставка
            $shipping = $quickorder_cart->getStorage()->getSessionData('shipping');
            if ($shipping) {
                $params['shipping'] = $shipping;
            }
            // Оплата
            $payment = $quickorder_cart->getData('payment_id');
            if ($payment) {
                $params['payment'] = $payment;
            }
            // Контакт
            $contact = $quickorder_cart->getContact();
            if ($contact) {
                $params['contact'] = $contact;
            }
            // Купон
            $coupon_code = $quickorder_cart->getData('coupon');
            if ($coupon_code) {
                $params['coupon_code'] = $coupon_code;
                $params['flexdiscount-coupon'] = '';
            }
        }

        return $params;
    }
}

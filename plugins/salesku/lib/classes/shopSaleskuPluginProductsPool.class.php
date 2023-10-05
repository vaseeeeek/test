<?php
/**
 * По сути этот класс теперь shopSaleskuPluginProductsDataStorage и заодно пул
 */
class shopSaleskuPluginProductsPool {
    /**
     * @var shopSaleskuPluginProductsPool
     */
    protected static $_instance = null;

    /**
     * Объекты декораторов продуктов
     * @var array shopSaleskuPluginProductDecorator
     */
    protected $products = array();

    /**
     * Основной массив для хранения всех получаемых данных продуктов с разной структурой
     * @var array
     */
    protected $products_data = null;

    /**
     * Уникальные идентификаторы объектов shopSaleskuPluginProduct
     * используется для распознавания при инициализации продукта на витрине
     * @var array
     */
    protected static $uids = array();

    /**
     * Хранение данных продуктов из хука frontend_products
     * @var array
     */
    protected  $frontend_products = array();

    /**
     * Общее количество запрошенных продуктов
     * @var int
     */
    protected $count_products = 0;

    /**
     * Идентификаторы продуктов с выбиравемыми характеристиками
     * @var array
     */
    protected  $products_sku_selectable = array();

    /**
     * Все типы продуктов
     * @var array
     */
    protected  $product_types = array();

    /**
     * Типы продуктов с выбираемыми характеристиками
     * @var array
     */
    protected $product_types_selectable = array();

    /**
     * Склады магазина
     * @var array
     */
    protected static $stocks = null;

    /**
     * Методы для получения данных, можно было сделать по ключу, но так более ясно)
     * @var array
     */
    protected static $methods = array(
        'images' => 'setImages',
        'skus' => 'setSkus',
        'sku_features' => 'setSkuFeatures',
        'features_selectable' => 'setFeaturesSelectable',
        'services' => 'setServices'
    );
    protected  $custom_pools = array();

    /**
     * Генерирует уникальный идентификатор для объекта shopSaleskuPluginProduct
     * @return int
     */
    public static function getUid()
    {
        $uid = rand(11111, 99999);
        if (!array_key_exists($uid, self::$uids)) {
            self::$uids[$uid] = true;
            return $uid;
        } else {
            return self::getUid();
        }
    }

    /**
     * @return shopSaleskuPluginProductsPool
     */
    public static function getInstance()
    {
        if (self::$_instance == null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public static function getPool()
    {
       return self::getInstance();
    }

    /**
     * Возвращает объект декоратора продукта
     * @param array $data
     * @return null|shopSaleskuPluginProductDecorator
     */
    public  function getProduct($data = array())
    {
        if (isset($data['id'])) {
            if ($this->products_data == null) {
                /* При первом запросе их хелпера в шаблоне, инициалищируем данные продуктов */
                $this->prepareProductsData();
            }

            if (!isset($this->products[$data['id']])) {
                // Если в хуке не было продукта(сеты и списки в шаблонах), добавляем его все данные
                if(!array_key_exists($data['id'], $this->frontend_products)) {
                    $this->addProducts(array($data['id'] => $data));
                    if(isset($this->custom_pools[$data['id']])) {
                        return $this->custom_pools[$data['id']]->getProduct($data);
                    }
                } else {

                }
                $this->products[$data['id']] = new shopSaleskuPluginProductDecorator($data, true);
            }

            return $this->products[$data['id']];
        }
        return null;
    }
    
    /**
     * @param $name
     * @param $value
     */
    protected function setProductsData($name, $value)
    {
        if(!array_key_exists($name, $this->products_data)) {
            $this->products_data[$name] = array();
        }
        if($name == 'features_selectable' || $name == 'services')  {
            foreach ($value as $k => $v) {
                if(!array_key_exists($k, $this->products_data[$name])) {
                    $this->products_data[$name][$k] = array();
                }
                if(is_array($v)) {
                    $this->products_data[$name][$k] = $this->products_data[$name][$k]+$v;
                }
            }
        } else {
            $this->products_data[$name] = $this->products_data[$name]+$value; /* TODO: Для features, надо мержить значения сортировки */
        }
    }

    /**
     * Глобальный метод для получения данных продуктов
     * @param null $key ключ в массиве данных
     * @param null $id идентификатор продукта
     * @return array
     */
    public  function getProductData($key = null, $id = null)
    {
        if($id > 0 && array_key_exists($id,   $this->custom_pools)) {
            return $this->custom_pools[$id]->getProductData($key, $id);
        }
        if (!empty($key)) {
            if($this->products_data === null) {
                $this->prepareProductsData();
            }
            if (!array_key_exists($key, $this->products_data) && array_key_exists($key, self::$methods)) {
                $method = self::$methods[$key];
                $this->$method();
            }
            if (!is_null($id)) {
                /* Для данных распределенных по ключам продуктов */
                if (array_key_exists($key, $this->products_data)) {
                    $data = $this->products_data[$key];
                    if (array_key_exists($id, $data)) {
                        return $data[$id];
                    }elseif($data){
                        return $data;
                    }
                }
            } else {
                if (array_key_exists($key, $this->products_data)) {
                    return $this->products_data[$key];
                }
            }
            return array();
        }
    }

    /**
     * Добавляет продукты из хука frontend_products
     * @uses shopSaleskuPlugin::frontendProducts()
     * @param $products
     */
    public function addProducts($products)
    {
        if(is_array($products) && !empty($products)) {
            $products_pool = null;
            if ($this->products_data !== null) {
                $products_pool = new self();
                $products_pool->addProducts($products);
            }
            foreach ($products as $k => $v) {
                $id = null;
                if((is_array($v) && array_key_exists('id', $v)) || ($v instanceof shopProduct)) {
                    $id = $v['id'];
                } elseif(wa_is_int($v)) {
                    $id = $v;
                }
                if ($this->products_data !== null) {
                    if(!isset($this->custom_pools[$id])) {
                        $this->custom_pools[$id] = $products_pool;
                    }
                } else {
                    if($id) {
                        $this->frontend_products[$id] = array('id' => $id);
                    } else {
                        if(wa()->getConfig()->isDebug()) {
                            waLog::dump($products);
                        }
                    }
                }
            }
        }
    }

    /**
     * Подготовка основных данных продуктов
     */
    public function prepareProductsData()  {
        $this->products_data = array();
        $this->count_products = count($this->frontend_products);
        $ids = array_keys($this->frontend_products);
        $model = new shopProductModel();
        $products_data = $model->getById($ids);
        foreach ($this->frontend_products as $id => $data)  {
            if(is_array($data)) {
                $data = array_merge($data, $products_data[$id]);
                $this->frontend_products[$id] = $data;
                if ($data['sku_type'] == shopProductModel::SKU_TYPE_SELECTABLE) {
                    $this->products_sku_selectable[$id] = $id;
                    $this->product_types_selectable[$data['type_id']] = $data['type_id'];
                }
                $this->product_types[$data['type_id']] = $data['type_id'];
            } elseif (wa()->getConfig()->isDebug()) {
               waLog::dump($data);
            }
        }
        /* Предварительно готовим все артикулы, обрабатывая через хук */
        $this->setSkus();
    }

    /**
     * Получает все картинки продуктов определеного  размера
     * @param null|array $products
     * @return array
     */
    protected function setImages($products = null) {
        if($products == null) {
            $products = $this->frontend_products;
        }
        $settings = shopSaleskuPlugin::getPluginSettings();
        /* Получаем настройку размера */
        $sku_image_size = $settings['sku_image_size'];
        if (!empty($sku_image_size) && is_string($sku_image_size)) {
            $images_size = array('category' => $sku_image_size);
        } else {
            $images_size = array('category' => wa('shop')->getConfig()->getImageSize('crop'));
        }
        /* получаем картинки выбранным размером */
        $product_images_model = new shopProductImagesModel();
        $product_images = $product_images_model->getImages(array_keys($products), $images_size, 'id');
        $this->setProductsData('images', $product_images);
    }

    /**
     * Получает все артикулы продуктов
     * @param null|array $products
     * @return array
     */
    protected function setSkus($products = null) {
        $custom_products = true;
        if($products == null) {
            $custom_products = false;
            $products = $this->frontend_products;
        }

        $product_skus_model = new shopProductSkusModel();
        $skus = $product_skus_model->getDataByProductId(array_keys($products));

        $settings = shopSaleskuPlugin::getPluginSettings();
        if(isset($settings['frontend_products']) && $settings['frontend_products'] == 1) {
            /* Прогоняем по всем плагинам скидок и мультицен */
            $event_params = array(
                'products' => $products,
                'skus' => &$skus,
                'plugin' => shopSaleskuPlugin::PLUGIN_ID
            );
            wa('shop')->event('frontend_products', $event_params);
        }

        /* Для переданного списка принудительно готовим данные картинок */
        if($custom_products) {
            $this->setImages($products);
        }
        /* Дополняем адресом картинки, конвертируем валюту и разделяем по продукту */
        $products_skus = array();
        $images = $this->getProductData('images');
        // Convert $skus
        $config = wa('shop')->getConfig();
        $curs = $config->getCurrencies();
        $frontend_currency = $config->getCurrency(false);
        foreach ($skus as &$sku) {
            if(isset($sku['image_id']) && intval($sku['image_id'])>0 && array_key_exists($sku['image_id'],$images)) {
                $sku['image'] = $images[$sku['image_id']]['url_category'];
            }
            $product = ifset($products[$sku['product_id']]);
            $product_currency = ifset($product['unconverted_currency'], ifset($product['currency']));
            if (!$product_currency || !isset($curs[$product_currency]) || isset($sku['unconverted_currency'])) {
                continue;
            }
            $convert_currency = $product_currency != $frontend_currency && !empty($curs[$frontend_currency]['rounding']) && !empty($curs[$product_currency]);
            $sku['currency'] = $sku['unconverted_currency'] = $product_currency;
            if ($convert_currency) {
                $sku['currency'] = $frontend_currency;
            }
            foreach (array('price', 'compare_price') as $k) {
                if (!isset($sku[$k])) {
                    continue; // does not break on partly loaded data
                }
                $sku['frontend_'.$k] = $sku[$k];
                $sku['unconverted_'.$k] = $sku[$k];
                if ($sku[$k] > 0 && !empty($curs[$product_currency])) {
                    $sku['frontend_'.$k] = shop_currency($sku[$k], $product_currency, $frontend_currency, false);
                    if ($convert_currency) {
                        $sku[$k] = $sku['frontend_'.$k] = shopRounding::roundCurrency($sku['frontend_'.$k], $frontend_currency);
                    }
                }
            }
            $products_skus[$sku['product_id']][$sku['id']] = $sku;
        }
        unset($sku);
        $this->setProductsData('skus', $products_skus);
    }

    /**
     * Получает все характеристики артикулов
     * @param null|array $products
     * @return array
     */
    protected function setSkuFeatures($products = null) {
        if($products == null) {
            $products_sku_selectable = $this->products_sku_selectable;
        } else {
            $products_sku_selectable = array();
            foreach ($products as $v) {
                if ($v['sku_type'] == shopProductModel::SKU_TYPE_SELECTABLE) {
                    $products_sku_selectable[$v['id']] = $v['id'];
                }
            }
        }
        if(!empty($products_sku_selectable)) {
            $product_features_model = new shopProductFeaturesModel();
            $sql = "SELECT * FROM " . $product_features_model->getTableName() . " WHERE product_id in(" . implode(',', $products_sku_selectable) . ") AND sku_id IS NOT NULL";

            $rows = $product_features_model->query($sql)->fetchAll();
        } else {
            $this->setProductsData('sku_features', array());
            return;
        }

        $result = array();
        foreach ($rows as $row) {
            $row = array_map('intval', $row);
            $result[$row['product_id']][$row['sku_id']][$row['feature_id']] = $row['feature_value_id'];
        }
        foreach ($result as &$product) {
            foreach ($product as &$sku) {
                ksort($sku, SORT_NUMERIC);
                unset($sku);
            }
        }
        $this->setProductsData('sku_features', $result);
    }

    /**
     * Получает все выбираемые характеристики продуктов
     * @param null|array $products
     * @return array
     */
    protected function setFeaturesSelectable($products = null) {
        if($products == null) {
            $products_sku_selectable  = $this->products_sku_selectable;
            $product_types_selectable = $this->product_types_selectable;
        } else {
            $products_sku_selectable = array();
            $product_types_selectable = array();
            foreach ($products as $v) {
                if ($v['sku_type'] == shopProductModel::SKU_TYPE_SELECTABLE) {
                    $products_sku_selectable[$v['id']] = $v['id'];
                    $product_types_selectable[$v['type_id']] = $v['type_id'];
                }
            }
        }


        $features_selectable = array();
        $products_features_selectable = array();

        $features_selectable_model = new shopProductFeaturesSelectableModel();
        foreach ($features_selectable_model->getByField('product_id', $products_sku_selectable, true) as $row) {
            $row = array_map('intval', $row);
            $features_selectable[$row['feature_id']][$row['value_id']] = $row['value_id'];
            $products_features_selectable[$row['product_id']][$row['feature_id']][$row['value_id']] = $row['value_id'];
        }
        ksort($features_selectable, SORT_NUMERIC);

        $feature_model = new shopFeatureModel();
        $features = $feature_model->getById(array_keys($features_selectable));

        $type_features_model = new shopTypeFeaturesModel();
        $type_features_model->fillTypes($features, $product_types_selectable);
        /* attach values */
        $features = $feature_model->getValues($features);
        /* Переопределяем данные характеристик */
        $features_settings = shopSaleskuPlugin::getPluginSettings()->getFeaturesSettings();
        foreach ($features as &$v) {
            $feature_settings = $features_settings[$v['id']];
            $v['view_type'] = $features_settings->getDefault('view_type');
            if (isset($feature_settings['view_type'])) {
                $v['view_type'] = $feature_settings['view_type'];
            }
            $v['view_name'] = $v['name'];
            if (isset($feature_settings['view_name']) && !empty($feature_settings['view_name'])) {
                $v['name'] = $feature_settings['view_name'];
            }
            $v['view_name_hide'] = $features_settings->getDefault('view_name_hide');
            if (isset($feature_settings['view_name_hide'])) {
                $v['view_name_hide'] = $feature_settings['view_name_hide'];
                if (!empty($v['view_name_hide'])) {
                    $v['name'] = '';
                }
            }
        }
        unset($v);
        $this->setProductsData('features_selectable', array(
            'features' => $features,
            'products_features' => $products_features_selectable
        ));
    }

    /**
     * Формирование данных сервисов типов товаров, продуктов, и артикулов
     * @param null|array $products
     */
    protected function setServices($products = null) {
        $custom_products = false;
        if($products == null) {
            $product_types = $this->product_types;
            $products = $this->frontend_products;
        } else {
            $custom_products = true;
            $product_types = array();
            foreach ($products as $v) {
                if ($v['sku_type'] == shopProductModel::SKU_TYPE_SELECTABLE) {
                    $product_types[$v['type_id']] = $v['type_id'];
                }
            }
        }
        $services_ids = array();
        $type_services_model = new shopTypeServicesModel();
        $types_services = $type_services_model->getByField('type_id', array_keys($product_types), true);
        $type_services = array();
        foreach ($types_services as $v) {
            $type_services[$v['type_id']][$v['service_id']] = true;
            $services_ids[$v['service_id']] = $v['service_id'];
        }

        $product_services_model = new shopProductServicesModel();
        $product_ids =  array_keys($products);
        if(!empty($product_ids)) {
            $products_services_ids = $product_services_model->select('DISTINCT service_id')
                ->where('product_id IN (' . implode(",", $product_ids) . ')')->fetchAll();
            foreach ($products_services_ids as $row) {
                if (!array_key_exists($row['service_id'], $services_ids)) {
                    $services_ids[$row['service_id']] = $row['service_id'];
                }
            }
        }


        /* Fetch services */
        $service_model = new shopServiceModel();
        $services = $service_model->getById($services_ids);
        shopRounding::roundServices($services);

        /* Convert service.price from default currency to service.currency */
        foreach ($services as &$s) {
            $s['price'] = shop_currency($s['price'], null, $s['currency'], false);
        }
        unset($s);

        /* Fetch service variants */
        $variants_model = new shopServiceVariantsModel();
        $rows = $variants_model->getByField('service_id', $services_ids, true);
        shopRounding::roundServiceVariants($rows, $services);
        foreach ($rows as $row) {
            if (!$row['price']) {
                $row['price'] = $services[$row['service_id']]['price'];
            } elseif ($services[$row['service_id']]['variant_id'] == $row['id']) {
                $services[$row['service_id']]['price'] = $row['price'];
            }
            $services[$row['service_id']]['variants'][$row['id']] = $row;
        }

        /* Fetch service prices for specific products and skus */
        $rows = $product_services_model->getByField('product_id', array_keys($products), true);
        shopRounding::roundServiceVariants($rows, $services);

        $skus_services = array();
        $skus = array();

        $products_skus = $this->getProductData('skus');
        if($custom_products) {
            $diff = array_diff(array_keys($products), array_keys($products_skus));
            if(!empty($diff)) {
                $this->setSkus($products);
            }
            $products_skus = array();
            foreach (array_keys($products) as $id) {
                $products_skus[$id] = $this->getProductData('skus', $id);
            }
        }
        foreach ($products_skus as $product => $_skus) {
            foreach ($_skus as $sku) {
                $skus[$sku['id']] = $sku;
                $skus_services[$sku['product_id']][$sku['id']] = array();
            }
        }
        $frontend_currency = wa('shop')->getConfig()->getCurrency(false);
        $products_services = array();
        foreach ($rows as $row) {
            if (!$row['sku_id']) {
                /* Дальнейшая обработка будет в классе продукта */
                $products_services[$row['product_id']][] = $row;
            } else {
                if (!$row['status']) {
                    $skus_services[$row['product_id']][$row['sku_id']][$row['service_id']][$row['service_variant_id']] = false;
                } else {
                    $skus_services[$row['product_id']][$row['sku_id']][$row['service_id']][$row['service_variant_id']] = $row['price'];
                }
            }
        }
        /* Fill in gaps in $skus_services */
        foreach ($skus_services as $product_id => &$product_skus_services) {
            /* Получаем валюту продукта */
            if (array_key_exists($product_id, $products) && array_key_exists('currency', $products[$sku['product_id']])) {
                $product_currency = $products[$product_id]['currency'];
            } else {
                $product_currency = $frontend_currency;
            }

            foreach ($product_skus_services as $sku_id => &$sku_services) {
                $sku_price = $skus[$sku_id]['price'];
                foreach ($services as $service_id => $service) {
                    if (isset($sku_services[$service_id])) {
                        if ($sku_services[$service_id]) {
                            foreach ($service['variants'] as $v) {
                                if (!isset($sku_services[$service_id][$v['id']]) || $sku_services[$service_id][$v['id']] === null) {
                                    $sku_services[$service_id][$v['id']] = array($v['name'], $this->getServicePrice($v['price'], $service['currency'], $sku_price, $product_currency));
                                } elseif ($sku_services[$service_id][$v['id']]) {
                                    $sku_services[$service_id][$v['id']] = array($v['name'], $this->getServicePrice($sku_services[$service_id][$v['id']], $service['currency'], $sku_price, $product_currency));
                                }
                            }
                        }
                    } else {
                        foreach ($service['variants'] as $v) {
                            $sku_services[$service_id][$v['id']] = array($v['name'], $this->getServicePrice($v['price'], $service['currency'], $sku_price, $product_currency));
                        }
                    }
                }
            }
            unset($sku_services);
            /// TODO: $skus_services[$product_id] = $product_skus_services;
        }
        foreach ($skus_services as $product_id => $product_skus_services) {
            /* disable service if all variants are disabled */
            foreach ($product_skus_services as $sku_id => $sku_services) {
                foreach ($sku_services as $service_id => $service) {
                    if (is_array($service)) {
                        $disabled = true;
                        foreach ($service as $v) {
                            if ($v !== false) {
                                $disabled = false;
                                break;
                            }
                        }
                        if ($disabled) {
                            $skus_services[$product_id][$sku_id][$service_id] = false;
                        }
                    }
                }
            }
        }
        $this->setProductsData('services', array(
            'services' => $services,
            'type' => $type_services,
            'products' => $products_services,
            'skus' => $skus_services
        ));
    }

    /**
     * Расчитывет стоимость сервиса для артикула
     * @param $price
     * @param $currency
     * @param $product_price
     * @param $product_currency
     * @return mixed|string
     */
    protected function getServicePrice($price, $currency, $product_price, $product_currency) {
        if ($currency == '%') {
            return shop_currency($price * $product_price / 100, $product_currency, null, 0);
        } else {
            return shop_currency($price, $currency, null, 0);
        }
    }

    /**
     * Возвращает склады магазина
     * @return array
     */
    public static function getStocks()
    {
        if (self::$stocks == null) {
            self::$stocks = array();
            if (method_exists('shopHelper', 'getStocks')) {
                self::$stocks = shopHelper::getStocks(true);
            } else {
                /* Для старой версии, проверял на 6.3 */
                $stock_model = new shopStockModel();
                self::$stocks = $stock_model->getAll('id');
            }
        }
        return self::$stocks;
    }

}
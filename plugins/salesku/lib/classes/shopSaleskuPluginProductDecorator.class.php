<?php
class shopSaleskuPluginProductDecorator implements ArrayAccess {

    protected $product = null;
    protected $methods = array(
        'currency_info' => 'getCurrencyInfo',
        'sku_services' => 'getServicesVars',
        'services' => 'getServicesVars',
        'skus' => 'getSkus',
        'sku_features_selectable' => 'getSkuFeatures',
        'sku_features' => 'getSkuFeatures',
        'features_selectable' => 'getFeaturesSelectable',
        'selectable_features_control' => 'getSelectableFeaturesControl',
        'stocks' => 'getStocks',
        'sku_id' => 'getSkuId'
    );
    protected $data = array(
        'currency_info' => null,
        'sku_services' => null,
        'services' => null,
        'skus' => null,
        'sku_features_selectable' => null,
        'sku_features' => null,
        'features_selectable' => null,
        'selectable_features_control' => null,
        'stocks' => null,
        'sku_id' => null,
        'skus_view_type' => null,
    );

    public function __construct($data = array(), $is_frontend = true)
    {
        if($data instanceof shopProduct) {
            $this->product = $data;
        } elseif(is_array($data) && !empty($data['id'])) {
            $this->product = new shopProduct($data, $is_frontend);
        }
        $settings = shopSaleskuPlugin::getPluginSettings();
        $this->setData('skus_view_type', $settings['skus_view_type']);
    }
    /* Все данные проходят через этот метод */
    protected function getData($name) {
        if(array_key_exists($name, $this->data)) {
            if(is_null($this->data[$name])) {
                $method = $this->methods[$name];
                $this->$method();
            }
            return $this->data[$name];
        } elseif(isset($this->product[$name])) {
            return $this->product[$name];
        } else {
            return null;
        }
    }
    public function getSkuId() {
        $product = $this->getProduct();
        $this->setData('sku_id', $product['sku_id']);
        $settings = shopSaleskuPlugin::getPluginSettings();
        if(isset($settings['available_sku']) && $settings['available_sku']=='1' && $this->getData('status')) {
            $skus = $this->getData('skus');
            if(array_key_exists($product['sku_id'], $skus) && $this->isAvailableSku($skus[$product['sku_id']])) {
               return;
            }
            foreach ($skus as $_sku) {
               if($this->isAvailableSku($_sku)) {
                  $this->setData('sku_id', $_sku['id']);
                  break;
               }
            }
        } 
    }
    public function isAvailableSku($sku_data) {
        $sku = false;
        if (is_array($sku_data) && array_key_exists('available', $sku_data)) {
            $sku = $sku_data;
        } elseif(!is_array($sku_data) && is_numeric($sku_data)) {
            $skus = $this->getData('skus');
            if(isset($skus[$sku_data])) {
                $sku = $skus[$sku_data];
            }
        }
        if($sku && $sku['available'] && ($this->getConfig()->getGeneralSettings('ignore_stock_count') || $sku['count'] === null || $sku['count'] > 0)) {
           return true;
        }
        return false;
    }
    /* 
    * Основные методы получения дополнительных данных продукта 
    */
    /* Склады */
    protected function getStocks() {
       $this->setData('stocks',shopSaleskuPluginProductsPool::getStocks());
    }
    /* Информация о сервисах продукта */
    protected function getServicesVars() {
        $global_services = shopSaleskuPluginProductsPool::getPool()->getProductData('services',$this->getData('id'));
        $all_services = $global_services['services'];
        $services = array();
        // Получаем сервисы типа товаров
        if(array_key_exists($this->getData('type_id'), $global_services['type'])) {
            $types_services = $global_services['type'][$this->getData('type_id')];
            foreach ($types_services as $id => $v) {
                if(array_key_exists($id,$all_services)) {
                    $services[$id] = $all_services[$id];
                }
            }
        }
        // Получаем и устанавливаем настройки сервисов самого продукта
        if(array_key_exists($this->getData('id'), $global_services['products'])) {
            $rows = $global_services['products'][$this->getData('id')];
            foreach ($rows as $row) {
                if(!array_key_exists($row['service_id'], $services)) {
                    if(array_key_exists($row['service_id'],$all_services)) {
                        $services[$row['service_id']] = $all_services[$row['service_id']];
                    } else {
                        continue;
                    }
                }
                if (!$row['status']) {
                    // remove disabled services and variants
                    unset($services[$row['service_id']]['variants'][$row['service_variant_id']]);
                } elseif ($row['price'] !== null) {
                    // update price for service variant, when it is specified for this product
                    $services[$row['service_id']]['variants'][$row['service_variant_id']]['price'] = $row['price'];
                    // !!! also set other keys related to price
                }
                if ($row['status'] == shopProductServicesModel::STATUS_DEFAULT) {
                    // default variant is different for this product
                    $services[$row['service_id']]['variant_id'] = $row['service_variant_id'];
                }
            }
        }

        $skus_services = array();
        if(array_key_exists($this->getData('id'), $global_services['skus'])) {
            $skus_services = $global_services['skus'][$this->getData('id')];
        }
        // Calculate prices for %-based services,
        // and disable variants selector when there's only one value available.
        $skus = $this->getData('skus');
        foreach ($services as $s_id => &$s) {
            if (!$s['variants']) {
                unset($services[$s_id]);
                continue;
            }
            $default_sku = $skus[$this->getData('sku_id')];
            if ($s['currency'] == '%') {
                foreach ($s['variants'] as $v_id => $v) {
                    $s['variants'][$v_id]['price'] = $v['price'] * $default_sku['price'] / 100;
                }
                $s['currency'] = $this->getData('currency');
            }

            if (count($s['variants']) == 1) {
                $v = reset($s['variants']);
                if ($v['name']) {
                    $s['name'] .= ' '.$v['name'];
                }
                $s['variant_id'] = $v['id'];
                $s['price'] = $v['price'];
                unset($s['variants']);
                foreach ($skus_services as $sku_id => $sku_services) {
                    if (isset($sku_services[$s_id]) && isset($sku_services[$s_id][$v['id']])) {
                        $skus_services[$sku_id][$s_id] = $sku_services[$s_id][$v['id']][1];
                    }
                }
            }
        }
        unset($s);

        uasort($services, array('shopServiceModel', 'sortServices'));

        $this->setData('sku_services', $skus_services);
        $this->setData('services', $services);
    }
    /* Информация о характеристиках артикулов */
    protected function getSkuFeatures() {
        if ($this->getData('sku_type') == shopProductModel::SKU_TYPE_SELECTABLE) {
            $features_selectable = $this->getData('features_selectable');
            $sku_features = shopSaleskuPluginProductsPool::getPool()->getProductData('sku_features', $this->getData('id'));
            $sku_selectable = array();
            $skus = $this->getData('skus');
            $currency = $this->getData('currency');

            foreach ($sku_features as $sku_id => $sf) {
                if (!isset($skus[$sku_id])) {
                    continue;
                }
                $sku_f = "";

                foreach ($features_selectable as $f_id => $f) {
                    if (isset($sf[$f_id])) {
                        $sku_f .= $f_id.":".$sf[$f_id].";";
                    }
                }
                $sku = $skus[$sku_id];
                $sku['image_id'] = (int)$sku['image_id'];
                $sku_selectable[$sku_f] = array(
                    'id'        => $sku_id,
                    'price'     => (float)shop_currency($sku['price'], $currency, null, false),
                    'available' => $sku['sku_available'],
                    'image_id'  =>  $sku['image_id']
                );
                // Дополняем данные адресом картинки артикула
                if(isset($sku['image']) && !empty($sku['image'])) {
                    $sku_selectable[$sku_f]['image'] = $sku['image'];
                }
                if ($sku['compare_price']) {
                    $sku_selectable[$sku_f]['compare_price'] = (float)shop_currency($sku['compare_price'], $currency, null, false);
                }
            }
            $this->setData('sku_features_selectable',$sku_selectable);
            $this->setData('sku_features', ifset($sku_features[$this->getData('sku_id')], array()));
        }
    }
    /* Данные выбираемых характеристик */
    protected function getFeaturesSelectable() {
        $features_data = shopSaleskuPluginProductsPool::getPool()->getProductData('features_selectable',$this->getData('id'));
        $features = array();
        if(isset($features_data['products_features']) && array_key_exists($this->getData('id'),$features_data['products_features'])) {
            $selected = $features_data['products_features'][$this->getData('id')];
        } else {
            $selected = array();
        }
      
        foreach ($features_data['features'] as $feature) {
           if(array_key_exists($this->getData('type_id'), $feature['types']) && array_key_exists($feature['id'],$selected)) {
               $feature['sort'] = ifset($feature['sort'][$this->getData('type_id')]);
               $features[$feature['id']] = $feature;
           }
        }
        uasort($features, function($a,$b) {
            return max(-1,min(1,$a["sort"]-$b["sort"]));
        });
      
        foreach ($features as &$f) {
            $count = 0;
            foreach ($f['values'] as $id => &$v) {
                if(isset($selected[$f['id']][$id])) {
                    $count += 1;
                } else {
                    unset($f['values'][$id]);
                }
            }
            $f['selected'] = $count;
            unset($f);
        }

        $this->setData('features_selectable', $features);
        return $features;
    }
    /* Данные артикулов не по характеристикам */
    protected function getSkus() {
        $skus = shopSaleskuPluginProductsPool::getPool()->getProductData('skus', $this->getData('id'));
        $return = array();
        if(is_array($skus) && !empty($skus)) {
            foreach ($skus as &$sku) {
                // Дополнительные данные доступности
                $sku['sku_available'] = false;
                if($this->getData('status') && $this->isAvailableSku($sku)) {
                    $sku['sku_available'] = true;
                }
                $return[$sku['id']] = $sku;
            }
            unset($sku);
        }
        $this->setData('skus', $return);
    }
    /* Объект продукта */
    private function getProduct() {
        return $this->product;
    }
    public function isAction($action = '') {
        $product_types_settings = shopSaleskuPlugin::getPluginSettings()->getProductTypeSettings();
        $return = false;
        if(waRequest::isMobile()) {
            if(empty($product_types_settings[$this->getData('type_id')]['status_mobile_off'])) {
                $return = true;
            }
        } elseif(empty($product_types_settings[$this->getData('type_id')]['status_off'])) {
            $return =  true;
        }
        if($return && !empty($action)) {
            $action_setting = array(
                'stocks' => 'hide_stocks',
                'services' => 'hide_services'
            );
            if(array_key_exists($action,$action_setting)
                && !empty($product_types_settings[$this->getData('type_id')][$action_setting[$action]])) {
                $return = false;
            }
        }
        return $return;
    }

    /* 
    * Дополнительные методы для получения данных продукта
    */
    protected function getConfig() {
        return shopSaleskuPluginSettings::getAppConfig();
    }
    protected function getCurrencyInfo()
    {
        $currency = waCurrency::getInfo($this->getConfig()->getCurrency(false));
        $locale = waLocale::getInfo(wa()->getLocale());
        $this->setData('currency_info',array(
            'code'          => $currency['code'],
            'sign'          => $currency['sign'],
            'sign_html'     => !empty($currency['sign_html']) ? $currency['sign_html'] : $currency['sign'],
            'sign_position' => isset($currency['sign_position']) ? $currency['sign_position'] : 1,
            'sign_delim'    => isset($currency['sign_delim']) ? $currency['sign_delim'] : ' ',
            'decimal_point' => $locale['decimal_point'],
            'frac_digits'   => $locale['frac_digits'],
            'thousands_sep' => $locale['thousands_sep'],
        ));
    }
    protected function getPrice($price, $currency, $product_price, $product_currency) {
        if ($currency == '%') {
            return shop_currency($price * $product_price / 100, $product_currency, null, 0);
        } else {
            return shop_currency($price, $currency, null, 0);
        }
    }
    /* Доступ как к массиву */
    public function offsetExists($offset) {
        return (isset($this->data[$offset]) || isset($this->product[$offset]));
    }
    public function offsetGet($offset) {
        return $this->getData($offset);
    }
    public function offsetSet($offset, $value) {
        $this->setData($offset, $value);
    }
    public function offsetUnset($offset) {
        return true; // не надо ничего удалять!
    }

    public function __get($name) {
        return $this->getData($name);
    }
    public function __set($name, $value) {
       // Спасибо, не надо ничего записывать
    }

    public function __call($name, $args) {
        if (method_exists($this->product, $name)) {
            return call_user_func_array(array($this->product, $name), $args);
        }
        return null;

    }

    protected function setData($name, $value) {
        if(array_key_exists($name, $this->data)) {
            $this->data[$name] = $value;
        }
    }
}
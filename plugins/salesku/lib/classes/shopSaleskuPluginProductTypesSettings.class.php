<?php

class shopSaleskuPluginProductTypesSettings  extends shopSaleskuPluginSettingsAbstract {
    protected $default_settings =  array(
        'related_sku'      => '1', // настройка связанных артикулов
        'status_off'       => '0', // выключение плагина
        'status_mobile_off'=> '0', // выключение плагина для мобильников
        'hide_stocks'      => '0', // скрыть склады
        'hide_services'    => '0', // скрыть сервисы
    );
    protected $model_class_name = 'shopSaleskuPluginProductTypeSettingsModel';
    protected static $product_types = null;

    public function getProductTypes() {
        if(self::$product_types == null) {
            $type_model = new shopTypeModel();
            $types = $type_model->getTypes();
            if(is_array($types) && !empty($types)) {
                self::$product_types = $types;
            } else {
                self::$product_types = array();
            }
        }
        return self::$product_types;
    }
    public function save($data) {
        if(is_array($data)) {
            $this->getModel()->saveByStorefront($this->getStorefront()->getId(),  $data);
        }
    }

}
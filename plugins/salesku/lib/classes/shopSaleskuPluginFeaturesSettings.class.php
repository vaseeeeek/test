<?php

class shopSaleskuPluginFeaturesSettings  extends shopSaleskuPluginSettingsAbstract {
    protected $default_settings =  array(
        'view_type'      => 'select', // Вид показа выбираемой характеристики
        'view_name'      => '',       // Альтернативное название характеристики
        'view_name_hide' => 0         // Скрыть имя характеристики
    );
    protected $model_class_name = 'shopSaleskuPluginFeatureSettingsModel';
    public function getFeaturesSelectable(){
        $feature_model = new shopFeatureModel();
        $features = $feature_model->getFeatures('selectable', 1);
        return $features;
    }

    public function getData()
    {
        return $this->data;
    }
    public function save($data) {
        if(is_array($data)) {
                $this->getModel()->saveByStorefront($this->getStorefront()->getId(), $data);
        }
    }
    public function offsetGet($offset)
    {
        if($this->offsetExists($offset)) {
            return  $this->data[$offset];
        }
        return array();
    }

}
<?php

/**
 * Класс отвечает за настройки связанных артикулов
 * Class shopSaleskuPluginRelatedSkuSettings
 */
class shopSaleskuPluginRelatedSkuSettings  extends shopSaleskuPluginSettingsAbstract {

    /**
     * Глобальный объект настроек
     * @var null
     */
    protected $settings = null;
    /**
     * Настройки по умолчанию
     * @var array
     */
    protected $default_settings = array(
        'related_sku' => 1
    );

    /**
     * shopSaleskuPluginRelatedSkuSettings constructor.
     * @param null $settings
     */
    public function __construct($settings) {
        $this->settings = $settings;
    }

    /**
     * @param null $product_type_id
     * @return mixed
     */
    protected function getData($product_type_id = null) {
        $data = null;
        if($this->settings->getProductTypeSettings()->offsetExists($product_type_id)) {
            $data = $this->settings->getProductTypeSettings()->offsetGet($product_type_id);
        }
        if(is_array($data) && isset($data['related_sku'])) {
            return $data['related_sku'];
        }
        return $this->settings->getProductTypeSettings()->getDefault('related_sku');
    }

    /**
     * @return mixed
     */
    public function getProductTypes() {
        return $this->settings->getProductTypeSettings()->getProductTypes();
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetExists($offset) {
        return $this->settings->getProductTypeSettings()->offsetExists($offset);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)  {
        return $this->getData($offset);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value) {}

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset) {}

    /**
     * @param $data
     */
    public function save($data) {}
}
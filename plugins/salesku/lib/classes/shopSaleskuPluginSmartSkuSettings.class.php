<?php

/**
 * Class shopSaleskuPluginSmartSkuSettings
 */
class shopSaleskuPluginSmartSkuSettings  extends shopSaleskuPluginSettingsAbstract {

    /**
     * Объект глобальных настроек
     * @var null| shopSaleskuPluginSettings
     */
    protected $settings = null;
    /**
     * Массив настроек по умолчанию
     * @var array
     */
    protected $default_settings = array(
        'smart_sku'                         => 1, // Общая настройка
        'smart_sku_replace'                 => 0, // Менять ли артикул на доступный
        'smart_sku_hide_single_feature'     => 0, // Скрывать характеристику если всего один вариант выбора
        'smart_sku_hide_multi_feature'      => 0, // Скрывать характеристику если всего один вариант выбора при наличии нескольких характеристик
        'smart_sku_hide_not_available_type' => 1, // Тип скрытия характеристик недоступного артикула
        'smart_sku_hide_non_existent_type'  => 1, // Тип скрытия характеристик несуществующего артикула
        'smart_sku_hard_hide_type'          => 1, // Режим жесткого скрытия
        'smart_sku_hide_style'              => 0,  // Свои классы для  скрытия
        'smart_sku_class_grey'              => '', // Класс частичного скрытия
        'smart_sku_class_hide'	            => '', // Класс полного скрытия
    );

    /**
     * shopSaleskuPluginSmartSkuSettings constructor.
     * @param null $settings
     */
    public function __construct($settings)
    {
        $this->settings = $settings;
    }

    /**
     * Возвращает данные настройки по ее ключу
     * @param $offset
     * @return mixed
     */
    protected function getData($offset) {
        if(isset($this->default_settings[$offset]) && $this->offsetExists($offset)) {
            return $this->settings[$offset];
        }
    }

    /**
     * Возвращает массив настроек для текущей витрины
     * @param null $product_type_id - временно не используется, будет использоваться в след. версиях для более гибких настроек
     * @return array
     */
    public function getSettings($product_type_id = null) {
        $data = array();
        foreach ($this->default_settings as $k => $v) {
            $data[$k] = $this->settings[$k];
        }
        return $data;
    }

    /**
     * Возвращает все типы товаров магазина 
     * @return mixed
     */
    public function getProductTypes() {
        return $this->settings->getProductTypeSettings()->getProductTypes();
    }
    /* Стандартные методы интерфейса ArrayAccess */
    public function offsetExists($offset) {
        return $this->settings->offsetExists($offset);
    }

    public function offsetGet($offset)  {
        return $this->getData($offset);
    }

    public function offsetSet($offset, $value) {}

    public function offsetUnset($offset) {}

    /**
     * Метод будет использоваться для сохранения настроек, пока он используется как заглушка для предотвращения конфликтов общей структуры настроек
     *  @see  shopSaleskuPluginSettingsAbstract
     * @param $data
     */
    public function save($data) {}
}
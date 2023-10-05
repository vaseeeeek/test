<?php
abstract class shopSaleskuPluginSettingsAbstract implements ArrayAccess {
    /**
     * Объект глобальных настроек плагина для витрины
     * @var null
     */
    protected $settings = null;
    /**
     * Модель для работы вс данными объекта настроек
     * @var null
     */
    protected $model = null;
    /**
     * Данные настроек
     * @var array
     */
    protected $data = array();
    /**
     * Название класса модели настроек
     * @var string
     */
    protected $model_class_name = '';
    /**
     * Массив настроек по умолчанию
     * @var array
     */
    protected $default_settings =  array();
    /**
     * shopSaleskuPluginSettingsAbstract constructor.
     * @param null $settings
     */
    public function __construct($settings = null)  {
        $this->settings = $settings;
        $this->setSettings();
        $this->init();
    }

    /**
     * Метод инициализирует массив настроек для текущей витрины
     * @see $this->data
     */
    protected function setSettings() {
        $data = $this->getModel()->getByStorefront((string)$this->getStorefront()->getId());
        if(!empty($data)) {
            $this->data = $data;
        }
    }

    /**
     * Метод инициализации объекта, необязательный
     */
    protected function init(){}

    /**
     * Возвращает настройку по умолчанию или весь массив настроек
     * @param null $name
     * @return null
     */
    public function getDefault($name = null) {
        if(isset($this->default_settings[$name])) {
            return $this->default_settings[$name];
        } elseif ($name==null) {
            return $this->default_settings;
        }
        return null;
    }

    /**
     * Возвращает модель настроек объекта
     * @return null | shopSaleskuPluginSettingsAbstractModel use
     */
    public function getModel() {
        if($this->model == null) {
            $class_name = $this->model_class_name;
            $this->model = new $class_name();
        }
        return $this->model;
    }

    /**
     * Возвращает объект Витрины
     * @return mixed | shopSaleskuPluginStorefront
     */
    public function getStorefront() {
        return $this->settings->getStorefront();
    }

    /**
     * Метод сохранения настроек плагина
     * @param $data - массив ключей и значений настроек для витрины
     * @return mixed
     */
    abstract public function save($data);

    /* Стандартные методы интерфейса ArrayAccess */

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        if(array_key_exists($offset,$this->data)) {
            return true;
        }
        return false;
    }

    /**
     * @param mixed $offset
     * @return mixed|null
     */
    public function offsetGet($offset)
    {

        if($this->offsetExists($offset)) {
           return  $this->data[$offset];
        } elseif(array_key_exists($offset,$this->default_settings)) {
            return $this->default_settings[$offset];
        }
        return null;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

}
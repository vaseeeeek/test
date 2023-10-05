<?php
class shopSaleskuPluginProduct implements ArrayAccess {

    /**
     * Уникальный идентификатор объекта продукта
     * @var int
     */
    protected $uid = 0;
    /**
     * @var shopSaleskuPluginProductDecorator
     */
    protected $product = null;

    public function __construct($data = array())
    {
        $this->product = shopSaleskuPluginProductsPool::getPool()->getProduct($data);
        $this->uid = shopSaleskuPluginProductsPool::getUid();
    }

    /**
     * Возвращает уникальный идентификатор объекта продукта
     * @return int
     */
    public function getUid() {
        return $this->uid;
    }

    /**
     * Пересоздает новый уникальный идентификатор объекта продукта
     */
    public function reset() {
        $this->uid = shopSaleskuPluginProductsPool::getUid();
    }
    /* Методы которые украсят и без того красивый декоратор) */
    public function __get($name) {
        return $this->product->$name;
    }
    public function __set($name, $value) {
        $this->product->$name = $value;
    }
    public function offsetExists($offset) {
        return (isset($this->product[$offset]) || isset($this->product[$offset]));
    }
    public function offsetGet($offset) {
        return $this->product[$offset];
    }
    public function offsetSet($offset, $value) {
        $this->product[$offset] = $value;
    }
    public function offsetUnset($offset) {
        unset($this->product[$offset]);
    }
    public function __call($name, $args) {
        if (method_exists($this->product, $name)) {
            return call_user_func_array(array($this->product, $name), $args);
        }
        return null;
    }
}
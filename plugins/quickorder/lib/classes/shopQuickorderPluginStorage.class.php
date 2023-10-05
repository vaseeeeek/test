<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopQuickorderPluginStorage
{
    private $code;
    private $storage;

    public function __construct($code = '')
    {
        if (!$this->storage) {
            $this->storage = wa()->getStorage();
        }
        if ($code === '') {
            $this->code = (new shopQuickorderPluginCart())->getCode();
        } else {
            $this->code = $code;
        }
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getSessionData($key, $default = null)
    {
        $data = $this->storage->get('shop/plugins/quickorder/' . $this->code);
        return isset($data[$key]) ? $data[$key] : $default;
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function setSessionData($key, $value)
    {
        $data = $this->storage->get('shop/plugins/quickorder/' . $this->code);
        if (!is_array($data)) {
            $data = array();
        }
        if ($value === null) {
            if (isset($data[$key])) {
                unset($data[$key]);
            }
        } else {
            $data[$key] = $value;
        }
        $this->storage->set('shop/plugins/quickorder/' . $this->code, $data);
    }

    public function clearSessionData()
    {
        $this->storage->del('shop/plugins/quickorder/' . $this->code);
    }
}
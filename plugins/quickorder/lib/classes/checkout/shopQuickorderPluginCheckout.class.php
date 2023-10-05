<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

abstract class shopQuickorderPluginCheckout
{
    protected $step;
    protected $methods;
    private $type;
    protected $cart = null;

    abstract function getMethods();

    public function __construct($methods = array(), shopQuickorderPluginCart $cart)
    {
        if ($this->cart === null) {
            $this->cart = $cart;
        }

        if ($this->type == shopPluginModel::TYPE_PAYMENT) {
            $this->step = new shopQuickorderPluginWaPayment($this->cart);
        } else {
            $this->step = new shopQuickorderPluginWaShipping($this->cart);
        }
        $this->methods = $this->initMethods($methods);
    }

    protected function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Filter methods with the help of plugin "delpayfilter"
     *
     * @return array
     * @throws waException
     */
    protected function postFilter()
    {
        $settings = shopQuickorderPluginHelper::getSettings();

        // Интеграция плагина "Фильтр доставки и оплаты" (delpayfilter)
        if (!empty($settings['use_delpayfilter'])) {
            // Список всех плагинов
            $plugins = wa()->getConfig()->getPlugins();
            // Проверяем доступность плагина
            if (isset($plugins['delpayfilter'])) {

                $filtered = $this->type == shopPluginModel::TYPE_PAYMENT ? shopDelpayfilterPlugin::filterPaymentMethods($this->methods) : shopDelpayfilterPlugin::filterDeliveryMethods($this->methods);

                if (waRequest::param('action') !== 'update') {
                    $this->methods = array_filter($this->methods);
                }
                foreach ($this->methods as $k => $m) {
                    // Если после фильтра необходимо скрыть метод, добавляем метку hide
                    if (!isset($filtered[$k]) || !empty($filtered[$k]['error'])) {
                        if ($this->methods[$k] === null) {
                            $this->methods[$k] = array('_id' => $k);
                        }
                        $this->methods[$k]['hide'] = 1;
                    } else {
                        $this->methods[$k] = $filtered[$k];
                    }
                }
            }
        } else {
            $filtered = array_filter($this->methods);
            foreach ($this->methods as $k => $m) {
                // Если после фильтра необходимо скрыть метод, добавляем метку hide
                if (!isset($filtered[$k])) {
                    if ($this->methods[$k] === null) {
                        $this->methods[$k] = array('_id' => $k);
                    }
                    $this->methods[$k]['hide'] = 1;
                } else {
                    $this->methods[$k] = $filtered[$k];
                }
            }
        }

        $this->methods = array_filter($this->methods);

        return $this->methods;
    }

    /**
     * Prepare and filter available methods before getting full information about them
     *
     * @param array $methods
     * @return array
     */
    private function initMethods($methods = array())
    {
        $methods = $this->prepareMethods($methods);

        // Если имеется необходимость вывести все методы, указываем это
        if (isset($methods['*'])) {
            $methods = '*';
        }

        return $this->filterMethods($methods);
    }

    /**
     * Filter only available methods
     *
     * @param array|string $methods
     * @return array
     */
    private function filterMethods($methods)
    {
        $plugin_model = new shopPluginModel();
        // Получаем список всех плагинов доставки/оплаты
        $all_methods = $plugin_model->listPlugins($this->type);
        // Фильтруем методы, если нет необходимости выводить все
        if ($methods !== '*') {
            foreach ($methods as $id) {
                if (!isset($all_methods[$id])) {
                    unset($methods[$id]);
                    continue;
                }
                $methods[$id] = $all_methods[$id];
            }
        } else {
            $methods = $all_methods;
        }
        return $methods;
    }

    /**
     * Prepare methods
     *
     * @param array $methods
     * @return array
     */
    private function prepareMethods($methods)
    {
        $data = array();
        if (is_array($methods)) {
            foreach ($methods as $f) {
                foreach ($f as $v) {
                    if ($v['value'] == '') {
                        $v['value'] = '*';
                    }
                    $data[$v['value']] = $v['value'];
                }
            }
        }
        return $data;
    }
}
<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopQuickorderPluginShipping extends shopQuickorderPluginCheckout
{
    public function __construct($methods, shopQuickorderPluginCart $cart)
    {
        $this->setType(shopPluginModel::TYPE_SHIPPING);
        parent::__construct($methods, $cart);
    }

    /**
     * Get list of shipping methods
     *
     * @return array $methods
     * @throws Exception
     */
    public function getMethods()
    {
        $options = $this->step->getShippingOptions();

        $selected_shipping = $this->step->getSelectedMethod();

        $action = waRequest::param('action');
        // Подготавливаем методы доставки к выводу в шаблон
        foreach ($this->methods as $method_id => &$m) {
            $m = $this->step->workupShippingMethod($m, $options, $selected_shipping);
            unset($m);
        }

        // Обработка плагином Фильтр доставки и оплаты
        $this->postFilter();

        // Отмечаем по умолчанию доступный метод доставки
        if (!$selected_shipping || (!isset($this->methods[$selected_shipping['id']]))) {
            foreach ($this->methods as $usm) {
                if (empty($usm['error']) && empty($usm['hide']) && $action !== 'update') {
                    $rate_id = null;
                    $rate = $this->step->getRate($usm['id'], $rate_id);
                    if (is_string($rate)) {
                        $rate = false;
                    }
                    $this->cart->getStorage()->setSessionData('shipping', array(
                        'id' => $usm['id'],
                        'rate_id' => $rate_id,
                        'name' => $rate ? $rate['name'] : '',
                        'plugin' => $rate ? $rate['plugin'] : ''
                    ));

                    $selected_shipping = array('id' => $usm['id'], 'rate_id' => $rate_id);
                    break;
                }
            }
        }

        return array('methods' => $this->methods, 'selected' => $selected_shipping);
    }

}
<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopQuickorderPluginPayment extends shopQuickorderPluginCheckout
{
    public function __construct($methods, shopQuickorderPluginCart $cart)
    {
        $this->setType(shopPluginModel::TYPE_PAYMENT);
        parent::__construct($methods, $cart);
    }

    /**
     * Get list of payment methods
     *
     * @return array
     * @throws waException
     */
    public function getMethods()
    {
        // Получаем выбранный метод доставки
        $selected_shipping = $this->cart->getStorage()->getSessionData('shipping');

        $selected_payment = $this->step->getSelectedMethod();

        // Получаем список плагинов оплаты, которые недоступны для этого метода
        $disabled = array();
        if ($selected_shipping) {
            $disabled = shopHelper::getDisabledMethods('payment', $selected_shipping['id']);
        }

        $currencies = wa('shop')->getConfig()->getCurrencies();
        foreach ($this->methods as $key => $m) {
            $method_id = $m['id'];

            // Если плагин оплаты недоступен, удаляем его
            if (in_array($method_id, $disabled)) {
                unset($this->methods[$key]);
                continue;
            }

            try {
                $plugin = shopPayment::getPlugin($m['plugin'], $m['id']);
                $custom_fields = $this->step->getCustomFields($method_id, $plugin);
                $custom_html = '';
                foreach ($custom_fields as $c) {
                    $custom_html .= '<div class="wa-field">' . $c . '</div>';
                }
                $this->methods[$key]['custom_html'] = $custom_html;
                $allowed_currencies = $plugin->allowedCurrency();
                if ($allowed_currencies !== true) {
                    $allowed_currencies = (array) $allowed_currencies;
                    if (!array_intersect($allowed_currencies, array_keys($currencies))) {
                        // Валюта не определена
                        unset($this->methods[$key]);
                        continue;
                    }
                }
                if (!$selected_payment && empty($this->methods[$key]['error'])) {
                    $selected_payment = $method_id;
                }
            } catch (Exception $ex) {
                waLog::log($ex->getMessage(), 'shop/checkout.error.log');
            }
        }

        $this->postFilter();

        // Отмечаем по умолчанию доступный метод оплаты
        if ($this->methods && (!$selected_payment || (!isset($this->methods[$selected_payment])))) {
            $method = reset($this->methods);
            $selected_payment = $method['id'];
            $this->cart->getStorage()->setSessionData('payment', $selected_payment);
        }

        return array('methods' => $this->methods, 'selected' => $selected_payment);
    }
}
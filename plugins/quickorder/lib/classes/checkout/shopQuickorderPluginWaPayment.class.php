<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopQuickorderPluginWaPayment extends shopCheckoutPayment
{
    private $cart;

    public function __construct(shopQuickorderPluginCart $cart)
    {
        $this->cart = $cart;
    }

    public function getCustomFields($id, waPayment $plugin)
    {
        $contact = $this->getContact();
        $order_params = $this->cart->getStorage()->getSessionData('params', array());
        $payment_params = isset($order_params['payment']) ? $order_params['payment'] : array();
        foreach ($payment_params as $k => $v) {
            $order_params['payment_params_' . $k] = $v;
        }
        $order = new waOrder(array(
            'contact' => $contact,
            'contact_id' => $contact ? $contact->getId() : null,
            'params' => $order_params,
        ));
        $custom_fields = $plugin->customFields($order);
        if (!$custom_fields) {
            return $custom_fields;
        }

        $selected = ($id == $this->cart->getStorage()->getSessionData('payment'));

        if ($selected) {
            foreach ($custom_fields as $name => &$row) {
                if (isset($payment_params[$name])) {
                    $row['value'] = $payment_params[$name];
                }
                unset($row);
            }
        }
        if (method_exists($this, 'getControls')) {
            return $this->getControls($custom_fields, 'payment_' . $id);
        } else {
            return (new shopQuickorderPluginMigrate())->getControls($custom_fields, 'payment_' . $id);
        }
    }

    public function getSelectedMethod()
    {
        if (waRequest::method() == 'post') {
            $data = waRequest::post('quickorder', array());
            $selected = $payment_id = ifempty($data['payment_id'], null);
            $this->cart->getStorage()->setSessionData('payment', $payment_id);
        } else {
            $selected = $this->cart->getStorage()->getSessionData('payment');
        }
        return $selected;
    }

}
<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopQuickorderPluginFrontendUpdateShippingController extends waJsonController
{

    public function execute()
    {
        // Тип формы: товар, корзина
        $type = waRequest::request('qformtype', 'product');

        $cart = new shopQuickorderPluginCart($type);

        // Общая сумма заказа
        $total = $cart->getTotal(false);

        $shipping = new shopQuickorderPluginWaShipping($cart);
        $items = $shipping->getItems();

        if (waRequest::method() == 'post') {
            wa()->getStorage()->close();
            $shipping_id = waRequest::post('shipping_id');
            $customer = waRequest::post('customer_' . $shipping_id);

            $address = ifset($customer['address.shipping'], array());

            if ($shipping_id) {
                $this->response = $shipping->getSingleShippingRates($shipping_id, $items, $address, $total);
            } else {
                $this->errors = _w('Shipping is required');
            }
        } elseif ($shipping_ids = waRequest::get('shipping_id', array(), waRequest::TYPE_ARRAY_INT)) {
            $address = $shipping->getAddress();
            wa()->getStorage()->close();
            $empty = true;
            foreach ($address as $v) {
                if ($v) {
                    $empty = false;
                    break;
                }
            }
            if ($empty) {
                $address = array();
            }
            if (!$address) {
                $config = wa('shop')->getConfig();
                /**
                 * @var shopConfig $config
                 */
                $settings = $config->getCheckoutSettings();
                if ($settings['contactinfo']['fields']['address']) {
                    foreach ($settings['contactinfo']['fields']['address']['fields'] as $k => $f) {
                        if (!empty($f['value'])) {
                            $address[$k] = $f['value'];
                        }
                    }
                }
            }

            waNet::multiQuery('shop.shipping');
            foreach ($shipping_ids as $shipping_id) {
                $this->response[$shipping_id] = $shipping->getSingleShippingRates($shipping_id, $items, $address, $total);
            }
        }
    }

}
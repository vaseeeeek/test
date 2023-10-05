<?php

class shopBuyPluginFrontendSuccessAction extends shopFrontendAction
{
    public function execute()
    {
        wa()->popActivePlugin();
        
        $order_id = wa()->getStorage()->get('shop/order_id');
        if (!$order_id) {
            wa()->getResponse()->redirect(wa()->getRouteUrl('shop/frontend'));
        }

        $order_model = new shopOrderModel();
        $order = $order_model->getById($order_id);
        $order_params_model = new shopOrderParamsModel();
        $order['params'] = $order_params_model->get($order_id);

        $payment = '';
        if (!empty($order['params']['payment_id'])) {
            try {
                $plugin = shopPayment::getPlugin(null, $order['params']['payment_id']);
                $payment = $plugin->payment(waRequest::post(), shopPayment::getOrderData($order, $plugin), null);
            } catch (waException $ex) {
                $payment = $ex->getMessage();
            }
        }
        $order['id'] = shopHelper::encodeOrderId($order_id);
        $order_items_model = new shopOrderItemsModel();
        $order['items'] = $order_items_model->getByField('order_id', $order_id, true);
        $this->getResponse()->addGoogleAnalytics($this->getGoogleAnalytics($order));
        $this->view->assign('order', $order);
        $this->view->assign('payment', $payment);


        /**
         * @event frontend_checkout
         * @return array[string]string $return[%plugin_id%] html output
         */
        $event_params = array('step' => 'success');
        $this->view->assign('frontend_checkout', wa()->event('frontend_checkout', $event_params));
        waRequest::setParam('action', 'checkout'); // kmgtm

        $this->getResponse()->setTitle('Заказ '.shopHelper::encodeOrderId($order_id).' оформлен!');

        if($this->getTheme()->getFile('buy.plugin.success.html'))
            $this->setThemeTemplate('buy.plugin.success.html');
    }

    protected function getGoogleAnalytics($order)
    {
        $title = waRequest::param('title');
        if (!$title) {
            $title = $this->getConfig()->getGeneralSettings('name');
        }
        if (!$title) {
            $app = wa()->getAppInfo();
            $title = $app['name'];
        }

        $result =  "_gaq.push(['_addTrans',
            '".$order['id']."',           // transaction ID - required
            '".htmlspecialchars($title)."',  // affiliation or store name
            '".$this->getBasePrice($order['total'], $order['currency'])."',          // total - required
            '".$this->getBasePrice($order['tax'], $order['currency'])."',           // tax
            '".$this->getBasePrice($order['shipping'], $order['currency'])."',              // shipping
            '".$this->getOrderAddressField($order, 'city')."',       // city
            '".$this->getOrderAddressField($order, 'region')."',     // state or province
            '".$this->getOrderAddressField($order, 'country')."'             // country
        ]);\n";

        foreach ($order['items'] as $item) {
            $sku = $item['type'] == 'product' ? $item['sku_code'] : '';
            $result .= " _gaq.push(['_addItem',
            '".$order['id']."',           // transaction ID - required
            '".$sku."',           // SKU/code - required
            '".htmlspecialchars($item['name'])."',        // product name
            '',   // category or variation
            '".$this->getBasePrice($item['price'], $order['currency'])."',          // unit price - required
            '".$item['quantity']."'               // quantity - required
          ]);\n";
        }

        $result .= "_gaq.push(['_trackTrans']);\n";

        return $result;
    }

    protected function getOrderAddressField($order, $name)
    {
        if (isset($order['params']['shipping_address.'.$name])) {
            return htmlspecialchars($order['params']['shipping_address.'.$name]);
        }
        return '';
    }

    protected function getBasePrice($price, $currency)
    {
        return shop_currency($price, $currency, $this->getConfig()->getCurrency(true), false);
    }


}
<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopFlexdiscountPluginFrontendUpdateCartController extends shopFlexdiscountPluginJsonController
{

    public function execute()
    {
        $data = waRequest::post('data', array());

        // Обновление цен у товаров
        if (!empty($data['items'])) {
            $items = $data['items'];
            $order = shopFlexdiscountApp::get('order.full');
            $plugin_helper = new shopFlexdiscountPluginViewHelper(null, 'flexdiscount');
            if (!empty($order['order']['items'])) {
                foreach ($order['order']['items'] as $item) {
                    if (isset($items[$item['id']])) {
                        foreach ($items[$item['id']] as $block_id => $i) {
                            $this->response['items'][$block_id] = $plugin_helper->cartItem($item, $i['field'], $i['params']);
                        }
                    }
                }
            }
        }

        // Deprecated. TODO УДАЛИТЬ
        if (!empty($data['fields'])) {
            $fields = $data['fields'];
            $order = shopFlexdiscountApp::get('order.full');
            if (!empty($order['order']['items'])) {
                $c = 0;
                foreach ($order['order']['items'] as $item) {
                    if (isset($fields[$item['id']])) {
                        foreach ($fields[$item['id']] as $k => $i) {
                            $mult = (int) $i['mult'];
                            $this->response['fields'][$c]['removeClass'] = $k;
                            $this->response['fields'][$c]['elem'] = ".flexdiscount-cart-price.cart-id-" . $item['id'] . "." . $k;
                            $this->response['fields'][$c]['price'] = shopFlexdiscountPluginHelper::cartPrice($item, $mult, (int) $i['html'], (int) $i['format']);
                            $this->response['fields'][$c]['clear_price'] = shopFlexdiscountPluginHelper::cartPrice($item, $mult, null, false, false);
                            $this->response['fields'][$c]['clear_product_price'] = $item['price'] * ($mult ? $item['quantity'] : 1);
                            $c++;
                        }
                    }
                }
            }
        }

        waRequest::setParam('flexdiscount_skip_frontend_products', 1);

        // Примененные скидки
        if (!empty($data['user_discounts'])) {
            foreach ($data['user_discounts'] as $view_type) {
                $this->response['blocks']['discounts'][$view_type] = shopFlexdiscountPluginHelper::getUserDiscounts($view_type);
            }
        }
        // Бонусы
        if (shopAffiliate::isEnabled()) {
            if (!empty($data['affiliate_block'])) {
                foreach ($data['affiliate_block'] as $view_type) {
                    $this->response['blocks']['affiliate'][$view_type] = shopFlexdiscountPluginHelper::getUserAffiliate($view_type);
                }
            }

            $workflow = shopFlexdiscountApp::getOrder()->getOrderCalculateDiscount();
            $cart = new shopCart();
            $add_affiliate_bonus = shopAffiliate::calculateBonus(array(
                'total' => $cart->total(),
                'items' => $cart->items(false)
            ));
            $bonus = (float) round($workflow['affiliate'], 2) + (float) round($add_affiliate_bonus, 2);
            $this->response['clear_affiliate'] = $bonus;
        }
    }

}

<?php

/**
 * Класс для создания заказа во фронтенде
 *
 * @author Steemy, created by 03.04.2018
 * @link http://steemy.ru/
 */

class shopClicklitePluginFrontendOrderController extends waJsonController
{
    public function execute()
    {
        /**
         * Проверка CSRF если в настройках магазина активирована
         */
        if (wa('shop')->getConfig()->getInfo('csrf') && waRequest::method() == 'post') {
            if (waRequest::post('_csrf') != waRequest::cookie('_csrf')) {
                throw new waException('CSRF Protection', 403);
            }
        }

        $pluginSetting = shopClicklitePluginSettings::getInstance();
        $settings = $pluginSetting->getSettings();

        if(!$settings['status']) {
            $this->response = array(
                'status' => false,
            );
            return false;
        }

        $clickliteCart = waRequest::post('clicklite__cart', '');
        $name = waRequest::post('clicklite__name', '');
        $phone = waRequest::post('clicklite__phone', '');
        $antispam = waRequest::post('clicklite__antispam', '');

        // politika checkbox
        $policyCheckbox = true;
        if ($settings['policy_checkbox']) {
            $policyCheckbox = waRequest::post('policy_checkbox');
        }

        if($phone && $antispam && $policyCheckbox)
        {
            if ($clickliteCart == 1)
            {
                $errors = array();
                if ($orderId = $this->createOrderCart($errors)) {

                    $shopOrderModel = new shopOrderModel();
                    $order = $shopOrderModel->getOrder($orderId);

                    $this->bildNoticeInsertOrder($order);

                    $this->response = array(
                        'status' => true,
                        'redirect' => true,
                        'message' => str_replace('$orderId', shopHelper::encodeOrderId($orderId), $settings['thank']),
                        'info' => json_encode($this->getEcommerce($order)),
                    );
                } else {
                    $this->response = array(
                        'status' => false,
                        'message' => $errors[0],
                    );
                }
            }
            else
            {
                $id = waRequest::post('product_id', '');
                $skuId = waRequest::post('product_sku', '');
                $quantity = (int)waRequest::post('quantity', 1);

                $_POST['quantity'] = $quantity;

                if ($id && $skuId && $quantity)
                {
                    $skuModel = new shopProductSkusModel();
                    $sku = $skuModel->getById($skuId);

                    if ($sku){
                        if (!wa()->getSetting('ignore_stock_count')) {
                            if ($sku['count'] !== null && $quantity > $sku['count']) {
                                $this->response = array(
                                    "status" => false,
                                    "message" => 'В наличии только ' . $sku['count'] . ' шт.',
                                );
                                return false;
                            }
                        }

                        if ($orderId = $this->createOrder(waRequest::post())) {

                            $shopOrderModel = new shopOrderModel();
                            $order = $shopOrderModel->getOrder($orderId);

                            $this->bildNoticeInsertOrder($order);

                            $this->response = array(
                                'status' => true,
                                'message' => str_replace('$orderId', shopHelper::encodeOrderId($orderId), $settings['thank']),
                                'info' => json_encode($this->getEcommerce($order)),
                            );
                        } else {
                            waLog::log('Не удалось создать заказ', 'shop/clicklite.error.log');
                            $this->response = array(
                                'status' => false,
                                'message' => 'Не удалось создать заказ',
                            );
                        }
                    } else {
                        waLog::log('Не удалось получить skuID', 'shop/clicklite.error.log');
                        $this->response = array(
                            'status' => false,
                            'message' => 'Не удалось получить skuID',
                        );
                    }
                }
                else
                {
                    waLog::log('Не переданы id, sku_id товара', 'shop/clicklite.error.log');
                    $this->response = array(
                        'status' => false,
                        'message' => 'Не переданы id, sku_id товара',
                    );
                }
            }
        }
        else
        {
            waLog::log('Не передано одно из значений $_POST', 'shop/clicklite.error.log');
            $this->response = array(
                'status' => false,
                'message' => 'Не передано одно из значений $_POST',
            );
        }
    }

    /**
     * Уведомления в вк и телеграмм
     * @param $order
     */
    private function bildNoticeInsertOrder($order)
    {
        $pluginSetting = shopClicklitePluginSettings::getInstance();
        $settings = $pluginSetting->getSettings();

        /*
         * - Отправляем сообщение в телеграм и вк если удалось создать заказ
         */
        $notice = new shopClicklitePluginNotice();
        $notice->notifyTelegram($order['id']);
        $notice->notifyVk($order);

        /*
         * Пишем в базу id заказа для показа потом в бекенде магазина
         */
        $model = new shopClicklitePluginModel();
        $model->insert(array('order_id'=>$order['id']));
    }

    /**
     * Создание заказа в корзине
     * @param array $errors
     * @return bool|mixed
     * @throws waException
     */
    private function createOrderCart(&$errors = array())
    {
        $pluginSetting = shopClicklitePluginSettings::getInstance();
        $settings = $pluginSetting->getSettings();

        $cart = new shopCart();
        if (!wa()->getSetting('ignore_stock_count')) {
            $check_count = true;
            if (wa()->getSetting('limit_main_stock') && waRequest::param('stock_id')) {
                $check_count = waRequest::param('stock_id');
            }
            $cart_model = new shopCartItemsModel();
            $not_available_items = $cart_model->getNotAvailableProducts($cart->getCode(), $check_count);
            foreach ($not_available_items as $row) {
                if ($row['sku_name']) {
                    $row['name'] .= ' (' . $row['sku_name'] . ')';
                }
                if ($row['available']) {
                    if ($row['count'] > 0) {
                        $errors[] = sprintf(_w('Only %d pcs of %s are available, and you already have all of them in your shopping cart.'), $row['count'], $row['name']);
                    } else {
                        $errors[] = sprintf(_w('Oops! %s just went out of stock and is not available for purchase at the moment. We apologize for the inconvenience. Please remove this product from your shopping cart to proceed.'), $row['name']);
                    }
                } else {
                    $errors[] = sprintf(_w('Oops! %s is not available for purchase at the moment. Please remove this product from your shopping cart to proceed.'), $row['name']);
                }
            }
            if ($errors) {
                return false;
            }
        }

        $name = htmlspecialchars(trim(waRequest::post('clicklite__name', '')));
        $phone = htmlspecialchars(trim(waRequest::post('clicklite__phone')));

        if (wa()->getUser()->isAuth()) {
            $contact = wa()->getUser();
        } else {
            $contact = new waContact();
        }

        if($name)
            $contact->set('firstname', $name);

        $contact->set('phone', $phone);

        if (wa()->getUser()->isAuth()) {
            $contact->save();
        }

        $items = $cart->items(false);

        // remove id from item
        foreach ($items as &$item) {
            unset($item['id']);
            unset($item['parent_id']);
        }
        unset($item);

        $order = array(
            'contact' => $contact,
            'items'   => $items,
            'total'   => $cart->total(false),
            'params'  => array(),
        );

        $order['discount_description'] = null;
        $order['discount'] = shopDiscounts::apply($order, $order['discount_description']);

        $routing_url = wa()->getRouting()->getRootUrl();
        $order['params']['storefront'] = wa()->getConfig()->getDomain().($routing_url ? '/'.$routing_url : '');
        if (wa()->getStorage()->get('shop_order_buybutton')) {
            $order['params']['sales_channel'] = 'buy_button:';
        }

        $order['params']['ip'] = waRequest::getIp();
        $order['params']['user_agent'] = waRequest::getUserAgent();
        $order['shipping'] = 0;

        $order['comment'] = $settings['comment'];

        $workflow = new shopWorkflow();
        if ($order_id = $workflow->getActionById('create')->run($order)) {

            $step_number = shopCheckout::getStepNumber();
            $checkout_flow = new shopCheckoutFlowModel();
            $checkout_flow->add(array(
                'step' => $step_number
            ));

            $cart->clear();
            wa()->getStorage()->remove('shop/checkout');
            wa()->getStorage()->set('shop/order_id', $order_id);

            return $order_id;
        } else {
            return false;
        }
    }

    /**
     * Создание заказа для одного товара
     * @param $data
     * @param $settings
     * @return bool|mixed
     * @throws waException
     */
    private function createOrder($data)
    {
        $pluginSetting = shopClicklitePluginSettings::getInstance();
        $settings = $pluginSetting->getSettings();

        $name      = !empty($data['clicklite__name']) ? htmlspecialchars(trim($data['clicklite__name'])) : '';
        $phone     = htmlspecialchars(trim($data['clicklite__phone']));
        $antispam  = htmlspecialchars(trim($data['clicklite__antispam']));
        $id        = htmlspecialchars(trim($data['product_id']));
        $skuId     = htmlspecialchars(trim($data['product_sku']));
        $quantity  = htmlspecialchars(trim($data['quantity']));

        if (wa()->getUser()->isAuth()) {
            $contact = wa()->getUser();
        } else {
            $contact = new waContact();
        }

        if($name)
            $contact->set('firstname', $name);

        $contact->set('phone', $phone);

        if (wa()->getUser()->isAuth()) {
            $contact->save();
        }


        $productModel = new shopProductModel();
        $product = $productModel->getById($id);

        $skuModel = new shopProductSkusModel();
        $sku = $skuModel->getById($skuId);

        $code = waRequest::cookie('shop_cart');
        if (!$code) {
            $code = md5(uniqid(time(), true));
            wa()->getResponse()->setCookie('shop_cart', $code, time() + 30 * 86400, null, '', false, true);
        }

        $count = 0;
        $dataInf = array(
            'code' => $code,
            'contact_id' => $this->getUser()->getId(),
            'product_id' => $product['id'],
            'sku_id' => $skuId,
            'create_datetime' => date('Y-m-d H:i:s'),
            'quantity' => $quantity
        );
        $items[$count] = $dataInf;
        $items[$count]['type'] = 'product';
        $items[$count]['product'] = $product;
        $items[$count]['sku_code'] = $skuId;
        $items[$count]['purchase_price'] = $sku['purchase_price'];
        $items[$count]['sku_name'] = $sku['name'];
        $items[$count]['currency'] = $product['currency'];
        $items[$count]['price'] = $sku['price'];
        $items[$count]['name'] = $product['name'];
        if ($sku['name']) {
            $items[$count]['name'] .= ' (' . $sku['name'] . ')';
        }

        $total = shop_currency($quantity * $items[$count]['price'], $items[$count]['currency'], null, false);


        $order = array(
            'contact' => $contact,
            'items'   => $items,
            'total'   => $total,
            'params'  => array(),
        );

        $order['discount_description'] = null;
        $order['discount'] = shopDiscounts::apply($order, $order['discount_description']);

        $routing_url = wa()->getRouting()->getRootUrl();
        $order['params']['storefront'] = wa()->getConfig()->getDomain().($routing_url ? '/'.$routing_url : '');
        if (wa()->getStorage()->get('shop_order_buybutton')) {
            $order['params']['sales_channel'] = 'buy_button:';
        }

        $order['params']['ip'] = waRequest::getIp();
        $order['params']['user_agent'] = waRequest::getUserAgent();

        $order['shipping'] = 0;
        $order['comment'] = $settings['comment'];

        $workflow = new shopWorkflow();
        if ($order_id = $workflow->getActionById('create')->run($order)) {

            $step_number = shopCheckout::getStepNumber();
            $checkout_flow = new shopCheckoutFlowModel();
            $checkout_flow->add(array(
                'step' => $step_number
            ));

            wa()->getStorage()->remove('shop/checkout');
            wa()->getStorage()->set('shop/order_id', $order_id);

            return $order_id;
        } else {
            return false;
        }
    }

    /**
     * Формируем для эелектронной коммерции
     * @param $order
     * @return array
     */
    private function getEcommerce($order)
    {
        foreach($order['items'] as $item)
        {
            $products[] = array(
                'id' => $item['product_id'],
                'name' => $item['name'],
                'price' => $item['price'],
                'category' => $this->getCategoryName($item['product_id']),
                'quantity' => $item['quantity'],
            );
        }

        $ecommerce['currencyCode'] = $order['currency'];
        $ecommerce['purchase'] = array(
            'actionField' => array(
                'id' => shopHelper::encodeOrderId($order['id']),
                'affiliation' => wa('shop')->getConfig()->getGeneralSettings('name'),
                'revenue' => $order['total'],
                'tax' => $order['tax'],
                'shipping' => $order['shipping'],
            ),
            'products' => $products,
        );

        return $ecommerce;
    }

    private function getCategoryName($product_id)
    {
        $categoryModel = new shopCategoryModel();
        $category = $categoryModel->getById($product_id);
        return ifset($category['name']);
    }
}

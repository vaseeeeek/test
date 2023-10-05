<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopQuickorderPluginFrontendUpdateController extends waJsonController
{
    public function execute()
    {
        // Тип формы: товар, корзина
        $type = waRequest::post('qformtype', 'product');

        $cart = new shopQuickorderPluginCart($type);
        $storage = wa()->getStorage();

        if ($cart->issetData('coupon')) {
            $data = $storage->get('shop/checkout');
            $old_coupon = ifset($data, 'coupon_code', '');
            $data['coupon_code'] = $cart->getData('coupon');
            $storage->set('quickorder/coupon', $data['coupon_code']);
            // Подменяем штатный купон тем, который передан через плагин
            $storage->set('shop/checkout', $data);
        }

        // Общая сумма заказа
        $total = $cart->getTotal();
        // Скидка
        $discount = $cart->getDiscount();

        // Проверяем, был ли введен верный купон
        // Для этого имитируем корзину без купона
        $is_coupon_valid = -1;
        if ($cart->issetData('coupon') && $cart->getData('coupon')) {
            $discount_without_coupon = $cart->getDiscountWithoutCoupon();
            $is_coupon_valid = $discount == $discount_without_coupon ? 0 : 1;
        }

        $helper = new shopQuickorderPluginHelper();
        $settings = $helper::getSettings();
        $form_settings = !empty($settings['shared_display_settings']) ? $settings['product'] : $settings[$type];
        $templates = $helper->getTemplates($settings);
        $view = new waSmarty3View(wa());
        $view->assign('form_settings', $form_settings);

        // Заменяем поля доставки
        $shipping = $cart->getMethods('shipping');
        $view->assign('shipping', $shipping);
        $view->assign('no_js', 1);
        $shipping_html = $view->fetch('string:' . $templates['shipping']['frontend_template']);

        // Заменяем поля оплаты
        $payment = $cart->getMethods('payment');
        $view->assign('payment', $payment);
        $payment_html = $view->fetch('string:' . $templates['payment']['frontend_template']);

        $items = $cart->getItems();

        // Интеграция плагина "Гибкие скидки и бонусы" (flexdiscount)
        // Проверяем доступность плагина
        $use_flexdiscount = 0;
        if ((!empty($settings['use_flexdiscount_ad']) || !empty($settings['flexdiscount_prices'])) && method_exists('shopFlexdiscountPlugin', 'isEnabled') && shopFlexdiscountPlugin::isEnabled()) {
            $use_flexdiscount = 1;
        }

        if ($use_flexdiscount) {
            // Активные скидки
            if (!empty($settings['use_flexdiscount_ad'])) {
                $workflow = shopFlexdiscountData::getOrderCalculateDiscount();
                $view_type = !empty($settings['flexdiscount_avt']) ? (int) $settings['flexdiscount_avt'] : 0;
                $user_discounts = array(
                    'html' => !empty($workflow['active_rules']) ? shopFlexdiscountPluginHelper::getUserDiscounts($view_type) : '',
                    'collapse' => !empty($settings['collapse_flexdiscount']),
                );
            }
            // Цены со скидкой
            if (!empty($settings['flexdiscount_prices'])) {
                foreach ($items as $item) {
                    if ($item['type'] == 'product') {
                        if ($type !== 'product') {
                            $prices[$item['sku_id']] = shopFlexdiscountPluginHelper::cartPrice($item, false, 0, false, false);
                        } else {
                            $workflow = shopFlexdiscountPluginHelper::getProductDiscounts($item, null, 0, false);
                            $prices[$item['sku_id']] = ifset($workflow, 'clear_price', $item['price']);
                        }
                    }
                }
            }
        }

        // Бонусная программа
        $affiliate_html = '';
        if (!empty($form_settings['use_affiliate']) && shopAffiliate::isEnabled()) {
            $affiliate = $helper->getAffiliate($cart, $form_settings, ifset($workflow));
            $view->assign('settings', $form_settings);
            $view->assign('affiliate', $affiliate);
            $affiliate_html = $view->fetch('string:' . $templates['affiliate']['frontend_template']);
        }

        $quantity = 0;
        foreach ($items as $item) {
            if ($item['type'] == 'product') {
                $quantity += $item['quantity'];
            }
        }

        // Возвращаем штатный купон на место
        if ($cart->issetData('coupon')) {
            $data = $storage->get('shop/checkout');
            $data['coupon_code'] = $old_coupon;
            $storage->set('shop/checkout', $data);
        }

        $this->response = array(
            'total' => $total,
            'quantity' => $quantity,
            'discount' => $discount,
            'affiliate' => $affiliate_html,
            'shipping' => $shipping_html,
            'payment' => $payment_html,
            'is_coupon_valid' => $is_coupon_valid,
            'user_discounts' => !empty($user_discounts) ? $user_discounts : null,
            'prices' => !empty($prices) ? $prices : null,
            'replace_shipping' => waRequest::post('replace_shipping'),
            'replace_shipping_plugins' => ['courier', 'novaposhta2']
        );
    }

}
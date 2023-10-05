<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopDelpayfilterCore extends shopDelpayfilterConditions
{

    /**
     * Get delivery and payment methods we should hide
     *
     * @param array $filters
     * @param bool $force
     * @return array - array('delivery' => array(), 'payment' => array())
     * @throws waException
     */
    public static function getMethods($filters, $force = false)
    {
        static $methods = null;
        static $already_forced = 0;
        if ($methods === null || ($force && !$already_forced)) {

            // Учитываем плагин "Купить в 1 клик" (quickorder)
            if (waRequest::param('plugin', '') == 'quickorder') {
                $order_params = shopDelpayfilterData::getCurrentOrderParams();
                $items = $order_params['quickorder_cart']->getItems();
                $total = $order_params['quickorder_cart']->getTotal(false);
            } else {
                $cart = new shopCart();
                $items = $cart->items(false);
                $total = $cart->total();
            }

            // Выполняем предварительную обработку товаров
            shopDelpayfilterHelper::workupProducts($items);

            $result_items = array();
            $user = self::$user = wao(new shopDelpayfilterHelper())->getContact();
            self::$total = (float) shop_currency($total, wa('shop')->getConfig()->getCurrency(false), wa('shop')->getConfig()->getCurrency(true), false);
            shopDelpayfilterData::setOrderItems($items);

            // Выполняем перебор фильтров
            foreach ($filters as $rule) {
                if ($rule['status']) {

                    // Для неавторизованного пользователя производим проверку по email или телефону
                    if (!empty($rule['check_email']) || !empty($rule['check_phone'])) {
                        self::$user = self::getUser($rule);
                    } else {
                        self::$user = $user;
                    }

                    // Условия 
                    $conditions = self::decode($rule['conditions']);
                    // Товары, удовлетворяющие условиям
                    $result_items[$rule['id']] = $conditions ? self::filter_items($items, $conditions['group_op'], $conditions['conditions']) : $items;
                }
            }
            // Отобранные методы доставки и оплаты
            $methods = self::filterMethods($result_items, $filters);
            if ($force) {
                $already_forced = 1;
            }
        }
        return $methods;
    }

    /**
     * Filter methods
     *
     * @param array $result_items - items after conditions
     * @param array $rules - all rules
     * @return float
     */
    private static function filterMethods($result_items, $rules)
    {
        $target_delivery = $target_payment = array();
        foreach ($result_items as $rule_id => $filter_items) {
            // Если фильтра не существует или условия не пройдены, пропускаем
            if (!isset($rules[$rule_id]) || empty($filter_items)) {
                continue;
            }

            $rule = $rules[$rule_id];
            $target = self::decode($rule['target']);
            // Отбираем методы
            foreach ($target as $t) {
                if ($t['target'] == 'shipping') {
                    $target_delivery[$t['condition']['value']] = !empty($rule['error_shipping']) ? $rule['error_shipping'] : '';
                }
                if ($t['target'] == 'payment') {
                    $target_payment[$t['condition']['value']] = !empty($rule['error_payment']) ? $rule['error_payment'] : '';
                }
            }
        }
        return array("delivery" => $target_delivery, "payment" => $target_payment);
    }

}

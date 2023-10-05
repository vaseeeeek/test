<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopDelpayfilterPlugin extends shopPlugin
{

    private static $active_shipping = array();

    // Данный хук необходим для того, чтобы в корзине отлавливать POST/GET запросы. Иначе они не сохраняются
    public function orderCalculateDiscount($params)
    {
        static $inited = 0;
        // Учитываем плагин "Купить в 1 клик" (quickorder)
        if (!$inited && waRequest::param('plugin', '') !== 'quickorder' && wa()->getEnv() == 'frontend') {
            $inited = 1;
            self::getFailedMethods();
        }
    }

    public function frontendOrderCartVars(&$params)
    {
        $output = array();

        /* Осуществляем поиск условий "по данным пользователя".
           Если такое условие будет найдено, тогда отслеживаем на витрине изменение данных пользователя и при необходимости
           обновляем данные доставки и оплаты.
        */
        $filters = shopDelpayfilterHelper::getFilters();
        $target_types = array();
        foreach ($filters as $rule) {
            if ($rule['status']) {
                // Условия
                $conditions = shopDelpayfilterConditions::decode($rule['conditions']);
                if (!empty($conditions['conditions'])) {
                    if ($this->findUserConditions($conditions['conditions'])) {
                        $targets = shopDelpayfilterConditions::decode($rule['target']);
                        $target_types += $this->getTargetTypes($targets);
                    }
                }
            }
        }
        if ($target_types) {
            $output['bottom'] = '<script>';
            $output['bottom'] .= '$(document).on("change", "#wa-step-auth-section input, #wa-step-auth-section select, #wa-step-auth-section textarea", function() {';
            $output['bottom'] .= 'var controller = $("#js-order-form").data("controller");';
            $output['bottom'] .= 'if (controller !== undefined && (' . (isset($target_types['shipping']) ? 'controller.sections.shipping.$form.length' : '') . (isset($target_types['payment']) && isset($target_types['shipping']) ? ' || ' : '') . (isset($target_types['payment']) ? 'controller.sections.payment.$form.length' : '') . ')) {';
            $output['bottom'] .= '$(document).trigger("wa_order_cart_changed");';
            $output['bottom'] .= '}';
            $output['bottom'] .= '});';
            $output['bottom'] .= '</script>';
        }

        return $output;
    }

    /**
     * Check if conditions have "user_data"
     *
     * @param $conditions
     * @return int
     */
    private function findUserConditions($conditions)
    {
        $result = 0;
        foreach ($conditions as $c) {
            // Если перед нами группа скидок, разбираем ее
            if (isset($c['group_op'])) {
                $conditions2 = shopDelpayfilterConditions::decode($c['conditions']);
                $result = $this->findUserConditions($conditions2);
                if ($result) {
                    break;
                }
            } elseif ($c['type'] == 'user_data') {
                $result = 1;
                break;
            }
        }
        return $result;
    }

    /**
     * Get active target types of the
     *
     * @param $targets
     * @return array
     */
    private function getTargetTypes($targets)
    {
        $types = array();
        foreach ($targets as $target) {
            if ($target['target'] == 'payment') {
                $types['payment'] = 1;
            } elseif ($target['target'] == 'shipping') {
                $types['shipping'] = 1;
            }
        }
        return $types;
    }

    public function checkoutAfterShipping($params)
    {
        // Меняем отображение
        if (self::$active_shipping) {
            $params['process_result']['data']['input']['shipping']['type_id'] =
            $params['data']['input']['shipping']['type_id'] =
                self::$active_shipping['type'];
            $params['process_result']['data']['input']['shipping']['variant_id'] =
            $params['data']['input']['shipping']['variant_id'] =
                self::$active_shipping['variant_id'];
            $params['process_result']['data']['shipping']['selected_variant'] = self::$active_shipping['data'];
            $params['process_result']['result']['selected_type_id'] = self::$active_shipping['type'];
            $params['process_result']['result']['selected_variant_id'] = self::$active_shipping['variant_id'];
        }
    }

    public function checkoutRenderShipping($params)
    {
        self::$active_shipping = array();

        // Сохраняем информацию о заказе
        $cache = new waRuntimeCache('delpayfilter_checkout_params');
        $checkout_params = array(
            'shipping' => array('id' => 0, 'rate_id' => 0),
            'contact' => ''
        );
        if (!empty($params['vars']['shipping']['selected_variant_id'])) {
            $parts = explode('.', $params['vars']['shipping']['selected_variant_id']);
            $checkout_params['shipping']['id'] = (int) $parts[0];
            $checkout_params['shipping']['rate_id'] = $parts[1];
        }

        $helper = new shopDelpayfilterHelper();
        $update = array();
        // Обновляем контактные поля
        if (!empty($params['vars']['auth']['fields'])) {
            $update = $params['vars']['auth']['fields'];
        }
        // Обновляем поля адреса
        if (!empty($params['vars']['region']['selected_values'])) {
            $address = array('address.shipping' => array('country' => $params['vars']['region']['selected_values']['country_id'], 'region' => $params['vars']['region']['selected_values']['region_id'], 'city' => $params['vars']['region']['selected_values']['city']));
            $update += $address;
        }
        if ($update) {
            $helper->updateContact($update);
        }
        $cache->set($checkout_params);

        // Собираем все методы в массив
        $methods = array('pickup' => array(), 'todoor' => array(), 'post' => array());
        $methods_ids = array();
        if (!empty($params['vars']['shipping']['types'])) {
            foreach ($params['vars']['shipping']['types'] as $k => $type) {
                if (!empty($type['variants'])) {
                    foreach ($type['variants'] as $variant_k => $variant) {
                        $id = explode('.', $variant_k)[0];
                        $methods[$k][$variant_k] = $id;
                        $methods_ids[$id] = $id;
                    }
                }
            }

            // Фильтруем методы
            $output_methods = self::filterDeliveryMethods($methods_ids, true);

            // Удаляем методы на витрине
            $change_variant = false;
            foreach ($params['vars']['shipping']['types'] as $k => $type) {
                if (!empty($type['variants'])) {
                    foreach ($type['variants'] as $variant_k => $variant) {
                        $key = $methods[$k][$variant_k];
                        // Если варианта доставки нет среди отфильтрованных, удаляем вариант
                        if (!isset($output_methods[$key])) {
                            unset($params['vars']['shipping']['types'][$k]['variants'][$variant_k]);
                            // Если вариантов больше не осталось, удаляем метод доставки
                            if (empty($params['vars']['shipping']['types'][$k]['variants'])) {
                                unset($params['vars']['shipping']['types'][$k]);
                            }
                            // Если вариант, который мы удалили, был выбран, заменяем его
                            if ($checkout_params['shipping']['id'] == $key) {
                                $change_variant = true;
                            }
                        }
                        // Меняем активный способ доставки
                        if ($change_variant) {
                            if (!empty($params['vars']['shipping']['types'][$k]['variants'])) {
                                $params['data']['shipping']['selected_variant'] =
                                self::$active_shipping['data'] =
                                    reset($params['vars']['shipping']['types'][$k]['variants']);
                                $params['vars']['shipping']['selected_variant_id'] =
                                $params['data']['input']['shipping']['variant_id'] =
                                self::$active_shipping['variant_id'] =
                                    key($params['vars']['shipping']['types'][$k]['variants']);
                                $checkout_params['shipping']['id'] =
                                self::$active_shipping['id'] =
                                    explode('.', $params['vars']['shipping']['selected_variant_id'])[0];
                                self::$active_shipping['type'] = $k;
                            } else {
                                $params['vars']['shipping']['selected_variant_id'] =
                                $params['data']['input']['shipping']['variant_id'] =
                                $params['vars']['shipping']['selected_type_id'] =
                                $params['data']['input']['shipping']['type_id'] =
                                $params['data']['shipping']['selected_variant'] =
                                self::$active_shipping['type'] =
                                self::$active_shipping['variant_id'] =
                                self::$active_shipping['data'] =
                                self::$active_shipping['id'] = null;
                                $checkout_params['shipping']['id'] = 0;
                            }
                            $change_variant = false;
                        }
                    }
                }
            }
        }
        $cache->set($checkout_params);
    }

    public function checkoutRenderPayment($params)
    {
        $checkout_params = array(
            'payment' => 0
        );
        // Сохраняем информацию о заказе
        $cache = new waRuntimeCache('delpayfilter_checkout_params');
        if ($cache->isCached()) {
            $checkout_params += $cache->get();
        }

        if (!empty($params['vars']['payment']['selected_method_id'])) {
            $checkout_params['payment'] = (int) $params['vars']['payment']['selected_method_id'];
        }
        $cache->set($checkout_params);

        if (!empty($params['vars']['payment']['methods'])) {
            $payment_methods = $params['vars']['payment']['methods'];
            // Фильтруем методы
            $output_methods = self::filterPaymentMethods($payment_methods, true);

            // Удаляем методы на витрине
            foreach ($payment_methods as $k => $method) {
                if (!isset($output_methods[$k])) {
                    unset($params['vars']['payment']['methods'][$k]);
                    // Если вариант, который мы удалили, был выбран, заменяем его
                    if ($checkout_params['payment'] == $k) {
                        $checkout_params['payment'] = 0;
                    }
                }
            }
        }

        $cache->set($checkout_params);
    }

    /**
     * Filter delivery methods.
     *
     * @param array $methods
     * @param bool $force
     * @return array
     * @throws waException
     */
    public static function filterDeliveryMethods($methods, $force = false)
    {
        $filter_methods = self::getFailedMethods($force);
        foreach ($methods as $k => $m) {
            if (isset($filter_methods['delivery'][$k])) {
                if ($filter_methods['delivery'][$k]) {
                    $methods[$k]['error'] = $filter_methods['delivery'][$k];
                } else {
                    unset($methods[$k]);
                }
            }
        }
        return $methods;
    }

    /**
     * Filter payment methods.
     *
     * @param array $methods
     * @param bool $force
     * @return array
     * @throws waException
     */
    public static function filterPaymentMethods($methods, $force = false)
    {
        $filter_methods = self::getFailedMethods($force);
        foreach ($methods as $k => $m) {
            if (isset($filter_methods['payment'][$k])) {
                if ($filter_methods['payment'][$k]) {
                    $methods[$k]['error'] = $filter_methods['payment'][$k];
                } else {
                    unset($methods[$k]);
                }
            }
        }
        return $methods;
    }

    /**
     * Methods that we need to hide
     *
     * @param bool $force
     * @return array
     * @throws waException
     */
    public static function getFailedMethods($force = false)
    {
        $filters = shopDelpayfilterHelper::getFilters();
        return shopDelpayfilterCore::getMethods($filters, $force);
    }

}

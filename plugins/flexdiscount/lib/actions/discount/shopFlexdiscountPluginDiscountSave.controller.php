<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopFlexdiscountPluginDiscountSaveController extends waJsonController
{

    public function preExecute()
    {
        $user = shopFlexdiscountApp::get('system')['wa']->getUser();
        if (!$user->isAdmin() && !$user->getRights("shop", "flexdiscount_rules")) {
            throw new waRightsException();
        }
    }

    public function execute()
    {
        $id = waRequest::post("id", 0, waRequest::TYPE_INT);
        $data = waRequest::post("data");
        $params = waRequest::post("params");

        $discount = (new shopFlexdiscountPluginModel())->getDiscount($id);

        // Символьный код
        $data['code'] = !empty($data['code']) ? preg_replace("/[^a-zA-Z0-9\-_]/", "", $data['code']) : '';
        // Статус
        $data['status'] = isset($data['status']) ? $data['status'] : 0;

        // Проверка условий на наличие купонов и обработка купонов
        $coupon_model = new shopFlexdiscountCouponPluginModel();
        $coupon_discount_model = new shopFlexdiscountCouponDiscountPluginModel();
        // Получаем информацию об использовании купонов в условиях
        $conditions = shopFlexdiscountConditions::decode($data['conditions']);
        $coupon_codes = array();
        if (!empty($conditions->conditions)) {
            $coupon_codes = $this->findCoupons($conditions->conditions);
            if ($coupon_codes) {
                // Активируем использование купонов
                $params['rule_has_coupon'] = 1;

                // Получаем существующие купоны
                $coupons = $coupon_model->getByField('code', $coupon_codes, 'code');
                foreach ($coupon_codes as $coupon_code) {
                    $coupon_discount = array();
                    // Если купон не существует, создаем новый
                    if (!isset($coupons[$coupon_code])) {
                        $new_coupon = array(
                            "code" => $coupon_code,
                            "create_datetime" => date("Y-m-d H:i:s"),
                            "comment" => '',
                            "limit" => ''
                        );
                        if ($coupon_id = $coupon_model->save($new_coupon)) {
                            $coupon_discount =  array("coupon_id" => $coupon_id, "fl_id" => $discount['id']);
                        }
                    } else {
                        // Существующие купоны присваваем правилу
                        $coupon_discount =  array("coupon_id" => $coupons[$coupon_code]['id'], "fl_id" => $discount['id']);
                    }
                    $coupon_discount_model->insert($coupon_discount, 2);
                }
            }
        }

        // Получаем информацию об использовании купонов в старых условиях
        if (!empty($discount['conditions'])) {
            $old_conditions = shopFlexdiscountConditions::decode($discount['conditions']);
            if (!empty($old_conditions->conditions)) {
                $old_coupon_codes = $this->findCoupons($old_conditions->conditions);
                if ($old_coupon_codes) {
                    $deleted_coupon_codes = array_diff($old_coupon_codes, $coupon_codes);
                    $deleted_coupons = $coupon_model->getByField('code', $deleted_coupon_codes, 'id');

                    if (!empty($deleted_coupons)) {
                        // Удаляем привязку у купонов, которые больше не используются
                        $coupon_discount_model->deleteByField(array('fl_id' => $discount['id'], 'coupon_id' => array_keys($deleted_coupons)));
                        // Удаляем купоны, которые раньше использовались правилом, а сейчас не используются нигде
                        $sql = "SELECT c.id FROM {$coupon_model->getTableName()} c LEFT JOIN {$coupon_discount_model->getTableName()} cd ON cd.coupon_id = c.id WHERE c.id IN(i:coupons) AND cd.coupon_id IS NULL";
                        $deleted_coupon_ids = $coupon_model->query($sql, array('coupons' => array_keys($deleted_coupons)))->fetchAll(null, true);
                        $coupon_model->deleteById($deleted_coupon_ids);
                    }

                    // Отключаем использование купонов
                    if (!$coupon_codes) {
                        $params['rule_has_coupon'] = 0;
                    }
                }
            }
        }

        $model = new shopFlexdiscountPluginModel();
        // Основные данные
        if ($id) {
            $model->updateById($id, $data);
        } else {
            $data['sort'] = $model->getMaxSort() + 1;
            $id = $model->insert($data);
        }

        setlocale(LC_NUMERIC, 'en_US.utf8');
        // Параметры
        if ($id) {
            $float_params = array('discount_percentage', 'discount', 'affiliate', 'affiliate_percentage', 'maximum_affiliate');
            foreach ($float_params as $p) {
                if (!empty($params[$p])) {
                    $params[$p] = shopFlexdiscountApp::getFunction()->floatVal($params[$p]);
                }
            }
            if (!empty($params['maximum']['value'])) {
                $params['maximum']['value'] = shopFlexdiscountApp::getFunction()->floatVal($params['maximum']['value']);
            }
            if (!empty($params['limit']['value'])) {
                $params['limit']['value'] = shopFlexdiscountApp::getFunction()->floatVal($params['limit']['value']);
            }
            $serialize_conditions = array(
                array('key' => 'available', 'value' => 'filter_by'),
                array('key' => 'available', 'value' => 'ignore_deny'),
            );
            foreach ($serialize_conditions as $v) {
                $params[$v['key']][$v['value']] = !empty($params[$v['key']][$v['value']]) ? serialize($params[$v['key']][$v['value']]) : '';
            }
            (new shopFlexdiscountParamsPluginModel())->add($id, $params);
            $this->response = $id;
        }
    }

    /**
     * Find using coupons in conditions
     *
     * @param stdClass $conditions
     * @return array
     */
    private function findCoupons($conditions)
    {
        $coupons = array();
        if ($conditions) {
            foreach ($conditions as $c) {
                // Если перед нами группа скидок, разбираем ее
                if (isset($c->group_op)) {
                    $coupons = array_merge($coupons, $this->findCoupons($c->conditions));
                } elseif ($c->type == 'coupon' && $c->op == 'eq') {
                    $coupons[] = $c->value;
                }
            }
        }
        return array_unique($coupons);
    }

}

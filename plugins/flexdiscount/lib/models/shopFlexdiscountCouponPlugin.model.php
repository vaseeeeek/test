<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopFlexdiscountCouponPluginModel extends shopFlexdiscountPluginModelHelper
{

    protected $alias = 'c';
    protected $table = 'shop_flexdiscount_coupon';

    /**
     * Get coupon info
     *
     * @param int $id
     * @return array
     */
    public function getCoupon($id)
    {
        $coupon_discount = new shopFlexdiscountCouponDiscountPluginModel();
        $sql = "SELECT c.*, GROUP_CONCAT(DISTINCT cd.fl_id SEPARATOR ',') as fl_id FROM {$this->table} c "
            . "LEFT JOIN {$coupon_discount->getTableName()} cd ON c.id = cd.coupon_id "
            . "WHERE c.id = '" . (int) $id . "'";
        $coupon = $this->query($sql)->fetchAssoc();
        if ($coupon['fl_id']) {
            $coupon['fl_id'] = explode(",", $coupon['fl_id']);
        }

        // Получаем заказы, в которых участвовал купон
        $sfcom = new shopFlexdiscountCouponOrderPluginModel();
        $orders = $sfcom->getCouponOrders($id);
        $coupon['orders'] = $orders;
        return $coupon;
    }

    /**
     * Get coupons data
     *
     * @param array $filter
     * @return array
     */
    public function getCouponsByFilter($filter)
    {
        $sql = "SELECT c.* FROM {$this->table} c WHERE 1=1 ";

        $sql .= $this->addFilterValues($filter, array(
            'id' => array('func' => 'empty', 'type' => 'int'),
            'not_id' => array('func' => 'empty', 'type' => 'int', 'key' => 'id', 'not' => true),
            'type' => array('func' => 'empty', 'type' => 'string'),
        ));
        $sql .= $this->addLimit($filter);

        $coupons = $this->query($sql)->fetchAll('id');

        if ($coupons) {
            $rule_ids = (new shopFlexdiscountCouponDiscountPluginModel())->getCouponDiscountIds(array_keys($coupons));
            foreach ($rule_ids as $coupon_id => $ids) {
                $coupons[$coupon_id]['fl_id'] = $ids;
            }
        }

        return $coupons;
    }

    /**
     * Generate coupon code
     *
     * @param string $alphabet
     * @param int $length
     * @return string
     */
    public static function generateCode($alphabet, $length)
    {
        $result = '';
        while (strlen($result) < $length) {
            $result .= $alphabet[mt_rand(0, strlen($alphabet) - 1)];
        }
        return $result;
    }

    /**
     * Get user limit for using the coupon
     *
     * @param int $coupon_id
     * @param array $order_params
     * @return bool|mixed
     */
    public function getUserLimit($coupon_id, $order_params)
    {
        $contact_id = $order_params['contact']->getId();
        $order_id = isset($order_params['order']['id']) ? (int) $order_params['order']['id'] : 0;
        $sfcom = new shopFlexdiscountCouponOrderPluginModel();
        $som = new shopOrderModel();
        $sql = "SELECT COUNT(som.id) FROM {$sfcom->getTableName()} sfcom 
                LEFT JOIN {$som->getTableName()} som ON sfcom.order_id = som.id
                WHERE som.contact_id = '" . (int) $contact_id . "' 
                        AND sfcom.coupon_id = '" . (int) $coupon_id . "' 
                        AND som.id <> '" . $order_id . "'
                        AND som.state_id NOT IN ('deleted', 'refunded')";
        return $this->query($sql)->fetchField();
    }

    /**
     * Save coupon
     *
     * @param array $coupon
     * @return bool|int
     */
    public function save($coupon)
    {
        $coupon_id = $this->insert($coupon);

        if (!empty($coupon['code'])) {
            // Связываем созданный купон с обезличенными купонами, которые присвоены заказам
            $com = new shopFlexdiscountCouponOrderPluginModel();
            if ($com->getByField(array("code" => $coupon['code'], "coupon_id" => 0))) {
                $com->updateByField(array("code" => $coupon['code'], "coupon_id" => 0), array("coupon_id" => $coupon_id));
            }
        }

        return $coupon_id;
    }

    /**
     * Delete coupon and discount relations
     *
     * @param array[int]|int $coupon_id
     * @param int $fl_id
     * @return bool
     */
    public function delete($coupon_id, $fl_id = 0)
    {
        if ($coupon_id) {
            $cdm = new shopFlexdiscountCouponDiscountPluginModel();

            // Если необходимо удалить принадлежность купона к скидке, но не сам купон
            if ($fl_id) {
                $cdm->deleteByField(array("coupon_id" => $coupon_id, "fl_id" => $fl_id));
            } else {
                // Обезличивание купонов у заказов
                $sfcom = new shopFlexdiscountCouponOrderPluginModel();
                $sfcom->updateByField(array("coupon_id" => $coupon_id), array("coupon_id" => 0));
                // Удаляем купон безвозвратно
                $cdm->deleteByField(array("coupon_id" => $coupon_id));
                $this->deleteById($coupon_id);
            }
        }
        return true;
    }

    /**
     * Increment coupon used
     *
     * @param int $coupon_id - coupon ID
     * @param int $clean_coupon
     */
    public function useOne($coupon_id, $clean_coupon = 0)
    {
        $sql = "UPDATE {$this->table} SET used = used + 1 WHERE id = :id";
        $this->exec($sql, array('id' => $coupon_id));
        // Если необходимо удалять неиспользуемые купоны
        if ($clean_coupon) {
            $coupon = $this->getById($coupon_id);
            if ($coupon && shopFlexdiscountHelper::getCouponStatus($coupon) === -3) {
                $this->delete($coupon_id);
            }
        }
    }

    /**
     * Get coupons and generatirs count for discount
     *
     * @param int $rule_id - rule ID
     * @param int $clean_coupon
     *
     * @return array
     */
    public function getCoupons($rule_id, $clean_coupon = 0)
    {
        $cdm = new shopFlexdiscountCouponDiscountPluginModel();
        // Количество купонов у правила
        $sql = "SELECT COUNT(cm.id) FROM {$this->table} cm 
                LEFT JOIN {$cdm->getTableName()} cdm ON cdm.coupon_id = cm.id 
                WHERE cdm.fl_id = '" . (int) $rule_id . "' AND cm.type = 'coupon'";
        // Количество генераторов у правила
        $sql2 = "SELECT COUNT(cm.id) FROM {$this->table} cm 
                LEFT JOIN {$cdm->getTableName()} cdm ON cdm.coupon_id = cm.id 
                WHERE cdm.fl_id = '" . (int) $rule_id . "' AND cm.type = 'generator'";

        // Удаляем неиспользуемые купоны
        // Если срок действия купонов истек или достигнут предел по количеству использований купона
        $sql_expired = "(`end` IS NOT NULL AND UNIX_TIMESTAMP(NOW()) > UNIX_TIMESTAMP(`end`))";
        $sql_limit = "(`limit` > 0 AND `used` >= `limit`)";
        if ($clean_coupon && $this->query("SELECT COUNT(*) FROM {$this->getTableName()} WHERE {$sql_expired} OR {$sql_limit}")->fetchField()) {
            // Обезличивание купонов у заказов
            $sfcom = new shopFlexdiscountCouponOrderPluginModel();
            $sfcom->exec("UPDATE {$sfcom->getTableName()} sfcom  SET sfcom.coupon_id = 0 
                          WHERE sfcom.coupon_id IN(SELECT id FROM {$this->getTableName()} WHERE {$sql_expired} OR {$sql_limit})");
            // Удаляем купон безвозвратно
            $cdm->exec("DELETE cdm FROM {$cdm->getTableName()} cdm 
                        WHERE cdm.coupon_id IN (SELECT id FROM {$this->getTableName()} WHERE {$sql_expired} OR {$sql_limit})");
            $this->exec("DELETE FROM {$this->getTableName()} WHERE {$sql_expired} OR {$sql_limit}");
        }

        return array("coupons" => $this->query($sql)->fetchField(), "generators" => $this->query($sql2)->fetchField());
    }

    /**
     * Get coupon by order ID
     *
     * @param int $order_id
     * @return array
     */
    public function getCouponsByOrderId($order_id)
    {
        $com = new shopFlexdiscountCouponOrderPluginModel();
        $sql = "SELECT * FROM {$com->getTableName()} WHERE order_id = '" . (int) $order_id . "'";
        return $com->query($sql)->fetchAll();
    }

    /**
     * Check if coupon exists
     *
     * @param string $coupon_code
     * @return array|null
     * @throws waException
     */
    public function issetCoupon($coupon_code)
    {
        return $this->getByField('code', $coupon_code);
    }

    /**
     * Filter array of coupons. Get coupons, which not exists
     *
     * @param array $coupons - coupon codes should be the keys of this array
     * @return array
     */
    public function filterByNew($coupons)
    {
        $sql = "SELECT code FROM {$this->table} WHERE code IN ('" . implode("','", array_keys($coupons)) . "')";
        foreach ($this->query($sql) as $r) {
            if (isset($coupons[$r['code']])) {
                unset($coupons[$r['code']]);
            }
        }
        return $coupons;
    }

}

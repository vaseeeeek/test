<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopFlexdiscountAffiliatePluginModel extends waModel
{

    protected $table = 'shop_flexdiscount_affiliate';

    /**
     * Get contact affiliate bonus by order_id
     *
     * @param int $order_id
     * @return array|false
     */
    public function getByOrder($order_id)
    {
        return $this->getByField("order_id", (int) $order_id);
    }

    /**
     * Set status of order equal to done
     *
     * @param int $order_id
     * @return bool
     */
    public function done($order_id)
    {
        return $this->updateByField("order_id", (int) $order_id, array("status" => 1));
    }

    /**
     * Set status of order equal to process
     *
     * @param int $order_id
     * @return bool
     */
    public function cancel($order_id)
    {
        return $this->updateByField("order_id", (int) $order_id, array("status" => 0));
    }

    /**
     * Check if order is enable to add affiliate bonus
     *
     * @param int $order_id
     * @return int
     */
    public function isEnabled($order_id)
    {
        $status = $this->select("status")->where("order_id = '" . (int) $order_id . "'")->fetchField();
        return $status ? 0 : 1;
    }

    /**
     * Save bonuses, if we want to apply them after changing the order state
     *
     * @param array $order_data
     * @param float $bonuses
     */
    public function saveBonuses($order_data, $bonuses)
    {
        if (!empty($order_data['order_id']) && shopAffiliate::isEnabled()) {
            $app = new shopFlexdiscountApp();
            $order = $app->set('order.full', $app::getOrder()->updateOrder($order_data['order_id']));
            $contact_id = ($order['contact'] ? ($order['contact']->getId() ? $order['contact']->getId() : 0) : 0);
            if ($contact_id) {
                $this->insert(array(
                    "contact_id" => $contact_id,
                    "order_id" => (int) $order_data['order_id'],
                    "affiliate" => (float) $bonuses,
                    "status" => 0
                ), 1);
            }
        }
    }

    /**
     * Update bonuses
     *
     *
     * @param int $order_id
     * @param string $type
     * @param string $message
     * @param string $action_id
     * @return  bool
     */
    public function updateBonuses($order_id, $type, $message, $action_id = '')
    {
        $isEnabled = $this->isEnabled($order_id);
        if (($type == 'cancel' && $isEnabled) || ($type == 'done' && !$isEnabled)) {
            return false;
        }
        $affiliate = $this->getByOrder($order_id);
        if ($affiliate) {
            // Обновляем количество бонусов у пользователя
            // Избегаем двойного начисления бонусов при восстановлении заказа, если бонусы не были начислены вообще
            if ($action_id == 'restore') {
                $order = (new shopOrderModel())->getById($order_id);
                if (empty($order) || ($order && !$order['paid_date'])) {
                    return false;
                }
            }
            $shop_aff_trans = new shopAffiliateTransactionModel();
            $shop_aff_trans->applyBonus($affiliate['contact_id'], $type == 'done' ? $affiliate['affiliate'] : (-1) * $affiliate['affiliate'], $order_id, $message);
            if ($type == 'done') {
                return $this->done($order_id);
            } else {
                return $this->cancel($order_id);
            }
        }
        return true;
    }
}

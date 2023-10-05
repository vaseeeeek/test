<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopFlexdiscountCouponOrderPluginModel extends waModel
{

    protected $table = 'shop_flexdiscount_coupon_order';

    /**
     * Get coupon orders
     * 
     * @param int $id
     * 
     * @return array
     */
    public function getCouponOrders($id)
    {
        $orders = array();
        $shop_order_model = new shopOrderModel();
        $sql = "SELECT o.*, so.state_id, so.currency FROM {$this->table} o
                LEFT JOIN {$shop_order_model->getTableName()} so ON so.id = o.order_id WHERE o.coupon_id = '" . (int) $id . "' ORDER BY o.datetime";
        $workflow = new shopWorkflow();
        $states = $workflow->getAllStates();
        $result = $this->query($sql);
        if ($result) {
            foreach ($result as $k => $r) {
                $orders[$k] = $r;
                $state = isset($states[$r['state_id']]) ? $states[$r['state_id']] : null;
                $icon = '';
                $style = '';
                if ($state) {
                    $icon = $state->getOption('icon');
                    $style = $state->getStyle();
                }
                $orders[$k]['icon'] = $icon;
                $orders[$k]['style'] = $style;
            }
        }
        return $orders;
    }

}

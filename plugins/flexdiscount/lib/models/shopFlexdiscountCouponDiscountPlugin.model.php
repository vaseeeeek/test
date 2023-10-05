<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopFlexdiscountCouponDiscountPluginModel extends waModel
{

    protected $table = 'shop_flexdiscount_coupon_discount';

    /**
     * @param array[int] $coupon_ids
     * @return array
     */
    public function getCouponDiscountIds($coupon_ids)
    {
        return $this->select('coupon_id, fl_id')->where('coupon_id IN (?)', [$coupon_ids])->fetchAll('coupon_id', 2);
    }
}

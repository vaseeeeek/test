<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopFlexdiscountPluginMarketingLoadCouponsController extends waJsonController
{
    const PER_PAGE = 50;

    public function execute()
    {
        $page = waRequest::post('page', 1);
        $ignore_ids = waRequest::post('ignore', []);
        $coupons = (new shopFlexdiscountCouponPluginModel())->getCouponsByFilter([
            'type' => 'coupon',
            'not_id' => $ignore_ids,
            'limit' => ['offset' => self::PER_PAGE * ($page - 1), 'length' => self::PER_PAGE]
        ]);
        $helper = new shopFlexdiscountHelper();
        foreach ($coupons as &$coupon) {
            $coupon = $helper->prepareCouponForJS($coupon);
        }
        $this->response = $coupons;
    }
}
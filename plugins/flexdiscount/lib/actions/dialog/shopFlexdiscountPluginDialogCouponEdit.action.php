<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopFlexdiscountPluginDialogCouponEditAction extends waViewAction
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
        $type = waRequest::get("type", 'coupon');
        $f_id = waRequest::get("f_id", 0, waRequest::TYPE_INT);
        $coupon_id = waRequest::get("id", 0, waRequest::TYPE_INT);

        if ($coupon_id) {
            $coupon = (new shopFlexdiscountCouponPluginModel())->getCoupon($coupon_id);

            // Время жизни купона
            if ($coupon['lifetime']) {
                $day = floor($coupon['lifetime'] / 86400);
                $hour = floor(($coupon['lifetime'] - $day * 86400) / 3600);
                $minute = floor(($coupon['lifetime'] - $day * 86400 - $hour * 3600) / 60);
                $coupon['days'] = array(
                    'day' => $day,
                    'hour' => $hour,
                    'minute' => $minute
                );
            }

            $type = $coupon['type'];
            $this->view->assign("coupon", $coupon);
        }

        $this->view->assign("type", $type);
        $this->view->assign("f_id", $f_id);
        $this->view->assign('plugin_url', shopFlexdiscountApp::get('system')['wa']->getPlugin('flexdiscount')->getPluginStaticUrl());
    }

}

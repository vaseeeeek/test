<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopFlexdiscountPluginCouponsDeleteController extends waJsonController
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
        if (waRequest::method() == 'post') {
            $id = waRequest::post("id", 0, waRequest::TYPE_INT);
            $fl_id = waRequest::post("fl_id", 0, waRequest::TYPE_INT);
            if ($id) {
                if (!(new shopFlexdiscountCouponPluginModel())->delete($id, $fl_id)) {
                    $this->errors = _wp("Delete error");
                }
            } else {
                $this->errors = _wp("Coupon ID is empty");
            }
        }
    }

}

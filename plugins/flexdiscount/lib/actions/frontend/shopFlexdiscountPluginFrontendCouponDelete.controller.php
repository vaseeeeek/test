<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopFlexdiscountPluginFrontendCouponDeleteController extends shopFlexdiscountPluginJsonController
{

    public function execute()
    {
        $this->getStorage()->del("flexdiscount-coupon");
    }

}

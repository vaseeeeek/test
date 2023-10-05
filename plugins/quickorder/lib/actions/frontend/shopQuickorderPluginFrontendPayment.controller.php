<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopQuickorderPluginFrontendPaymentController extends waController
{

    public function execute()
    {
        if ($order_id = wa()->getStorage()->get('shop_quickorder/order_id')) {
            $order_class = new shopQuickorderPluginOrder();
            $order_class->success($order_id);
        }
    }

}

<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopQuickorderPluginFrontendSendController extends waJsonController
{

    public function execute()
    {   
        $order_class = new shopQuickorderPluginOrder();
        $order = $order_class->prepareOrder($this->errors);
        
        if (!$this->errors) {
            // Создаем новый заказ
            if ($order_id = $order_class->createOrder($order)) {
                $this->response = $order_class->success($order_id);
            } else {
                $this->errors[] = _wp('Cannot create order');
            }
        }
    }

}

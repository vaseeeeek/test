<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

$model = new waModel();
try {
    $model->exec("ALTER TABLE `shop_flexdiscount_affiliate` CHANGE `affiliate` `affiliate` FLOAT (14,2) NOT NULL");
} catch (waDbException $e) {

}
try {
    $model->exec("ALTER TABLE `shop_flexdiscount_coupon_order` CHANGE `affiliate` `affiliate` FLOAT (14,2) NOT NULL");
} catch (waDbException $e) {

}

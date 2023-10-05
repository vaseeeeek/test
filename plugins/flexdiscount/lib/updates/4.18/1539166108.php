<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */
$model = new waModel();
try {
    $model->exec("SELECT user_limit FROM shop_flexdiscount_coupon WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_flexdiscount_coupon ADD user_limit INT(11) NOT NULL DEFAULT '0'");
}

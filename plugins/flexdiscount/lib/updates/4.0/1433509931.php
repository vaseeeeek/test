<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */
$model = new waModel();
try {
    $model->exec("SELECT lifetime FROM shop_flexdiscount_coupon WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_flexdiscount_coupon ADD lifetime VARCHAR (20) NOT NULL DEFAULT ''");
}
try {
    $model->exec("SELECT frontend_sort FROM shop_flexdiscount WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_flexdiscount ADD frontend_sort INT (11) NOT NULL DEFAULT '-1'");
}
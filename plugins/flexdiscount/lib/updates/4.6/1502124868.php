<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */
$model = new waModel();
try {
    $model->exec("SELECT create_datetime FROM shop_flexdiscount_coupon WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_flexdiscount_coupon ADD create_datetime DATETIME DEFAULT NULL");
}

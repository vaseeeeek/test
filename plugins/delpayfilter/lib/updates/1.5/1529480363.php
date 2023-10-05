<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

$model = new waModel();

try {
    $model->exec("SELECT check_email FROM shop_delpayfilter WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_delpayfilter ADD check_email TINYINT(1) NOT NULL DEFAULT '0'");
}
try {
    $model->exec("SELECT check_phone FROM shop_delpayfilter WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_delpayfilter ADD check_phone TINYINT(1) NOT NULL DEFAULT '0'");
}
<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */
$model = new waModel();
try {
    $model->exec("SELECT status FROM shop_flexdiscount WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_flexdiscount ADD status TINYINT (1) NOT NULL DEFAULT '1'");
}
try {
    $model->exec("SELECT description FROM shop_flexdiscount WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_flexdiscount ADD description TEXT");
}
try {
    $model->exec("SELECT conditions FROM shop_flexdiscount WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_flexdiscount ADD conditions TEXT");
}
try {
    $model->exec("SELECT target FROM shop_flexdiscount WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_flexdiscount ADD target TEXT");
}
try {
    $model->exec("SELECT deny FROM shop_flexdiscount WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_flexdiscount ADD deny TINYINT (1) NOT NULL DEFAULT '0'");
}
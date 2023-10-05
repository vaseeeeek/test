<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */
$model = new waModel();

try {
    $model->exec("SELECT inc_id FROM shop_productsets_items WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_productsets_items ADD inc_id INT (11) NOT NULL AUTO_INCREMENT UNIQUE FIRST");
}
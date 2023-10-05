<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */
$model = new waModel();

try {
    $model->exec("SELECT sort_id FROM shop_productsets_items WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE shop_productsets_items ADD sort_id INT (11) NOT NULL DEFAULT '0'");
}
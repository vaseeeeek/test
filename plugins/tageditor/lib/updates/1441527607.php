<?php

$model = new waModel();
try {
    $model->query('SELECT sort_products FROM shop_tageditor_tag WHERE 0');
} catch (waDbException $e) {
    $model->exec('ALTER TABLE `shop_tageditor_tag` ADD `sort_products` VARCHAR(32) NULL DEFAULT NULL');
}

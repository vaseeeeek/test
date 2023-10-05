<?php

$model = new waModel();

try {
    $model->exec('ALTER TABLE `shop_tageditor_tag` CHANGE `edit_datetime` `edit_datetime` DATETIME NULL DEFAULT NULL');
    $model->exec('UPDATE shop_tageditor_tag SET edit_datetime = IFNULL(edit_datetime, NOW())');
} catch (Exception $e) {
    //
}

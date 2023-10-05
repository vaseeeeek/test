<?php

$model = new waModel();

//edit_datetime
try {
    $model->exec('SELECT edit_datetime FROM shop_tageditor_tag WHERE 0');
} catch (Exception $e) {
    $model->exec('ALTER TABLE `shop_tageditor_tag` ADD `edit_datetime` DATETIME NULL DEFAULT NULL');
    $model->exec('UPDATE shop_tageditor_tag SET edit_datetime = NOW()');
}

//description_extra
try {
    $model->exec('SELECT description_extra FROM shop_tageditor_tag WHERE 0');
} catch (Exception $e) {
    $model->exec("ALTER TABLE shop_tageditor_tag ADD description_extra TEXT NOT NULL AFTER description");
}

//title
try {
    $model->exec('SELECT title FROM shop_tageditor_tag WHERE 0');
} catch (Exception $e) {
    $model->exec("ALTER TABLE shop_tageditor_tag ADD title TEXT NOT NULL AFTER url");
}

<?php

$model = new waModel();
try {
    $model->query('SELECT description FROM shop_tageditor_tag WHERE 0');
} catch (waDbException $e) {
    $model->exec('ALTER TABLE shop_tageditor_tag ADD description TEXT NOT NULL');
}
$model->exec('ALTER TABLE shop_tageditor_tag CHANGE meta_title meta_title VARCHAR(255) NOT NULL');

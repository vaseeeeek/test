<?php

$model = new waModel();
try {
    $model->query('SELECT url FROM shop_tageditor_tag WHERE 0');
} catch (waDbException $e) {
    $sql = 'ALTER TABLE shop_tageditor_tag ADD url VARCHAR(255) NOT NULL';
    $model->exec($sql);
}

<?php

$model = new waModel();
try {
    $model->query("SELECT image FROM shop_category WHERE 0");
    $model->exec("ALTER TABLE shop_category DROP image");
} catch (waDbException $e) {
}


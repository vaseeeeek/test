<?php

$model = new waModel();

try {
    $model->exec('SELECT og_title FROM shop_tageditor_tag WHERE 0');
} catch (Exception $e) {
    $model->exec('ALTER TABLE `shop_tageditor_tag` ADD `og_title` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `meta_keywords`;');
}

try {
    $model->exec('SELECT og_description FROM shop_tageditor_tag WHERE 0');
} catch (Exception $e) {
    $model->exec('ALTER TABLE `shop_tageditor_tag` ADD `og_description` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `og_title`;');
}

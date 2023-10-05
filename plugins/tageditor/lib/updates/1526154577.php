<?php

waFiles::delete(wa()->getAppPath('plugins/tageditor/lib/config/routing.php', 'shop'));

$model = new waModel();

try {
    $model->exec('ALTER TABLE `shop_tageditor_tag` CHANGE `meta_title` `meta_title` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL');
} catch (Exception $e) {
    //
}

try {
    $model->exec('ALTER TABLE `shop_tageditor_tag` CHANGE `meta_description` `meta_description` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL');
} catch (Exception $e) {
    //
}

try {
    $model->exec('ALTER TABLE `shop_tageditor_tag` CHANGE `meta_keywords` `meta_keywords` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL');
} catch (Exception $e) {
    //
}

try {
    $model->exec('ALTER TABLE `shop_tageditor_tag` CHANGE `og_title` `og_title` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL');
} catch (Exception $e) {
    //
}

try {
    $model->exec('ALTER TABLE `shop_tageditor_tag` CHANGE `og_description` `og_description` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL');
} catch (Exception $e) {
    //
}

try {
    $model->exec('ALTER TABLE `shop_tageditor_tag` CHANGE `url` `url` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL');
} catch (Exception $e) {
    //
}

try {
    $model->exec('ALTER TABLE `shop_tageditor_tag` CHANGE `title` `title` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL');
} catch (Exception $e) {
    //
}

try {
    $model->exec('ALTER TABLE `shop_tageditor_tag` CHANGE `description` `description` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL');
} catch (Exception $e) {
    //
}

try {
    $model->exec('ALTER TABLE `shop_tageditor_tag` CHANGE `description_extra` `description_extra` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL');
} catch (Exception $e) {
    //
}

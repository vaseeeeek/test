<?php

$model = new waModel();

try {
    $model->exec(
        'CREATE TABLE IF NOT EXISTS `shop_tageditor_index_product_tags` (
            `product_id` int(11) UNSIGNED NOT NULL,
            `tag_id` int(11) UNSIGNED NOT NULL,
            `type_id` int(11) UNSIGNED NOT NULL,
            PRIMARY KEY (`product_id`,`tag_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8'
    );
} catch (Exception $e) {
    //
}

try {
    $model->exec(
        'CREATE TABLE IF NOT EXISTS `shop_tageditor_index_tag` (
            `tag_id` int(11) UNSIGNED NOT NULL,
            `type_id` int(11) UNSIGNED NOT NULL,
            `count` int(11) UNSIGNED NOT NULL,
            UNIQUE KEY `tag_type` (`tag_id`,`type_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;'
    );
} catch (Exception $e) {
    //
}

$plugin_path = wa()->getAppPath('plugins/tageditor', 'shop');
waFiles::delete($plugin_path.'/js/tageditor.js');
waFiles::delete($plugin_path.'/js/tageditor-menu.js');
waFiles::delete($plugin_path.'/templates/includes/settings', true);

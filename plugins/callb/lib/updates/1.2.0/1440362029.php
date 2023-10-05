<?php

/**
 * @author Max Severin <makc.severin@gmail.com>
 */

$plugin_id = array('shop', 'callb');

$model = new waModel();


// 1) replacement from show_deleted setting to show_done:

$app_settings_model = new waAppSettingsModel();
$show_deleted = $app_settings_model->get($plugin_id, 'show_deleted');

$app_settings_model->set($plugin_id, 'show_done', $show_deleted);
$app_settings_model->del($plugin_id, 'show_deleted');

$model->query("UPDATE `shop_callb_request` SET `status` = 'done' WHERE `status` = 'del'");


// 2) adding a new url param to request table:

try {
    $model->query("SELECT `url` FROM `shop_callb_request` WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE `shop_callb_request` ADD `url` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL");
}


// 3) adding a new comment param to request table:

$app_settings_model->set($plugin_id, 'comment_status', 'off');
$app_settings_model->set($plugin_id, 'text_comment_placeholder', _wp('Your comment'));

try {
    $model->query("SELECT `comment` FROM `shop_callb_request` WHERE 0");
} catch (waDbException $e) {
    $model->exec("ALTER TABLE `shop_callb_request` ADD `comment` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL");
}
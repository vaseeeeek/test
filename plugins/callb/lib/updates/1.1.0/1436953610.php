<?php

/**
 * @author Max Severin <makc.severin@gmail.com>
 */
$plugin_id = array('shop', 'callb');

$app_settings_model = new waAppSettingsModel();

$app_settings_model->set($plugin_id, 'frontend_head_status',         'on');
$app_settings_model->set($plugin_id, 'phone_masked_input',           '');
$app_settings_model->set($plugin_id, 'style_close_ok_background',    '4d9b58');
$app_settings_model->set($plugin_id, 'style_close_error_background', 'de4d2c');
$app_settings_model->set($plugin_id, 'text_thanks_message',          _wp('Thanks') . ',');
$app_settings_model->set($plugin_id, 'text_more_thanks_message',     _wp('your message has been sent!'));
$app_settings_model->set($plugin_id, 'style_thanks_text_color',      '717171');
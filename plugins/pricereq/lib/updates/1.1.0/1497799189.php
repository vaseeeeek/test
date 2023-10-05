<?php

/**
 * @author Max Severin <makc.severin@gmail.com>
 */
$plugin_id = array('shop', 'pricereq');

$app_settings_model = new waAppSettingsModel();

$app_settings_model->set($plugin_id, 'privacy_status',           'off');
$app_settings_model->set($plugin_id, 'privacy_text',             _wp('Clicking on the «Send» button, I give my'));
$app_settings_model->set($plugin_id, 'privacy_link_text',        _wp('consent to the personal data processing'));
$app_settings_model->set($plugin_id, 'privacy_link_url',         '/site/privacy-policy/');
$app_settings_model->set($plugin_id, 'privacy_checkbox_status',  'on');
$app_settings_model->set($plugin_id, 'privacy_checkbox_checked', 'unchecked');
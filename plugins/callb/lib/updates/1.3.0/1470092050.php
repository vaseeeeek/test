<?php

/**
 * @author Max Severin <makc.severin@gmail.com>
 */
$plugin_id = array('shop', 'callb');

$app_settings_model = new waAppSettingsModel();

$app_settings_model->set($plugin_id, 'callb_request_limit', '10');
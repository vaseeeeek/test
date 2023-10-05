<?php
$settings_model = new waAppSettingsModel();
$settings_model->set(array('shop', 'emailform'), 'start', date('Y-m-d H:i:s'));

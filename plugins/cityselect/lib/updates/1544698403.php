<?php

//Обновление настроек
$settings = $this->getSettings();

foreach ($settings['routes'] as $key => $route_setting) {
    $settings['routes'][$key]['in_custom_form'] = '.wa-signup-form-fields,.quickorder-form,#storequickorder .wa-form';
    $settings['routes'][$key]['in_custom_city'] = '';
}

$this->saveSettings($settings);

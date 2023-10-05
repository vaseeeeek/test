<?php

//Обновление настроек
$settings = $this->getSettings();

foreach ($settings['routes'] as $key => $route_setting) {
    $settings['routes'][$key]['plugin_dp'] = 0;
}

$this->saveSettings($settings);

<?php

/**
 * Установка начальных значений
 *
 * @author Steemy, created by 23.03.2018
 * @link http://steemy.ru/
 */

/**
 * Получаем настрйоки по умолчанию
 */
$pluginSetting = shopClicklitePluginSettings::getInstance();

$settings = array();
$pluginSetting->getSettingsCheck($settings);
$namePlugin = $pluginSetting->namePlugin;
$appSettingsModel = $pluginSetting->appSettingsModel;

/**
 * Устанавливаем настройки для плагина по умолчанию
 */
foreach($settings as $key=>$value) {
    if(is_array($value)) {
        $value = json_encode($value);
    }
    $appSettingsModel->set(array('shop', $namePlugin), $key, $value);
}
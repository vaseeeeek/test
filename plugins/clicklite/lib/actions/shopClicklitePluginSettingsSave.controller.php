<?php

/**
 * Класс сохранения настроек бекенд
 *
 * @author Steemy, created by 03.04.2018
 * @link http://steemy.ru/
 */

class shopClicklitePluginSettingsSaveController extends waJsonController
{

    public function execute()
    {
        $pluginSetting = shopClicklitePluginSettings::getInstance();
        $namePlugin = $pluginSetting->namePlugin;

        $settings = waRequest::post('shop_plugins', array());
        $pluginSetting->getSettingsCheck($settings);

        try {
            $settingsFile = waRequest::post('shop_plugins_file', array());
            $pluginSetting->saveFileSettings($settingsFile);

            $plugin = waSystem::getInstance()->getPlugin($namePlugin);
            $plugin->saveSettings($settings);
        } catch (Exception $e) {
            $this->errors['messages'][] = 'Не удается сохранить поля настроек';
        }
    }
}
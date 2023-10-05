<?php

/**
 * Класс получения настроек бекенд
 *
 * @author Steemy, created by 03.04.2018
 * @link http://steemy.ru/
 */

class shopClicklitePluginSettingsAction extends waViewAction
{
    public function execute()
    {
        if(!$this->getRights('clicklite_settings'))
            throw new waRightsException();

        $pluginSetting = shopClicklitePluginSettings::getInstance();

        $settings = $pluginSetting->getSettings();
        $pluginSetting->getSettingsCheck($settings);
        $pluginSetting->addFileSetting($settings);

        $v = '';
        for($i = 0; $i < 10; $i++) {
            $v .= mt_rand(0, 9);
        }
        $settings['version'] = $v;

        $this->view->assign("settings", $settings);
    }
}

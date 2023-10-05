<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopFlexdiscountPluginSettingsAction extends waViewAction
{

    public function preExecute()
    {
        $user = shopFlexdiscountApp::get('system')['wa']->getUser();
        if (!$user->isAdmin() && !$user->getRights("shop", "flexdiscount_settings")) {
            throw new waRightsException();
        }
    }

    public function execute()
    {
        // Получаем настройки
        $settings = (new shopFlexdiscountSettingsPluginModel())->getSettings();
        // Список всех плагинов
        $plugins = shopFlexdiscountApp::get('system')['config']->getPlugins();
        unset($plugins['flexdiscount']);

        $plugin = shopFlexdiscountApp::get('system')['wa']->getPlugin('flexdiscount');
        $this->view->assign('settings', $settings);
        $this->view->assign('js_locale_strings', (new shopFlexdiscountHelper())->getJsLocaleStrings());
        $this->view->assign('enabled', shopDiscounts::isEnabled('flexdiscount'));
        $this->view->assign('plugins', $plugins);
        $this->view->assign('plugin_url', $plugin->getPluginStaticUrl());
        $this->view->assign('version', $plugin->getVersion());
    }

}

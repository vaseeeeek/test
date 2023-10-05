<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopFlexdiscountPluginDialogSystemSettingsAction extends waViewAction
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
        $system_settings = shopFlexdiscountProfile::SETTINGS;
        $settings = [];
        foreach ($system_settings as $key => $value) {
            $settings[$value] = (new waAppSettingsModel())->get(array('shop', 'flexdiscount'), $value, shopFlexdiscountProfile::DEFAULT_SETTINGS[$key]);
        }
        $plugin_settings = (new shopFlexdiscountSettingsPluginModel())->getSettings();
        $this->view->assign('settings', $settings);
        $this->view->assign('plugin_settings', $plugin_settings);
        $this->view->assign('plugin_url', shopFlexdiscountApp::get('system')['wa']->getPlugin('flexdiscount')->getPluginStaticUrl());
    }

}

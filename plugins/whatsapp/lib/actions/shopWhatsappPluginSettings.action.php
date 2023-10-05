<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopWhatsappPluginSettingsAction extends waViewAction
{

    public function execute()
    {
        $settings = include shopWhatsappPlugin::path('config.php');
        $this->view->assign('settings', $settings);
        $this->view->assign('plugin_url', wa()->getPlugin('whatsapp')->getPluginStaticUrl());
    }

}
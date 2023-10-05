<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopAutobadgePluginDialogSystemSettingsAction extends waViewAction
{

    public function execute()
    {
        // Список всех плагинов
        $plugins = wa('shop')->getConfig()->getPlugins();
        unset($plugins['autobadge']);

        $this->view->assign('plugins', $plugins);

        $this->view->assign('settings', shopAutobadgeHelper::getSettings());
        $this->view->assign('plugin_url', wa('shop')->getPlugin('autobadge')->getPluginStaticUrl());
    }

}

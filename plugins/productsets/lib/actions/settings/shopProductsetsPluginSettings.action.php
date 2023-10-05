<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopProductsetsPluginSettingsAction extends waViewAction
{

    public function execute()
    {
        $plugin = wa()->getPlugin('productsets');

        $this->view->assign('templates', (new shopProductsetsPluginHelper())->getTemplates());
        $this->view->assign('settings', $plugin->getSettings());
        $this->view->assign('plugin_url', $plugin->getPluginStaticUrl());
        $this->view->assign('version', $plugin->getVersion());
    }

}
<?php

class shopArrivedPluginSettingsAction extends waViewAction
{

    public function execute()
    {
        $settings = include shopArrivedPlugin::path('config.php');
        $this->view->assign('settings', $settings);
        $this->view->assign('plugin_url', wa()->getPlugin('arrived')->getPluginStaticUrl());
		$routing = wa()->getRouting();
		$domain_routes = $routing->getByApp('shop');
        $this->view->assign('domain_routes', $domain_routes);
    }

}
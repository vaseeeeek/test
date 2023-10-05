<?php
/**
 * Created by PhpStorm
 * User: rmjv
 * Date: 03/10/2019
 * Time: 15:50
 */

class shopCatdoplinksPluginSettingsAction extends waViewAction
{
    protected $_settings;

    public function execute()
    {
        $plugin = wa('shop')->getPlugin('catdoplinks');
        $this->_settings = $plugin->getSettings();

        $css = file_get_contents($plugin->getPath().'/css/catdoplinks.css');

        $this->view->assign('settings', $this->_settings);
        $this->view->assign('plugin', $plugin);
        $this->view->assign('css', $css);


    }
}
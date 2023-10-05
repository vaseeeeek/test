<?php

/*
 * Class shopCallbPluginSettingsAction
 * @author Max Severin <makc.severin@gmail.com>
 */
class shopCallbPluginSettingsAction extends waViewAction {

    public function execute() {

        $plugin = wa('shop')->getPlugin('callb');
        $namespace = 'shop_callb';

        $params = array();
        $params['id'] = 'callb';
        $params['namespace'] = $namespace;
        $params['title_wrapper'] = '%s';
        $params['description_wrapper'] = '<br><span class="hint">%s</span>';
        $params['control_wrapper'] = '<div class="name">%s</div><div class="value">%s %s</div>';

        $settings = $plugin->getSettings();
        $settings_controls = $plugin->getControls($params);

        $this->view->assign('callb_settings', $settings);
        $this->view->assign('settings_controls', $settings_controls);
    	$this->view->assign('callback_url', wa()->getRouteUrl('shop/frontend/callback/'));
    }

}
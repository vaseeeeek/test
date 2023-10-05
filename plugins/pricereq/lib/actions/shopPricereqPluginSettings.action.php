<?php

/*
 * Class shopPricereqPluginSettingsAction
 * @author Max Severin <makc.severin@gmail.com>
 */
class shopPricereqPluginSettingsAction extends waViewAction {

    public function execute() {

        $plugin = wa('shop')->getPlugin('pricereq');
        $namespace = 'shop_pricereq';

        $params = array();
        $params['id'] = 'pricereq';
        $params['namespace'] = $namespace;
        $params['title_wrapper'] = '%s';
        $params['description_wrapper'] = '<br><span class="hint">%s</span>';
        $params['control_wrapper'] = '<div class="name">%s</div><div class="value">%s %s</div>';

        $settings = $plugin->getSettings();
        $settings_controls = $plugin->getControls($params);

        $this->view->assign('pricereq_settings', $settings);
        $this->view->assign('settings_controls', $settings_controls);
    	$this->view->assign('pricereq_url', wa()->getRouteUrl('shop/frontend/pricereq/'));
    }

}
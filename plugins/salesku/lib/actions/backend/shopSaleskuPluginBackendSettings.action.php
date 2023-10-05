<?php

class shopSaleskuPluginBackendSettingsAction extends waViewAction {
    
    public function execute() {
        $storefront = waRequest::get('storefront');
        if(empty($storefront)) {
            $storefront = shopSaleskuPlugin::GENERAL_STOREFRONT;
        }
        $settings = shopSaleskuPlugin::getPluginSettings($storefront);
        if(waRequest::method()=='get') {
            $this->view->assign('settings', $settings);
            $this->view->assign('image_sizes', $settings->getImageSizes());
            $this->view->assign('storefront', $settings->getStorefront());
        } else {
            $data = waRequest::post();
            $settings->save($data);
        }
    }
}

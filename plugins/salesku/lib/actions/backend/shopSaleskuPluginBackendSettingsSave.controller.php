<?php

class shopSaleskuPluginBackendSettingsSaveController extends waJsonController {
    
    public function execute() {
        $storefront = waRequest::get('storefront');
        if(empty($storefront)) {
            $storefront = shopSaleskuPlugin::GENERAL_STOREFRONT;
        }
        $settings = shopSaleskuPlugin::getPluginSettings($storefront);
        if(waRequest::method()=='get') {
          $this->errors[] = 'Неправильный запрос!';
        } else {
            $data = waRequest::post();
            $settings->save($data);
            $this->response = 'ok';
        }
    }
}

<?php

class shopSaleskuPluginBackendSettingsController extends waViewController {
    
    public function execute() {
        if(waRequest::method()=='get') {
         $action = new shopSaleskuPluginBackendSettingsAction();
            echo $action->display();
        } else {
           $action = new shopSaleskuPluginBackendSettingsSaveController();
          $action->run();
        }
    }
}

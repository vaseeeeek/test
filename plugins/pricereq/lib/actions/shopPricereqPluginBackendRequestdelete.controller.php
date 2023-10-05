<?php

/*
 * Class shopPricereqPluginBackendRequestdeleteController
 * @author Max Severin <makc.severin@gmail.com>
 */
class shopPricereqPluginBackendRequestdeleteController extends waJsonController {

    public function execute() {
        $id = waRequest::post('id', 0, 'int');  
        
        $model = new shopPricereqPluginRequestModel();

        if ( $id ) {
            $model->updateById($id, array('status' => 'del'));
            
            $this->response = true;
        } else {
            $this->response = false;
        }            
    }
    
}
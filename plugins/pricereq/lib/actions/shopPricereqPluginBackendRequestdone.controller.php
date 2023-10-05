<?php

/*
 * Class shopPricereqPluginBackendRequestdoneController
 * @author Max Severin <makc.severin@gmail.com>
 */
class shopPricereqPluginBackendRequestdoneController extends waJsonController {

    public function execute() {
        $id = waRequest::post('id', 0, 'int');  
        
        $model = new shopPricereqPluginRequestModel();

        if ( $id ) {
            $model->updateById($id, array('status' => 'done'));
            
            $this->response = true;
        } else {
            $this->response = false;
        }            
    }
    
}
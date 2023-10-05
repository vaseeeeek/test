<?php

/*
 * Class shopCallbPluginBackendRequestdeleteController
 * @author Max Severin <makc.severin@gmail.com>
 */
class shopCallbPluginBackendRequestdeleteController extends waJsonController {

    public function execute() {
        $id = waRequest::post('id', 0, 'int');  
        
        $model = new shopCallbPluginRequestModel();

        if ( $id ) {
            $model->updateById($id, array('status' => 'del'));
            
            $this->response = true;
        } else {
            $this->response = false;
        }            
    }
    
}
<?php

/*
 * Class shopCallbPluginBackendRequestdoneController
 * @author Max Severin <makc.severin@gmail.com>
 */
class shopCallbPluginBackendRequestdoneController extends waJsonController {

    public function execute() {
        $id = waRequest::post('id', 0, 'int');  
        
        $model = new shopCallbPluginRequestModel();

        if ( $id ) {
            $model->updateById($id, array('status' => 'done'));
            
            $this->response = true;
        } else {
            $this->response = false;
        }            
    }
    
}
<?php

class shopSkoneclickPluginBackendFieldDeleteController extends waJsonController{

    public function execute(){

        $control_id = waRequest::post("control_id");

        if(!$control_id){
            return false;
        }

        $controlsModel = new shopSkoneclickControlsModel();

        $controlsModel->deleteByField("control_id", $control_id);

        return true;

    }

}
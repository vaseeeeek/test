<?php
class shopFiwexPluginTriggerController extends waJsonController
{
    function execute()
    {
        $state = waRequest::post('state', waRequest::TYPE_INT);
        $app_settings = new waAppSettingsModel();
        if ($state == 1) {
            $app_settings->set(wa()->getApp('shop').'.fiwex','enable',0);
            $this->response['state'] = 0;
        } else if($state == 0) {
            $app_settings->set(wa()->getApp('shop').'.fiwex','enable',1);
            $this->response['state'] = 1;
        }
    }
}
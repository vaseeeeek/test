<?php

class shopEmailformPluginBackendDeleteallController extends waJsonController
{
    public function execute()
    {
        $pluginm = new shopEmailformPluginModel();

        if ($pluginm->deleteAll()) {
        	waLog::log('delete all emails', "emailform/emailform.log");
            $this->response = "ok";
        } else {
            $this->errors = "error";
        }
    }
}

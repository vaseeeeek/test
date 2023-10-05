<?php

class shopListfeaturesPluginSettingsTemplateSaveController extends waJsonController
{
    public function execute()
    {
        $template = waRequest::post('template');
        $result = shopListfeaturesPluginHelper::saveTemplate($template, waRequest::post('source'));
        if (!$template) {
            $this->response['new_template'] = $result;
        }
    }
}

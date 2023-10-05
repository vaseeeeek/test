<?php

class shopListfeaturesPluginSettingsTemplateViewController extends waController
{
    public function execute()
    {
        $template = waRequest::post('template');
        echo htmlentities(shopListfeaturesPluginHelper::getTemplate($template));
    }
}

<?php

class shopTageditorPluginBackendSuggestUrlController extends waController
{
    public function execute()
    {
        echo shopHelper::transliterate(waRequest::post('tag'));
    }
}

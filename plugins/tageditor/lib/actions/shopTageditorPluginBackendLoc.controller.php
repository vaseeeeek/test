<?php

class shopTageditorPluginBackendLocController extends waViewController
{
    public function preExecute()
    {
        // do not save this page as last visited
    }

    public function execute()
    {
        $this->executeAction(new shopTageditorPluginBackendLocAction());
    }
}

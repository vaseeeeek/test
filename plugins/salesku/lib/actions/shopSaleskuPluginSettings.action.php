<?php

class  shopSaleskuPluginSettingsAction extends waViewAction
{
    public function execute() {
       $this->view->assign('storefronts',shopSaleskuPluginSettings::getStorefronts());
     }
}

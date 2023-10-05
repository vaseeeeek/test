<?php

/*
 * Class shopPricereqPluginBackendLayout
 * @author Max Severin <makc.severin@gmail.com>
 */
class shopPricereqPluginBackendLayout extends shopBackendLayout {

    public function execute() {
        parent::execute();
        $this->assign('page', 'pricereq');
    }

}
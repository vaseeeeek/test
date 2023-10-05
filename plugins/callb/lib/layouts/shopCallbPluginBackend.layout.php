<?php

/*
 * Class shopCallbPluginBackendLayout
 * @author Max Severin <makc.severin@gmail.com>
 */
class shopCallbPluginBackendLayout extends shopBackendLayout {

    public function execute() {
        parent::execute();
        $this->assign('page', 'callb');
    }

}
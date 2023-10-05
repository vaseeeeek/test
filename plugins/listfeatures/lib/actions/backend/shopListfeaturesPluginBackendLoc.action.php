<?php

class shopListfeaturesPluginBackendLocAction extends waViewAction
{
    public function execute()
    {
        $strings = array();
        foreach (array(
            'Delete',
            'cancel',
            'Delete set %s?',
            'Edit settings for feature %s',
            'Edit settings for SKUs',
            'Edit settings for tags',
            'Edit settings for categories',
            'view template',
            'Close',
            'close',
            'New template',
            'Template %s',
            'Edit this default template as you need.',
            'delete template',
        ) as $id) {
            $strings[$id] = _wp($id);
        }
        $this->view->assign('strings', $strings ? $strings : new stdClass());
        $this->getResponse()->addHeader('Content-Type', 'text/javascript; charset=utf-8');
    }
}

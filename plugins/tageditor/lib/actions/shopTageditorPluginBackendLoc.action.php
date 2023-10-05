<?php

class shopTageditorPluginBackendLocAction extends waViewAction
{
    public function execute()
    {
        $strings = array();
        foreach (array(
            'Tag editor',
            'This tag will be removed from all products! Continue?',
            'View products with this tag',
            'Delete',
            'cancel',
            'Delete all product tags from your online store?',
            'Save',
            'A tag cannot be empty.',
            'Apply this value to all tags',
        ) as $id) {
            $strings[$id] = _wp($id);
        }
        $this->view->assign('strings', $strings);
        $this->getResponse()->addHeader('Content-Type', 'text/javascript; charset=utf-8');
    }
}

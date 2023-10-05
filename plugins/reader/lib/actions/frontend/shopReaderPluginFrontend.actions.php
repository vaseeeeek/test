<?php
class shopReaderPluginFrontendActions extends waViewActions
{
    public function execute()
    {
        if ($productId = waRequest::get('id',false,'int')) {
            $this->view->assign('id', $productId);
        }
    }
}

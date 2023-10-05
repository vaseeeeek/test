<?php
class shopProductalbumPluginBackendSavealbumAction extends waViewAction
{
    public function execute()
    {
        $product_id = waRequest::post('product_id');
        $album_id = waRequest::post('album_id');
        
        $model = new shopProductalbumModel();
        $model->addAlbumToProduct($product_id, $album_id);

        $this->view->assign('result', 'success');
    }
}

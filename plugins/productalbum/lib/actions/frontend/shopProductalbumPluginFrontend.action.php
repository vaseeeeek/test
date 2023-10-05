<?php

class shopProductalbumPluginFrontendAction extends waViewAction
{
    public function execute()
    {
        // Получаем ID продукта из GET-параметров
        $productId = waRequest::get('id', 0, 'int');

        // Подключаем модель
        $model = new shopProductalbumModel();

        // Получаем все альбомы, связанные с этим продуктом
        $albums = $model->getAlbumsByProduct($productId);

        // Передаем данные в шаблон
        $this->view->assign('albums', $albums);
        $this->view->assign('product_id', $productId);
        
        $plugin_path = wa()->getAppPath('plugins/productalbum/', 'shop');
        
        // Подключение CSS и JS
        $this->getResponse()->addCss($plugin_path . 'css/productalbum.css', 'backend');
        $this->getResponse()->addJs($plugin_path . 'js/productalbum.js', 'backend');
    }
}

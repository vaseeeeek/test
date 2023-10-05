<?php

class shopProductalbumPluginProductAlbumFormAction extends waViewAction
{
        public function execute()
        {
                $view = wa()->getView();

                // Получение альбомов приложения фото
                $photosAlbums = array();
                wa('photos');
                $album_model = new photosAlbumModel();
                $photosAlbums = $album_model->getAlbums();
                wa('shop');
                // Передача данных в шаблон
                $view->assign('photosAlbums', $photosAlbums);


                // Получаем ID продукта из GET-параметров
                $productId = waRequest::get('id', 0, 'int');
                // Подключаем модель
                $model = new shopProductalbumModel();

                // Получаем все альбомы, связанные с этим продуктом
                $selectedAlbum = $model->getAlbumsByProduct($productId);

                // Передаем данные в шаблон
                $this->view->assign('selectedAlbum', $selectedAlbum);
                $this->view->assign('product_id', $productId);


                $plugin_path = wa()->getAppPath('plugins/productalbum/', 'shop');

                
                // Получение содержимого файлов
                $css_content = file_get_contents($plugin_path . 'css/productalbum.css');
                $js_content = file_get_contents($plugin_path . 'js/productalbum.js');
                $view->assign('css_content', $css_content);
                $view->assign('js_content', $js_content);
        }
}
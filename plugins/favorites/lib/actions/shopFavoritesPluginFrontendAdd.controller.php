<?php

class shopFavoritesPluginFrontendAddController extends waJsonController
{
    public function execute()
    {
        $user = wa()->getUser();
        if (!$user->isAuth()) {
            $this->errors = 'Вы не авторизованы';
            return;
        }

        $product_id = waRequest::post('product_id', 0, waRequest::TYPE_INT);
        if (!$product_id) {
            $this->errors = 'Товар не найден';
            return;
        }

        $product_model = new shopProductModel();
        $product = $product_model->getById($product_id);
        if (!$product) {
            $this->errors = 'Вы не авторизованы';
            return;
        }

        $model_favorites = new shopFavoritesModel();
        $model_favorites->insert(array('contact_id' => $user->getId(), 'product_id' => $product_id), 2);
        $c = $model_favorites->countByField(array('contact_id' => $user->getId()));

        /**
         * @var shopFavoritesPlugin $plugin
         */
        $plugin = wa()->getPlugin('favorites');
        $url = wa()->getRouteUrl('shop/frontend/my').'favorites/';
        $this->response['html'] = $plugin->delHtml($url, $c);
        $this->response['count'] = $c;
    }
}
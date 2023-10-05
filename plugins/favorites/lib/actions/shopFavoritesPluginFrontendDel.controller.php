<?php

class shopFavoritesPluginFrontendDelController extends waJsonController
{
    public function execute()
    {
        $user = new waAuthUser();
        if (!$user->isAuth()) {
            $this->errors = 'Вы не авторизованы';
            return;
        }

        $product_id = waRequest::post('product_id', 0, waRequest::TYPE_INT);
        if (!$product_id) {
            $this->errors = 'Товар не найден';
            return false;
        }

        $model_favorites = new shopFavoritesModel();
        $model_favorites->deleteByField(array('contact_id' => $user->getId(), 'product_id' => $product_id));

        $plugin = wa()->getPlugin('favorites');
        $this->response['html'] = '<a href="'.wa()->getRouteUrl('shop/frontend/my').'favorites/add/" class="add">'.$plugin->getSettings('add').'</a>';
        $this->response['count'] = $model_favorites->countByField(array('contact_id' => $user->getId()));
    }
}
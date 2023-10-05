<?php
/**
 * @author Плагины Вебасист <info@wa-apps.ru>
 * @link http://wa-apps.ru/
 */
class shopFavoritesPluginSettingsAction extends waViewAction
{
    public function execute()
    {
        $plugin = wa()->getPlugin('favorites');
        $this->view->assign('settings', $plugin->getSettings());

        try {
            $model = new shopFavoritesModel();
            $this->view->assign('products', $model->getTopProducts());
            $this->view->assign('count', $model->countAll());
        } catch (waDbException $e) {
            $this->view->assign('products', array());
        }

    }

}


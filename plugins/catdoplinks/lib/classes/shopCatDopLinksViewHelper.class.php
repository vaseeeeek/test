<?php
/**
 * Created by PhpStorm.
 * User: rmjv
 * Date: 14/11/2018
 * Time: 16:05
 */

class shopCatDopLinksViewHelper extends shopViewHelper
{

    public static function getDopLinks($category_id = 0)
    {
        $plugin = wa('shop')->getPlugin('catdoplinks');
        $settings = $plugin->getSettings();
        if (!$settings['enable']){
            return '';
        }

        if(!$category_id){
            return '';
        }
        $plugin = wa()->getPlugin('catdoplinks');
        $view = wa()->getView();

        wa()->getResponse()->addCss('plugins/catdoplinks/css/catdoplinks.css', 'shop');
        wa()->getResponse()->addCss('plugins/catdoplinks/css/swiper.min.css', 'shop');
        wa()->getResponse()->addJs('plugins/catdoplinks/js/swiper.min.js', 'shop');

        $model = new shopCatdoplinksPluginModel();
        $sql = 'SELECT * FROM '.$model->getTableName().' WHERE category_id='.(int)$category_id.' ORDER BY sort';

        $items = $model->query($sql)->fetchAll();

        $view->assign('items', $items);

        $content = $view->fetch($plugin->getPath().'/templates/frontend/links.html');

        echo $content;
    }
}
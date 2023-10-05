<?php

class shopCatdoplinksPlugin extends shopPlugin
{

    public function saveSettings($settings = array())
    {

        $css = wa()->getRequest()->post()['shop_catdoplinks_css'];
        file_put_contents($this->getPath() . '/css/catdoplinks.css', strip_tags($css));

        parent::saveSettings($settings);
    }


    public function backendCategoryDialog($category)
    {
        $plugin = wa('shop')->getPlugin('catdoplinks');
        $settings = $plugin->getSettings();
        if (!$settings['enable']){
            return '';
        }

        $view = wa()->getView();
        $model = new shopCatdoplinksPluginModel();

        if((int)$category['id'] > 0){
            $sql = 'SELECT MAX(sort)+1 as sort FROM `'.$model->getTableName().'` WHERE `category_id`='.$category['id'];
            $catdoplinks_sort = $model->query($sql)->fetchAssoc();
        } else {
            $catdoplinks_sort['sort'] = 1;
        }

        $items = $model->getByField('category_id', $category['id'], true);

        $view->assign('catdoplinks_sort', $catdoplinks_sort['sort']!=null?$catdoplinks_sort['sort']:1);
        $view->assign('category', $category);
        $view->assign('catdoplinks', $items);
        $path = wa()->getAppPath('plugins/catdoplinks/templates/', 'shop');
        $content = $view->fetch($path . 'CategoryFields.html');
        return $content;

    }


    public function deleteCategory($category)
    {
        $target_model = new shopCatdoplinksPluginModel();

        $public_path = wa()->getDataPath("catdoplinks/{$category['id']}/", true, 'shop');

        $target_model->deleteByField('category_id',$category['id']);

        waFiles::delete($public_path, false);
    }

    public function getPath()
    {
        return $this->path;
    }
}

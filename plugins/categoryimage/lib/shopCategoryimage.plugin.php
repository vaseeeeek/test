<?php

/**
 * @author Плагины Вебасист <info@wa-apps.ru>
 * @link http://wa-apps.ru/
 */
class shopCategoryimagePlugin extends shopPlugin
{
    public function categoryDialog($category)
    {
        $view = wa()->getView();
        $view->assign('category', $category);
        if ($category['image']) {
            $view->assign('image_url', $this->getImageUrl($category, '96'));
        }
        return $view->fetch($this->path.'/templates/Dialog.html');
    }

    public function categoryTitle($params)
    {
        if ($params && $params['type'] == 'category' && $params['info']['image']) {
            $view = wa()->getView();
            $view->assign('image_url', $this->getImageUrl($params['info'], '96'));
            return array(
                'title_suffix' => $view->fetch($this->path.'/templates/Title.html')
            );
        }
    }

    protected function getImageUrl($c, $size = '')
    {
        $path = 'categories/'.$c['id'].'/'.$c['id'].($size ? '.'.$size : '').$c['image'];
        return wa()->getDataUrl($path, true).'?v='.filemtime(wa()->getDataPath($path, true));
    }

    public function categoryDelete($category)
    {
        if (is_numeric($category)) {
            $category_model = new shopCategoryModel();
            $category = $category_model->getById($category);
        }

        if ($category && !empty($category['image'])) {
            $path = wa()->getDataPath('categories/'.$category['id'], true, 'shop', false);
            waFiles::delete($path);
        }
    }

    public function categorySave($category)
    {
        $category_model = new shopCategoryModel();
        if (!waRequest::post('image')) {
            $category_model->updateById($category['id'], array('image' => ''));
            waFiles::delete(wa()->getDataPath('categories/'.$category['id'], true, 'shop', false));
        }

        $image = waRequest::file('image_file');
        if ($image->uploaded() && in_array(strtolower($image->extension), array('jpg', 'jpeg', 'png', 'gif'))) {
            $category['image'] = '.'.$image->extension;
            $path = wa()->getDataPath('categories/'.$category['id'].'/', true, 'shop');
            $image->moveTo($path, $category['id'].$category['image']);
            $sizes = $this->getSettings('sizes');
            if (!$sizes) {
                $sizes = '96';
            }
            $sizes = explode(';', $sizes);
            foreach ($sizes as $size) {
                if ($thumb_img = shopImage::generateThumb($path.$category['id'].$category['image'], $size)) {
                    $thumb_img->save($path.$category['id'].'.'.$size.$category['image']);
                }
            }
            $category_model->updateById($category['id'], array('image' => $category['image']));
        }
    }
}
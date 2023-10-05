<?php
/**
 * Created by PhpStorm.
 * User: rmjv
 * Date: 14/11/2018
 * Time: 12:20
 */

class shopCatdoplinksPluginAddController extends waJsonController
{

    public function execute()
    {
        $img = waRequest::file('file');
        $name = trim(waRequest::post('name', '', 'string'));
        $link = trim(waRequest::post('link', '', 'string'));
        $category_id = (int)waRequest::post('category_id', 0, 'int');
        $sort = (int)waRequest::post('sort', 1, 'int');


        if($name == '' || $link == '' || $category_id <= 0 || $img->error != '')
        {
            $this->setError('Заполните все поля!');
        } else {

            $image = $img->waImage();
            $path = wa()->getDataPath("catdoplinks/{$category_id}/", true, 'shop');
            $url = wa()->getDataUrl("catdoplinks/{$category_id}/", true, 'shop');

            $data = array(
                'category_id'=>$category_id,
                'name'=>$name,
                'link'=>$link,
                'sort'=>$sort,
                'img'=>"catdoplinks/{$category_id}/".$img->name,
            );
            $model = new shopCatdoplinksPluginModel();
            
            try{
                $data = $model->escape($data);
                $id = $model->insert($data);
                $image->save($path.$img->name);
            } catch (waException $e) {
                echo "%catdoplinks_plugin%Файл {$img->name} не является изображением, либо произошла другая ошибка -  ".$e->getMessage()."%catdoplinks_plugin%";
            }


           $this->response['sort'] = $sort+1;

            $ret = '<div class="catdoplinks-item" style="display: flex;">
                    <div class="catdoplinks-item-img"><img src="'.wa()->getDataUrl($data['img'], true, 'shop').'" alt="'.wa()->getDataUrl($data['img'], true, 'shop').'"></div>
                    <div class="catdoplinks-item-name">'.$data['name'].'</div>
                    <div class="catdoplinks-item-link">'.$data['link'].'</div>
                    <div class="catdoplinks-item-sort">'.$data['sort'].'</div>
                    <div class="catdoplinks-item-del"><a href="#" class="catdoplinks-item-del-item" data-id="'.(int)$id.'"><i class="icon16 delete"></i></a></div>
            </div>';

            $this->response['ret'] = $ret;
        }

    }
}
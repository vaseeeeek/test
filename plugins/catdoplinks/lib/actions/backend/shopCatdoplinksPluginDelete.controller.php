<?php
/**
 * Created by PhpStorm.
 * User: rmjv
 * Date: 14/11/2018
 * Time: 14:34
 */

class shopCatdoplinksPluginDeleteController extends waJsonController
{
    public function execute()
    {
        $id = (int)waRequest::post('id', 0, 'int');


        if($id > 0){
            $model = new shopCatdoplinksPluginModel();
            $item = $model->getById($id);

            try{
                if($item){
                    $path = wa()->getDataPath($item['img'], true, 'shop');
                    $model->deleteById($id);
                    waFiles::delete($path);
                }
            } catch (waException $e) {
                echo "%catdoplinks_plugin%Произошла ошибка -  ".$e->getMessage()."%catdoplinks_plugin%";
            }
        }
    }
}
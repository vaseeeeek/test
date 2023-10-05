<?php
class shopServicesetsPluginBackendEditserviceController extends waController
{
    public function execute()
    {
        $model = new shopServicesetsPluginServicesModel();
        $data = $model->escape(waRequest::post());
        $id = $data['id_service'];

        if ($data['reset-image-one'] == 'reset') {
            $data['image_one'] = '';
            $data['format_one'] = '';
        } elseif ($data['reset-image-two'] == 'reset') {
            $data['image_two'] = '';
            $data['format_two'] = '';
        } else {
            $image = waRequest::file('image_one');
            if ($image->uploaded() && in_array(strtolower($image->extension), array('jpg', 'jpeg', 'png', 'gif'))) {
                $fileName = $data['id_service'] . '-1.' . strtolower($image->extension);
                $imagePath = wa('shop')->getAppPath('plugins/servicesets/', 'shop') . 'img/service/' . $fileName;
                try {
                    $image->waImage()
                        ->save($imagePath);
                    $data['image_one'] = $imagePath;
                    $data['format_one'] = strtolower($image->extension);
                } catch(Exception $e) {
                    echo "Файл не является изображением, либо произошла другая ошибка: ".$e->getMessage();
                    return;
                }
            }


            $image = waRequest::file('image_two');
            if ($image->uploaded() && in_array(strtolower($image->extension), array('jpg', 'jpeg', 'png', 'gif'))) {
                $fileName = $data['id_service'] . '-2.' . strtolower($image->extension);
                $imagePath = wa('shop')->getAppPath('plugins/servicesets/', 'shop') . 'img/service/' . $fileName;
                try {
                    $image->waImage()
                        ->save($imagePath);
                    $data['image_two'] = $imagePath;
                    $data['format_two'] = strtolower($image->extension);
                } catch(Exception $e) {
                    echo "Файл не является изображением, либо произошла другая ошибка: ".$e->getMessage();
                    return;
                }
            }
        }

        if ((bool)$model->countByField('id_service', $id)) {
            $model->updateByField('id_service', $id, $data);
        } else {
            $model->insert($data, 2);
        }

        //header('Location: '.stristr($_SERVER['REQUEST_URI'], '?', true) . '?action=plugins#/servicesets');
    }
}

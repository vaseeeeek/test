<?php
class shopServicesetsPluginBackendEditgroupController extends waController
{
    public function execute()
    {
        $model = new shopServicesetsPluginGroupsModel();
        $data = $model->escape(waRequest::post());
        $id = $data['id'];

        if ($data['reset-image-one'] == 'reset') {
            $data['image_one'] = '';
            $data['format_one'] = '';
        } elseif ($data['reset-image-two'] == 'reset') {
            $data['image_two'] = '';
            $data['format_two'] = '';
        } else {
            $image = waRequest::file('image_one');
            if ($image->uploaded() && in_array(strtolower($image->extension), array('jpg', 'jpeg', 'png', 'gif'))) {
                $fileName = $data['id'] . '-1.' . strtolower($image->extension);
                $imagePath = wa('shop')->getAppPath('plugins/servicesets/', 'shop') . 'img/group/' . $fileName;
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
                $fileName = $data['id'] . '-2.' . strtolower($image->extension);
                $imagePath = wa('shop')->getAppPath('plugins/servicesets/', 'shop') . 'img/group/' . $fileName;
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
        $model->updateByField('id', $id, $data);
    }
}

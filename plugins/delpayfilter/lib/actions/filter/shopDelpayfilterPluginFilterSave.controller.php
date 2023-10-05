<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopDelpayfilterPluginFilterSaveController extends waJsonController
{

    public function execute()
    {
        $id = waRequest::post("id", 0, waRequest::TYPE_INT);
        $data = waRequest::post("data");

        $model = new shopDelpayfilterPluginModel();
        
        $data['status'] = isset($data['status']) ? $data['status'] : 0;
        $data['check_email'] = !empty($data['check_email']) ? 1 : 0;
        $data['check_phone'] = !empty($data['check_phone']) ? 1 : 0;

        // Основные данные
        if ($id) {
            $model->updateById($id, $data);
        } else {
            $id = $model->insert($data);
        }

        $this->response = $id;
    }

}

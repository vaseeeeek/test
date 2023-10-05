<?php

/*
 * @author Gaponov Igor <gapon2401@gmail.com>
 */

class shopAutobadgePluginFilterSaveController extends waJsonController
{

    public function execute()
    {
        $id = waRequest::post("id", 0, waRequest::TYPE_INT);
        $data = waRequest::post("data");

        $model = new shopAutobadgePluginModel();

        $data['status'] = isset($data['status']) ? $data['status'] : 0;

        // Основные данные
        if ($id) {
            $model->updateById($id, $data);
        } else {
            $width = (int) waRequest::post("width", 200);
            $height = (int) waRequest::post("height", 200);

            $data['sort'] = $model->getMaxSort() + 1;
            $id = $model->insert($data);

            // Параметры
            $params = array("preview_width" => !empty($width) ? $width : 200, "preview_height" => !empty($height) ? $height : 200);
            $params_model = new shopAutobadgeParamsPluginModel();
            $params_model->add($id, $params);
        }

        $this->response = $id;
    }

}

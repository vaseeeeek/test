<?php

class shopStatusifyPluginBackendAddstatusAction extends waViewAction
{
    public function execute()
    {
        $status_name = waRequest::post('status_name');
        $type = waRequest::post('type');
        var_dump(waRequest::post());

        if ($status_name && $type) {
            $model = new waModel();
            $sql = "INSERT INTO shop_statusify_statuses (status_name, type) VALUES (:status_name, :type)";
            $model->exec($sql, array('status_name' => $status_name, 'type' => $type));
            $this->response['message'] = 'Статус успешно добавлен.';
        } else {
            $this->response['message'] = 'Не удалось добавить статус.';
        }
    }
}

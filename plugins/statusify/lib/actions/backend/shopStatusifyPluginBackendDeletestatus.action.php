<?php

class shopStatusifyPluginBackendDeletestatusAction extends waViewAction
{
    public function execute()
    {   
        $type_id = waRequest::post('type_id');
        $status_name = waRequest::post('status_name');
        
        if ($type_id && $status_name) {
            $model = new waModel();
            $sql = "DELETE FROM shop_statusify_statuses WHERE type = :type_id AND status_name = :status_name";
            $model->exec($sql, array('type_id' => $type_id, 'status_name' => $status_name));
            $this->response = array('status' => 'ok', 'message' => 'Статус удален');
        } else {
            $this->response = array('status' => 'error', 'message' => 'Не удалось удалить статус');
        }
    }
}

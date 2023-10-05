<?php

class shopStatusifyPluginSettingsAction extends waViewAction
{

    public function execute()
    {
        $model = new waModel();
        $sql = "SELECT * FROM shop_type";
        $product_types = $model->query($sql)->fetchAll();

        // Получение имеющихся статусов из таблицы плагина с JOIN на shop_type
        $sql = "SELECT s.*, t.id as type_id, t.name as type_name FROM shop_statusify_statuses s JOIN shop_type t ON s.type = t.id";
        $statuses = $model->query($sql)->fetchAll();
        
        $existing_statuses = [];
        foreach ($statuses as $status) {
            $existing_statuses[$status['type_name']]['statuses'][] = $status['status_name'];
            $existing_statuses[$status['type_name']]['type_id'] = $status['type_id'];
        }

        $this->view->assign('product_types', $product_types);
        $this->view->assign('existing_statuses', $existing_statuses);
    }
}
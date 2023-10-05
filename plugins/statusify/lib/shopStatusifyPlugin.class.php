<?php

class shopStatusifyPlugin extends shopPlugin
{
    public function getProductsStatus(){
        $user_id = wa()->getUser()->getId(); // Получаем ID текущего пользователя

        $model = new waModel();

        // Получаем информацию о статусах товаров для текущего пользователя
        $sql = "SELECT p.name as product_name, s.status_name 
                FROM shop_statusify_user_product_status ups
                JOIN shop_product p ON ups.product_id = p.id
                JOIN shop_statusify_statuses s ON ups.status_id = s.status_id
                WHERE ups.user_id = :user_id";
        
        $statuses = $model->query($sql, array('user_id' => $user_id))->fetchAll();

        return $statuses;
    }

    public function frontendMyNav()
    {
        $selected = waRequest::param('plugin') === 'statusify' ? 'selected' : '';
        return "<a href='/statusify/my/' class='{$selected}'>Мои статусы</a>";
    }
}

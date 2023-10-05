<?php
class shopStatusifyPluginFrontendAction extends waViewAction
{
    public function execute()
    {
        if (waRequest::get()) {
            $product_id = waRequest::get('product_id');

            // Создаем экземпляр модели для выполнения запроса
            $model = new waModel();

            $sql = "SELECT type_id FROM shop_product WHERE id = :product_id LIMIT 1";

            // Выполняем запрос и получаем результат
            $result = $model->query($sql, array('product_id' => $product_id))->fetchAssoc();

            if ($result) {
                $product_type = $result['type_id']; // Получаем статусы для этого типа товара

                $model = new waModel();
                $sql = "SELECT status_name FROM shop_statusify_statuses WHERE type = :type";
                $statuses = $model->query($sql, array('type' => $product_type))->fetchAll();
                echo json_encode($statuses);
            }
        }
        if (waRequest::post()) {
            $product_id = waRequest::post('product_id');
            $status_name = waRequest::post('status_name');
            $user_id = wa()->getUser()->getId(); // Получаем ID текущего пользователя

            if ($product_id && $status_name && $user_id) {
                $model = new waModel();

                // Получаем type_id по product_id
                $sql = "SELECT type_id FROM shop_product WHERE id = :product_id LIMIT 1";
                $product = $model->query($sql, array('product_id' => $product_id))->fetchAssoc();
                if (!$product) {
                    $this->response = array('status' => 'error', 'message' => 'Товар не найден');
                    return;
                }
                $type_id = $product['type_id'];

                // Получаем status_id по status_name и type_id
                $sql = "SELECT status_id FROM shop_statusify_statuses WHERE status_name = :status_name AND type = :type_id LIMIT 1";
                $status = $model->query($sql, array('status_name' => $status_name, 'type_id' => $type_id))->fetchAssoc();
                if (!$status) {
                    $this->response = array('status' => 'error', 'message' => 'Статус не найден');
                    return;
                }
                $status_id = $status['status_id'];

                // Проверяем, существует ли уже запись для данного пользователя и товара
                $sql = "SELECT * FROM shop_statusify_user_product_status WHERE user_id = :user_id AND product_id = :product_id LIMIT 1";
                $existing = $model->query($sql, array('user_id' => $user_id, 'product_id' => $product_id))->fetchAssoc();

                if ($existing) {
                    // Обновляем запись
                    $sql = "UPDATE shop_statusify_user_product_status SET status_id = :status_id WHERE user_id = :user_id AND product_id = :product_id";
                } else {
                    // Создаем новую запись
                    $sql = "INSERT INTO shop_statusify_user_product_status (user_id, product_id, status_id) VALUES (:user_id, :product_id, :status_id)";
                }

                // Выполняем SQL-запрос
                $model->exec($sql, array('user_id' => $user_id, 'product_id' => $product_id, 'status_id' => $status_id));

                $this->response = array('message' => 'Статус сохранен');
            } else {
                $this->response = array('message' => 'Не удалось сохранить статус');
            }
        }
    }
}
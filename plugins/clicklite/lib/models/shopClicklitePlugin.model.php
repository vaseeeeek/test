<?php

/**
 * Класс для работы с базой данных
 *
 * @author Steemy, created by 03.04.2018
 * @link http://steemy.ru/
 */


class shopClicklitePluginModel extends waModel
{
    protected $table = 'shop_clicklite_order_id';

    /**
     * Получаем список заказов сделанные через плагин
     *
     * @return array
     */
    public function getOrderList($offset = 0, $limit = null)
    {
        $sql = '';

        $sql .= "SELECT * FROM shop_order as t1 INNER JOIN {$this->table} as t2";
        $sql .= " ON t1.id = t2.order_id";

        $sql .= " ORDER BY `create_datetime` DESC";
        $sql .= " LIMIT " . ($offset ? $offset.',' : '').(int)$limit;

        return $this->query($sql)->fetchAll('order_id');
    }

    /**
     * Получаем количество заказов через 1 клик по статусу
     *
     * @return array
     */
    public function getCountLite($status = 'active')
    {
        $sql = '';

        $sql .= "SELECT count(*) FROM shop_order as t1  JOIN {$this->table} as t2";
        $sql .= " ON t1.id = t2.order_id";

        if($status == 'active')
        {
            $sql .= " WHERE NOT state_id='completed' AND NOT state_id='deleted'";
        }
        else
        {
            $sql .= " WHERE state_id='{$status}'";
        }

        return $this->query($sql)->fetchField();
    }

    /**
     * Получаем количество заказов через обычную корзину
     *
     * @return array
     */
    public function getCountNotLite()
    {
        $sql = '';

        $sql .= "SELECT count(*) FROM shop_order as t1 LEFT JOIN {$this->table} as t2";
        $sql .= " ON t1.id = t2.order_id";

        $sql .= " WHERE t2.id IS NULL";

        return $this->query($sql)->fetchField();
    }

    /**
     * Получаем список заказов для выгрузки в CSV
     *
     * @return array
     */
    public function getOrderListForCSV($status = 'all', $limit = 1000)
    {
        $sql = '';

        $st = $status!='all' ? " WHERE state_id='" . $status ."'" : '';

        $sql .= '
          SELECT t.*,
                 t3.name as contact_name,
                 t3.last_datetime,
                 t3.create_datetime as contact_create_datetime,
                 t4.*
          FROM
            (SELECT t1.*, t2.order_id FROM
              (SELECT * FROM shop_order' . $st . ") as t1
              INNER JOIN {$this->table} as t2
              ON t1.id = t2.order_id
              ORDER BY t1.create_datetime DESC LIMIT " . (int)$limit ."
            ) as t";

        $sql .= " INNER JOIN wa_contact as t3 ON t.contact_id = t3.id";
        $sql .= " INNER JOIN shop_order_items as t4 ON t.order_id = t4.order_id";
        $sql .= " ORDER BY t.create_datetime DESC";

        return $this->query($sql)->fetchAll();
    }
}

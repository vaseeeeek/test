<?php

/**
 * Установка базы данных
 *
 * @author Steemy, created by 03.04.2018
 * @link http://steemy.ru/
 */

return array(
    'shop_clicklite_order_id' => array(
        'id'     => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'order_id'   => array('int', 11, 'null' => 0),
        ':keys'  => array(
            'PRIMARY' => 'id',
        ),
    ),
);
<?php
return array(
    'shop_statusify_statuses' => array(
        'status_id' => array('int', 11, 'autoincrement' => true),
        'status_name' => array('varchar', 255),
        'type' => array('int', 11, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => 'status_id',
        ),
    ),
    'shop_statusify_user_product_status' => array(
        'id' => array('int', 11, 'autoincrement' => true, 'null' => 0),
        'user_id' => array('int', 11, 'null' => 0),
        'product_id' => array('int', 11, 'null' => 0),
        'status_id' => array('int', 11, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => 'id',
            'user_id' => 'user_id',
            'product_id' => 'product_id',
            'status_id' => 'status_id',
        ),
    ),
);
<?php

/*
 * @author Max Severin <makc.severin@gmail.com>
 */
return array(
    'shop_pricereq_request' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'contact_id' => array('int', 11),
        'product_id' => array('int', 11),
        'name' => array('text'),
        'phone' => array('text'),
        'email' => array('text'),
        'comment' => array('text'),
        'status' => array('text'),
        'create_datetime' => array('datetime', 'null' => 0),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
);

<?php

/*
 * @author Max Severin <makc.severin@gmail.com>
 */
return array(
    'shop_callb_request' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'contact_id' => array('int', 11),
        'create_datetime' => array('datetime', 'null' => 0),
        'name' => array('text'),
        'phone' => array('text'),
        'status' => array('text'),
        'url' => array('text'),
        'comment' => array('text'),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
);

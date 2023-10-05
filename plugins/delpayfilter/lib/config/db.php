<?php
return array(
    'shop_delpayfilter' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'description' => array('text'),
        'conditions' => array('text'),
        'target' => array('text'),
        'status' => array('tinyint', 1, 'null' => 0, 'default' => '1'),
        'name' => array('varchar', 200, 'null' => 0, 'default' => ''),
        'error_shipping' => array('text'),
        'error_payment' => array('text'),
        'check_email' => array('tinyint', 1, 'null' => 0, 'default' => '0'),
        'check_phone' => array('tinyint', 1, 'null' => 0, 'default' => '0'),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
);

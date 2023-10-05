<?php
return array(
    'shop_arrived' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'user_id' => array('int', 11, 'null' => 1, 'default' => null),
        'product_id' => array('int', 11, 'null' => 0),
        'sku_id' => array('int', 11, 'null' => 0),
        'domain' => array('varchar', 100, 'null' => 0),
        'route_url' => array('varchar', 255, 'null' => 0),
        'email' => array('varchar', 100, 'null' => 1, 'default' => null),
        'phone' => array('varchar', 20, 'null' => 1, 'default' => null),
        'sended' => array('int', 1, 'null' => 0, 'default' => 0),
        'expired' => array('int', 1, 'null' => 0, 'default' => 0),
        'date_sended' => array('datetime', 'null' => 1, 'default' => null),
        'expiration' => array('datetime', 'null' => 1, 'default' => null),
        'created' => array('datetime', 'null' => 0),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
);
<?php
return array(
    'shop_cityselect__variables' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'type_id' => array('int', 11, 'null' => 0),
        'region_id' => array('int', 11, 'null' => 0),
        'value' => array('text'),
        ':keys' => array(
            'PRIMARY' => 'id',
            'type_id' => 'type_id',
            'region_id' => 'region_id',
        ),
    ),
    'shop_cityselect__variables_type' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'code' => array('varchar', 255, 'null' => 0),
        'name' => array('varchar', 255),
        'template' => array('text'),
        ':keys' => array(
            'PRIMARY' => 'id',
            'code' => 'code',
        ),
    ),
    'shop_cityselect__region' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'region' => array('int', 11, 'null' => 0),
        'city' => array('varchar', 255, 'null' => 0),
        'redirect' => array('varchar', 255, 'null' => 0, 'default' => ''),
        ':keys' => array(
            'PRIMARY' => 'id',
            'region' => array('region', 'city'),
        ),
    ),
    'shop_cityselect__cookies' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'key' => array('varchar', 32, 'null' => 0),
        'data' => array('mediumtext'),
        ':keys' => array(
            'PRIMARY' => 'id',
            'key' => 'key',
        ),
    ),
);

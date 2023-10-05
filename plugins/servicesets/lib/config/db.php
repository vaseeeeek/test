<?php

return array(
    'shop_servicesets_groups' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'groupname' => array('varchar', 250, 'null' => ''),
        'description' => array('varchar', 250, 'null' => ''),
        'format_one' => array('varchar', 250, 'null' => ''),
        'format_two' => array('varchar', 250, 'null' => ''),
        'image_one' => array('varchar', 250, 'null' => ''),
        'image_two' => array('varchar', 250, 'null' => ''),
        'ids' => array('varchar', 250, 'null' => ''),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
    'shop_servicesets_services' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'id_service' => array('int', 11),
        'description' => array('varchar', 250, 'null' => ''),
        'format_one' => array('varchar', 250, 'null' => ''),
        'format_two' => array('varchar', 250, 'null' => ''),
        'image_one' => array('varchar', 250, 'null' => ''),
        'image_two' => array('varchar', 250, 'null' => ''),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
    'shop_servicesets_variants' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'id_variants' => array('int', 11),
        'description' => array('varchar', 250, 'null' => ''),
        'format_one' => array('varchar', 250, 'null' => ''),
        'format_two' => array('varchar', 250, 'null' => ''),
        'image_one' => array('varchar', 250, 'null' => ''),
        'image_two' => array('varchar', 250, 'null' => ''),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
);

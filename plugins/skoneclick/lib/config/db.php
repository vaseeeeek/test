<?php
return array(

    'shop_skoneclick_defines' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'name' => array('varchar', 64, 'null' => 0),
        'value' => array('text'),
        ':keys' => array(
            'PRIMARY' => 'id',
            'name' => array('name', 'unique' => 1),
        ),
    ),

    'shop_skoneclick_controls' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'control_id' => array('varchar', 64, 'null' => 0),
        'title' => array('varchar', 255, 'null' => 0),
        'class' => array('varchar', 255, 'null' => 0),
        'additional' => array('varchar', 255),
        'is_mask' => array('tinyint', 4, 'default' => 0),
        'mask' => array('varchar', 64, 'default' => ''),
        'require' => array('tinyint', 4, 'default' => 0),
        'sort' => array('int', 11, 'default' => 0),
        ':keys' => array(
            'PRIMARY' => 'id',
            'control_id' => array('control_id', 'unique' => 1),
        ),
    ),

);
<?php
return array(
    'shop_complex_condition' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'field' => array('varchar', 255, 'null' => 0),
        'value' => array('varchar', 255, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
    'shop_complex_condition_group' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'mode' => array('varchar', 3, 'null' => 0, 'default' => 'and'),
        'conditions' => array('varchar', 255, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
    'shop_complex_price' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'status' => array('int', 1, 'null' => 0, 'default' => '1'),
        'sort' => array('int', 11, 'null' => 0, 'default' => '0'),
        'name' => array('varchar', 32, 'null' => 0),
        'rule_id' => array('int', 11, 'null' => 0),
        'default_style' => array('int', 1, 'null' => 0, 'default' => '0'),
        'default_from' => array('int', 1, 'null' => 0, 'default' => '0'),
        'default_value' => array('float', 'null' => 0, 'default' => '0'),
        'rounding' => array('decimal', "8,2", 'null' => 0, 'default' => '0.00'),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
    'shop_complex_rule' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'condition_group_id' => array('int', 11, 'null' => 0),
        'create_timestamp' => array('timestamp', 'null' => 0, 'default' => 'CURRENT_TIMESTAMP'),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
);

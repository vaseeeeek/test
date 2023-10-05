<?php
return array(
    'shop_edit_log' => array(
        'id' => array('int', 10, 'unsigned' => 1, 'null' => 0, 'autoincrement' => 1),
        'action' => array('varchar', 100, 'null' => 0),
        'datetime' => array('datetime', 'null' => 0),
        'actor_id' => array('int', 10, 'unsigned' => 1, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
    'shop_edit_log_param' => array(
        'log_id' => array('int', 10, 'unsigned' => 1, 'null' => 0),
        'name' => array('varchar', 64, 'null' => 0),
        'value' => array('text'),
        'value_is_json' => array('enum', "'Y','N'", 'null' => 0, 'default' => 'N'),
        ':keys' => array(
        ),
    ),
);

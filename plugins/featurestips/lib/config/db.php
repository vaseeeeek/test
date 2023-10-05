<?php
return array(
    'shop_featurestips_tips' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'feature_id' => array('int', 11, 'null' => 0),
        'type_id' => array('int', 11, 'null' => 0),
        'value' => array('text', 'null' => 0),
        'global' => array('tinyint', 1, 'null' => 0, 'default' => '1'),
        'visible' => array('tinyint', 1, 'null' => 0, 'default' => '1'),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
);
<?php
return array(
    'shop_colorfeaturespro' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'color_id' => array('int', 11),
        'name' => array('varchar', 100),
        'style' => array('text'),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
);

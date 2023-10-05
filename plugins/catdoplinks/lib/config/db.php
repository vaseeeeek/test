<?php
return array(
    'shop_catdoplinks' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'category_id' => array('int', 11, 'null' => 0),
        'name' => array('varchar', 255, 'null' => 0),
        'link' => array('varchar', 255, 'null' => 0),
        'img' => array('varchar', 255, 'null' => 0),
        'sort' => array('int', 11, 'null' => 0, 'default' => '0'),
        ':keys' => array(
            'PRIMARY' => 'id',
            'categoryID' => 'category_id',
        ),
    ),
);

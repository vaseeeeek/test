<?php

return array(
    'shop_productbrands' => array(
        'id' => array('int', 11, 'null' => 0),
        'name' => array('varchar', 255, 'null' => 0),
        'summary' => array('varchar', 255, 'null' => 1),
        'description' => array('text', 'null' => 1),
        'title' => array('varchar', 255, 'null' => 1),
        'h1' => array('varchar', 255, 'null' => 1),
        'meta_keywords' => array('text', 'null' => 1),
        'meta_description' => array('text', 'null' => 1),
        'seo_description' => array('text', 'null' => 1),
        'image' => array('varchar', 5, 'null' => 1),
        'url' => array('varchar', 255, 'null' => 1),
        'filter' => array('text', 'null' => 1),
        'hidden' => array('tinyint', 1, 'null' => 0, 'default' => 0),
        'sort_products' => array('varchar', 32, 'null' => 1),
        'enable_sorting' => array('tinyint', 1, 'null' => 0, 'default' => 0),
        'params' => array('text'),
        ':keys' => array(
            'PRIMARY' => 'id',
            'url' => 'url',
        )
    )
);
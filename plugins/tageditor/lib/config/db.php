<?php
return array(
    'shop_tageditor_index_product_tags' => array(
        'product_id' => array('int', 11, 'unsigned' => 1, 'null' => 0),
        'tag_id' => array('int', 11, 'unsigned' => 1, 'null' => 0),
        'type_id' => array('int', 11, 'unsigned' => 1, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => array('product_id', 'tag_id'),
        ),
    ),
    'shop_tageditor_index_tag' => array(
        'tag_id' => array('int', 11, 'unsigned' => 1, 'null' => 0),
        'type_id' => array('int', 11, 'unsigned' => 1, 'null' => 0),
        'count' => array('int', 11, 'unsigned' => 1, 'null' => 0),
        ':keys' => array(
            'tag_type' => array('tag_id', 'type_id', 'unique' => 1),
        ),
    ),
    'shop_tageditor_tag' => array(
        'id' => array('int', 11, 'unsigned' => 1, 'null' => 0),
        'meta_title' => array('varchar', 255),
        'meta_description' => array('text'),
        'meta_keywords' => array('text'),
        'og_title' => array('varchar', 255),
        'og_description' => array('text'),
        'url' => array('varchar', 255),
        'title' => array('text'),
        'description' => array('text'),
        'description_extra' => array('text'),
        'sort_products' => array('varchar', 32),
        'edit_datetime' => array('datetime'),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
);

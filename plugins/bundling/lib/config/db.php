<?php
return array(
    'shop_bundling_bundles' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'product_id' => array('int', 9),
        'type_id' => array('int', 9),
        'category_id' => array('int', 9),
        'subcategories' => array('int', 1, 'null' => 0, 'default' => '0'),
        'sort' => array('int', 11, 'null' => 0, 'default' => '0'),
        'feature_id' => array('int', 11),
        'feature_value' => array('int', 11),
        'title' => array('varchar', 255),
        'multiple' => array('int', 1, 'default' => '0'),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
    'shop_bundling_categories' => array(
        'category_id' => array('int', 11, 'null' => 0),
        'title' => array('varchar', 255, 'null' => 0),
        'multiple' => array('int', 1, 'null' => 0, 'default' => '0'),
        ':keys' => array(
            'PRIMARY' => 'category_id',
        ),
    ),
    'shop_bundling_products' => array(
        'product_id' => array('int', 11, 'null' => 0),
        'bundle_id' => array('int', 11, 'null' => 0),
        'bundled_product_id' => array('int', 11, 'null' => 0, 'default' => '0'),
        'sku_id' => array('int', 11, 'null' => 0, 'default' => '0'),
        'default_quantity' => array('decimal', "15,4", 'null' => 0, 'default' => '1.0000'),
        'discount' => array('int', 11, 'null' => 0, 'default' => '0'),
        'sort' => array('int', 11, 'null' => 0, 'default' => '0'),
        'params' => array('text'),
        ':keys' => array(
            'PRIMARY' => array('product_id', 'bundle_id', 'bundled_product_id', 'sku_id'),
        ),
    ),
    'shop_bundling_sort' => array(
        'bundle_id' => array('int', 11, 'null' => 0),
        'product_id' => array('int', 11, 'null' => 0),
        'sort' => array('int', 11, 'null' => 0, 'default' => '0'),
        ':keys' => array(
            'PRIMARY' => array('bundle_id', 'product_id'),
        ),
    ),
);

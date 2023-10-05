<?php
return array(
    'shop_salesku_storefront' => array(
        'id' => array('int', 11, 'unsigned' => 1, 'null' => 0, 'autoincrement' => 1),
        'name' => array('varchar', 255, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => 'id',
            'name' => array('name', 'unique' => 1),
        ),
    ),
    'shop_salesku_feature_settings' => array(
        'storefront_id' => array('int', 11, 'unsigned' => 1, 'null' => 0),
        'feature_id' => array('int', 11, 'unsigned' => 1, 'null' => 0),
        'product_type_id' => array('int', 11, 'unsigned' => 1),
        'key' => array('varchar', 70, 'null' => 0),
        'value' => array('varchar', 255, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => array('storefront_id', 'feature_id', 'key'),
            'feature_id_storefront_key_product_type_id' => array('feature_id', 'storefront_id', 'key', 'product_type_id', 'unique' => 1),
            'IDX_shop_slssku_storefront' => 'storefront_id',
        ),
    ),
    'shop_salesku_product_type_settings' => array(
        'storefront_id' => array('int', 11, 'unsigned' => 1, 'null' => 0),
        'product_type_id' => array('int', 11, 'unsigned' => 1, 'null' => 0, 'default' => '0'),
        'key' => array('varchar', 70, 'null' => 0),
        'value' => array('varchar', 255, 'null' => 0),
        ':keys' => array(
            'PRIMARY' => array('storefront_id', 'product_type_id', 'key'),
            'IDX_shop_slssku_storefront' => 'storefront_id',
            'storefront_product_type_id' => array('storefront_id', 'product_type_id'),
        ),
    ),
    'shop_salesku_settings' => array(
        'storefront_id' => array('int', 11, 'unsigned' => 1, 'null' => 0),
        'key' => array('varchar', 100, 'null' => 0),
        'value' => array('text'),
        ':keys' => array(
            'PRIMARY' => array('storefront_id', 'key'),
            'storefront_id' => 'storefront_id',
        ),
    ),
);
